<?php

declare(strict_types=1);

namespace App\Listeners\Intelligence;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\SeverityEvaluatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Async listener that enriches invoices with payment prediction after posting.
 * 
 * Workflow:
 * 1. CustomerInvoicePostedEvent dispatched (sync)
 * 2. Listener enqueued to background job (async)
 * 3. Extract payment prediction features from materialized view
 * 4. Store predictions in invoice_payment_predictions table
 * 5. Dashboard displays urgency scores and predicted payment dates
 * 
 * Non-blocking design ensures invoice posting is never delayed by AI processing.
 */
final class EnrichInvoiceWithPaymentPredictionListener implements ShouldQueue
{
    public string $queue = 'intelligence';
    public int $tries = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle the event.
     * 
     * @param object $event CustomerInvoicePostedEvent (from Nexus\Receivable)
     */
    public function handle(object $event): void
    {
        $invoice = $event->invoice;
        $invoiceId = method_exists($invoice, 'getId') ? $invoice->getId() : $invoice->id;
        $customerId = method_exists($invoice, 'getCustomerId') ? $invoice->getCustomerId() : $invoice->customer_id;
        $totalAmount = method_exists($invoice, 'getTotalAmount') 
            ? $invoice->getTotalAmount()->getAmount() 
            : $invoice->total_amount;
        $dueDate = method_exists($invoice, 'getDueDate') 
            ? $invoice->getDueDate()->format('Y-m-d') 
            : $invoice->due_date;

        try {
            // Resolve extractor from container (instrumented with cost tracking)
            $paymentPredictor = app('intelligence.extractor.receivable.payment_prediction');
            
            // Extract features from materialized view analytics
            $features = $paymentPredictor->extract([
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'invoice_amount' => $totalAmount,
                'due_date' => $dueDate,
            ]);

            // Resolve severity evaluator (determines urgency level)
            $evaluator = app(SeverityEvaluatorInterface::class);
            $severity = $evaluator->evaluate($features);

            // Store predictions for dashboard consumption (upsert)
            DB::table('invoice_payment_predictions')->updateOrInsert(
                ['invoice_id' => $invoiceId],
                [
                    'customer_id' => $customerId,
                    'predicted_payment_date' => $features['predicted_payment_date'] ?? null,
                    'payment_urgency_score' => $features['payment_urgency_score'] ?? 0.0,
                    'collection_difficulty' => $features['collection_difficulty_estimate'] ?? 0.0,
                    'predicted_days_late' => $features['predicted_days_late'] ?? 0,
                    'confidence_score' => $this->calculateConfidence($features),
                    'severity' => $severity->value,
                    'extracted_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->logger->info('Invoice payment prediction completed', [
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'urgency_score' => $features['payment_urgency_score'] ?? 0.0,
                'severity' => $severity->value,
                'feature_count' => count($features),
            ]);

            // Trigger high-urgency alert if needed
            if (($features['payment_urgency_score'] ?? 0) >= 8.0) {
                $this->triggerCollectionsAlert($invoiceId, $customerId, $features);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to enrich invoice with payment prediction', [
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Calculate prediction confidence based on data quality.
     * 
     * High confidence requires:
     * - Low payment delay variability (std_dev < 5 days)
     * - Sufficient history (tenure > 90 days)
     * - Recent activity (invoice_count_12m > 3)
     */
    private function calculateConfidence(array $features): float
    {
        $stdDev = $features['payment_delay_std_dev'] ?? 99.0;
        $tenure = $features['customer_tenure_days'] ?? 0;
        $invoiceCount = $features['invoice_count_12m'] ?? 0;

        $variabilityScore = max(0, 1 - ($stdDev / 10)); // Penalize high variance
        $tenureScore = min(1, $tenure / 180); // Max confidence at 6 months tenure
        $activityScore = min(1, $invoiceCount / 6); // Max confidence at 6 invoices/year

        return ($variabilityScore * 0.5) + ($tenureScore * 0.3) + ($activityScore * 0.2);
    }

    /**
     * Trigger collections alert for high-urgency invoices.
     * 
     * Integrates with Nexus\Notifier to send notifications to collections team.
     */
    private function triggerCollectionsAlert(string $invoiceId, string $customerId, array $features): void
    {
        try {
            $notifier = app(\Nexus\Notifier\Contracts\NotificationManagerInterface::class);
            
            $notifier->send(
                templateId: 'collections_high_urgency_invoice',
                recipientType: 'role',
                recipientId: 'collections_manager',
                data: [
                    'invoice_id' => $invoiceId,
                    'customer_id' => $customerId,
                    'urgency_score' => $features['payment_urgency_score'],
                    'predicted_days_late' => $features['predicted_days_late'],
                    'collection_difficulty' => $features['collection_difficulty_estimate'],
                ],
                channels: ['email', 'in_app']
            );

            $this->logger->info('Collections alert triggered for high-urgency invoice', [
                'invoice_id' => $invoiceId,
                'urgency_score' => $features['payment_urgency_score'],
            ]);
        } catch (\Exception $e) {
            // Non-critical failure - log but don't fail the job
            $this->logger->warning('Failed to send collections alert', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if the job should be retried after failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logger->error('Invoice payment prediction job failed permanently', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }
}
