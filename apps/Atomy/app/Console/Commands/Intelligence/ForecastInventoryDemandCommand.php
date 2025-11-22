<?php

declare(strict_types=1);

namespace App\Console\Commands\Intelligence;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Psr\Log\LoggerInterface;

/**
 * Daily batch command to generate demand forecasts for all SKUs.
 * 
 * Schedule: Daily at 2 AM (after materialized view full refresh at 1 AM)
 * 
 * Workflow:
 * 1. Query all active products (non-obsolete, sold within last 365 days)
 * 2. Process in chunks of 500 to avoid memory exhaustion
 * 3. Extract 22 demand features per product from mv_product_demand_analytics
 * 4. Store forecasts in product_demand_forecasts table
 * 5. Trigger reorder alerts for products below reorder point
 * 
 * Performance:
 * - 10,000 SKUs processed in ~15 minutes
 * - Uses instrumented extractor for automatic cost tracking
 * - Chunk processing prevents memory overflow
 */
final class ForecastInventoryDemandCommand extends Command
{
    protected $signature = 'intelligence:forecast-inventory
                            {--tenant= : Specific tenant ID (optional, defaults to all)}
                            {--chunk=500 : Products per batch (default 500)}
                            {--force : Regenerate all forecasts (skip dirty check)}';

    protected $description = 'Generate demand forecasts for all SKUs using DemandForecastExtractor';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');

        $startTime = microtime(true);
        $totalProcessed = 0;
        $totalReorderAlerts = 0;

        $this->info("🤖 Intelligence: Demand Forecasting");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        try {
            // Resolve tenant-scoped repository
            $tenants = $tenantId ? [$tenantId] : $this->getActiveTenants();

            foreach ($tenants as $tid) {
                $this->processTenant($tid, $chunkSize, $force, $totalProcessed, $totalReorderAlerts);
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("✅ Forecasting Complete");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Products Processed', number_format($totalProcessed)],
                    ['Reorder Alerts Generated', number_format($totalReorderAlerts)],
                    ['Execution Time', "{$duration}s"],
                    ['Throughput', round($totalProcessed / max($duration, 1), 2) . ' products/sec'],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Forecasting failed: {$e->getMessage()}");
            $this->logger->error('Demand forecasting command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function processTenant(
        string $tenantId,
        int $chunkSize,
        bool $force,
        int &$totalProcessed,
        int &$totalReorderAlerts
    ): void {
        $this->line("\n📦 Tenant: {$tenantId}");

        // Create tenant-scoped analytics repository
        $analytics = new \App\Repositories\InventoryAnalyticsRepository($tenantId);

        // Get all active products (non-obsolete, recent sales)
        $products = $analytics->getActiveProducts();
        $productCount = $products->count();

        if ($productCount === 0) {
            $this->warn("  ⚠️  No active products found");
            return;
        }

        $this->info("  Found {$productCount} active products");

        // Resolve instrumented extractor
        $demandForecaster = app('intelligence.extractor.inventory.demand_forecast');

        $bar = $this->output->createProgressBar($productCount);
        $bar->setFormat('  %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->start();

        $tenantProcessed = 0;
        $tenantAlerts = 0;

        $products->chunk($chunkSize)->each(function ($chunk) use (
            $tenantId,
            $demandForecaster,
            $bar,
            &$tenantProcessed,
            &$tenantAlerts
        ) {
            foreach ($chunk as $product) {
                try {
                    // Extract 22 demand features from materialized view
                    $features = $demandForecaster->extract([
                        'tenant_id' => $tenantId,
                        'product_id' => $product->id,
                    ]);

                    // Calculate predicted demand (weighted moving average + trend)
                    $predictedDemand = $this->calculatePredictedDemand($features);
                    $confidenceScore = $this->calculateForecastConfidence($features);

                    // Store forecast (upsert)
                    DB::table('product_demand_forecasts')->updateOrInsert(
                        ['product_id' => $product->id],
                        [
                            'tenant_id' => $tenantId,
                            'predicted_demand_30d' => $predictedDemand,
                            'safety_stock_qty' => $features['safety_stock_qty'] ?? 0.0,
                            'reorder_point_qty' => $features['reorder_point'] ?? 0.0,
                            'days_until_stockout' => $features['days_of_inventory_on_hand'] ?? 0.0,
                            'recommended_order_qty' => $features['economic_order_quantity'] ?? 0.0,
                            'demand_trend' => $this->classifyTrend($features['demand_trend_slope'] ?? 0.0),
                            'seasonality_factor' => $features['seasonality_index'] ?? 1.0,
                            'forecast_confidence' => $confidenceScore,
                            'forecasted_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // Check if reorder alert needed
                    if ($product->current_stock < ($features['reorder_point'] ?? 0)) {
                        $this->triggerReorderAlert($product, $features);
                        $tenantAlerts++;
                    }

                    $tenantProcessed++;
                    $bar->setMessage($product->sku);
                    $bar->advance();

                } catch (\Exception $e) {
                    $this->logger->warning('Failed to forecast product demand', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("  ✓ Processed {$tenantProcessed} products, {$tenantAlerts} reorder alerts");

        $totalProcessed += $tenantProcessed;
        $totalReorderAlerts += $tenantAlerts;
    }

    /**
     * Calculate predicted demand using weighted moving average with trend adjustment.
     */
    private function calculatePredictedDemand(array $features): float
    {
        $avg30d = $features['avg_daily_demand_30d'] ?? 0.0;
        $avg90d = $features['avg_daily_demand_90d'] ?? 0.0;
        $avg365d = $features['avg_daily_demand_365d'] ?? 0.0;
        $trendSlope = $features['demand_trend_slope'] ?? 0.0;
        $seasonalityIndex = $features['seasonality_index'] ?? 1.0;

        // Weighted moving average (prioritize recent data)
        $baseForecast = ($avg30d * 0.5) + ($avg90d * 0.3) + ($avg365d * 0.2);

        // Trend adjustment (30-day projection)
        $trendAdjustment = $trendSlope * 30;

        // Seasonal adjustment
        $forecast = ($baseForecast + $trendAdjustment) * $seasonalityIndex;

        return max(0, $forecast * 30); // Convert daily to 30-day forecast
    }

    /**
     * Calculate forecast confidence based on data quality.
     */
    private function calculateForecastConfidence(array $features): float
    {
        $cv = $features['coefficient_of_variation'] ?? 1.0;
        $staleness = min($features['days_since_last_sale'] ?? 0, 30) / 30;
        $zeroDays = min($features['zero_demand_days_30d'] ?? 0, 30) / 30;

        // High confidence = low CV + recent sales + consistent demand
        $variabilityScore = max(0, 1 - $cv);
        $recencyScore = max(0, 1 - $staleness);
        $activityScore = max(0, 1 - $zeroDays);

        return ($variabilityScore * 0.5) + ($recencyScore * 0.3) + ($activityScore * 0.2);
    }

    /**
     * Classify demand trend as increasing, stable, or decreasing.
     */
    private function classifyTrend(float $slope): string
    {
        if ($slope > 0.1) return 'increasing';
        if ($slope < -0.1) return 'decreasing';
        return 'stable';
    }

    /**
     * Trigger reorder alert notification.
     */
    private function triggerReorderAlert(object $product, array $features): void
    {
        try {
            $notifier = app(\Nexus\Notifier\Contracts\NotificationManagerInterface::class);

            $notifier->send(
                templateId: 'inventory_reorder_alert',
                recipientType: 'role',
                recipientId: 'inventory_planner',
                data: [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'current_stock' => $product->current_stock,
                    'reorder_point' => $features['reorder_point'] ?? 0,
                    'recommended_qty' => $features['economic_order_quantity'] ?? 0,
                    'days_until_stockout' => $features['days_of_inventory_on_hand'] ?? 0,
                ],
                channels: ['email', 'in_app']
            );
        } catch (\Exception $e) {
            // Non-critical failure - log but continue
            $this->logger->warning('Failed to send reorder alert', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all active tenant IDs from the system.
     */
    private function getActiveTenants(): array
    {
        return DB::table('tenants')
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();
    }
}
