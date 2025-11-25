<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\DemandForecastInterface;
use Nexus\Manufacturing\Contracts\ForecastProviderInterface;
use Nexus\Manufacturing\Contracts\ForecastFallbackInterface;
use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\Exceptions\ForecastUnavailableException;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Psr\Log\LoggerInterface;

/**
 * Demand Forecaster implementation.
 *
 * Integrates with Nexus\MachineLearning for AI-powered demand forecasting
 * with graceful fallback to historical data when ML is unavailable.
 */
final readonly class DemandForecaster implements DemandForecastInterface
{
    public function __construct(
        private ?ForecastProviderInterface $mlProvider = null,
        private ?ForecastFallbackInterface $fallbackProvider = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function forecast(string $productId, PlanningHorizon $horizon): DemandForecast
    {
        // Try ML provider first
        if ($this->mlProvider !== null) {
            try {
                $forecast = $this->mlProvider->generateForecast($productId, $horizon);

                $this->logger?->info('ML forecast generated', [
                    'productId' => $productId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->warning('ML forecast failed, falling back', [
                    'productId' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fall back to historical data
        if ($this->fallbackProvider !== null) {
            try {
                $forecast = $this->fallbackProvider->generateFromHistory($productId, $horizon);

                $this->logger?->info('Historical fallback forecast generated', [
                    'productId' => $productId,
                    'horizon' => $horizon->getTotalDays() . ' days',
                    'confidence' => $forecast->confidence->value,
                ]);

                // Publish event to notify that fallback was used
                // In real implementation, this would dispatch ForecastFallbackUsedEvent

                return $forecast;
            } catch (\Exception $e) {
                $this->logger?->error('Fallback forecast also failed', [
                    'productId' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw ForecastUnavailableException::noProviderAvailable($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function forecastMultiple(array $productIds, PlanningHorizon $horizon): array
    {
        $forecasts = [];
        $failed = [];

        foreach ($productIds as $productId) {
            try {
                $forecasts[$productId] = $this->forecast($productId, $horizon);
            } catch (\Exception $e) {
                $failed[$productId] = $e->getMessage();
            }
        }

        if (!empty($failed)) {
            $this->logger?->warning('Some forecasts failed', [
                'totalRequested' => count($productIds),
                'succeeded' => count($forecasts),
                'failed' => count($failed),
                'failedProducts' => array_keys($failed),
            ]);
        }

        return $forecasts;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfidenceLevel(string $productId): ForecastConfidence
    {
        // Determine confidence based on data availability
        $hasMLProvider = $this->mlProvider !== null;
        $hasFallback = $this->fallbackProvider !== null;

        if (!$hasMLProvider && !$hasFallback) {
            return ForecastConfidence::VERY_LOW;
        }

        if ($hasMLProvider) {
            try {
                return $this->mlProvider->getModelConfidence($productId);
            } catch (\Exception) {
                // ML provider can't assess confidence
            }
        }

        if ($hasFallback) {
            try {
                return $this->fallbackProvider->getHistoricalConfidence($productId);
            } catch (\Exception) {
                // Fallback can't assess confidence
            }
        }

        return ForecastConfidence::LOW;
    }

    /**
     * {@inheritdoc}
     */
    public function isMLAvailable(): bool
    {
        if ($this->mlProvider === null) {
            return false;
        }

        try {
            return $this->mlProvider->isHealthy();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoricalDemand(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        if ($this->fallbackProvider === null) {
            return [];
        }

        return $this->fallbackProvider->getHistoricalData($productId, $startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSeasonalFactors(string $productId): array
    {
        if ($this->fallbackProvider === null) {
            return array_fill(1, 12, 1.0); // No seasonality
        }

        return $this->fallbackProvider->calculateSeasonality($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function adjustForecast(DemandForecast $forecast, array $adjustments): DemandForecast
    {
        $adjustedQuantities = $forecast->periodQuantities;
        $notes = $forecast->notes ?? [];
        $notes[] = 'Manually adjusted';

        foreach ($adjustments as $period => $adjustment) {
            if (isset($adjustedQuantities[$period])) {
                $originalQty = $adjustedQuantities[$period];

                if (isset($adjustment['quantity'])) {
                    $adjustedQuantities[$period] = $adjustment['quantity'];
                } elseif (isset($adjustment['multiplier'])) {
                    $adjustedQuantities[$period] *= $adjustment['multiplier'];
                } elseif (isset($adjustment['delta'])) {
                    $adjustedQuantities[$period] += $adjustment['delta'];
                }

                $notes[] = "Period {$period}: adjusted from {$originalQty} to {$adjustedQuantities[$period]}";
            }
        }

        // Downgrade confidence after manual adjustment
        $newConfidence = match ($forecast->confidence) {
            ForecastConfidence::VERY_HIGH => ForecastConfidence::HIGH,
            ForecastConfidence::HIGH => ForecastConfidence::MEDIUM,
            ForecastConfidence::MEDIUM => ForecastConfidence::LOW,
            default => ForecastConfidence::VERY_LOW,
        };

        return new DemandForecast(
            productId: $forecast->productId,
            horizon: $forecast->horizon,
            totalQuantity: array_sum($adjustedQuantities),
            periodQuantities: $adjustedQuantities,
            confidence: $newConfidence,
            source: 'manual_adjustment',
            modelVersion: $forecast->modelVersion,
            generatedAt: new \DateTimeImmutable(),
            seasonalFactors: $forecast->seasonalFactors,
            trendIndicator: $forecast->trendIndicator,
            notes: $notes,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compareForecastToActual(
        string $productId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array {
        if ($this->fallbackProvider === null) {
            return [];
        }

        // Get historical forecasts for the period
        // This would require storing historical forecasts
        $historicalForecasts = [];

        // Get actual demand from history
        $actualDemand = $this->fallbackProvider->getHistoricalData($productId, $periodStart, $periodEnd);

        // Calculate accuracy metrics
        $totalForecast = 0.0;
        $totalActual = array_sum(array_column($actualDemand, 'quantity'));

        foreach ($historicalForecasts as $forecast) {
            $totalForecast += $forecast['quantity'];
        }

        $mape = $totalActual > 0
            ? abs($totalForecast - $totalActual) / $totalActual * 100
            : 0;

        $bias = $totalForecast - $totalActual;

        return [
            'productId' => $productId,
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
            'forecastedQuantity' => $totalForecast,
            'actualQuantity' => $totalActual,
            'variance' => $bias,
            'variancePercentage' => $totalActual > 0 ? ($bias / $totalActual) * 100 : 0,
            'mape' => $mape, // Mean Absolute Percentage Error
            'accuracy' => 100 - $mape,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAlgorithms(): array
    {
        $algorithms = [];

        if ($this->mlProvider !== null) {
            $algorithms = array_merge($algorithms, $this->mlProvider->getSupportedAlgorithms());
        }

        if ($this->fallbackProvider !== null) {
            $algorithms[] = 'historical_average';
            $algorithms[] = 'seasonal_decomposition';
            $algorithms[] = 'exponential_smoothing';
        }

        return array_unique($algorithms);
    }
}
