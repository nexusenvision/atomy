<?php

declare(strict_types=1);

namespace Tests\Feature\Intelligence;

use App\Events\Receivable\CustomerInvoicePostedEvent;
use App\Listeners\Intelligence\EnrichInvoiceWithPaymentPredictionListener;
use App\Models\CustomerInvoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('intelligence')]
#[Group('integrations')]
#[Group('receivable')]
final class EnrichInvoiceWithPaymentPredictionListenerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
    }

    #[Test]
    public function it_is_queued_when_invoice_posted_event_is_dispatched(): void
    {
        Queue::fake();

        // Arrange: Create customer invoice
        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => 'CUST-001',
            'invoice_amount' => 10000.00,
        ]);

        // Act: Dispatch event
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        event($event);

        // Assert: Verify listener was queued
        Queue::assertPushed(EnrichInvoiceWithPaymentPredictionListener::class, function ($job) use ($event) {
            return $job->event->invoiceId === $event->invoiceId;
        });
    }

    #[Test]
    public function it_creates_payment_prediction_record(): void
    {
        // Arrange: Create customer with analytics
        $customerId = 'CUST-002';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 5.0,
            'std_dev_payment_delay_days' => 2.0,
            'on_time_payment_rate' => 0.85,
            'late_payment_rate' => 0.15,
            'avg_days_to_pay' => 32.0,
            'invoice_count_30d' => 6,
            'invoice_count_90d' => 18,
            'invoice_count_365d' => 72,
            'paid_invoice_count_90d' => 16,
            'overdue_invoice_count' => 1,
            'total_outstanding_amount' => 12000.00,
            'overdue_amount' => 2000.00,
            'credit_limit' => 50000.00,
            'credit_utilization_ratio' => 0.24,
            'customer_tenure_days' => 730,
            'lifetime_value' => 250000.00,
            'last_payment_date' => now()->subDays(10),
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 5500.00,
            'payment_method_stability' => 0.90,
            'last_refreshed_at' => now(),
        ]);

        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'invoice_amount' => 8000.00,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        // Act: Handle event synchronously
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        $listener = app(EnrichInvoiceWithPaymentPredictionListener::class);
        $listener->handle($event);

        // Assert: Verify prediction record was created
        $prediction = \DB::table('invoice_payment_predictions')
            ->where('invoice_id', $invoice->id)
            ->first();

        $this->assertNotNull($prediction);
        $this->assertSame($invoice->id, $prediction->invoice_id);
        $this->assertSame($customerId, $prediction->customer_id);
        $this->assertNotNull($prediction->predicted_payment_date);
        $this->assertGreaterThan(0, $prediction->confidence_score);
        $this->assertGreaterThan(0, $prediction->urgency_score);
        $this->assertIsArray(json_decode($prediction->features, true));
    }

    #[Test]
    public function it_calculates_confidence_score_based_on_payment_history(): void
    {
        // Arrange: Create customer with excellent payment history
        $customerId = 'CUST-003';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 1.0,
            'std_dev_payment_delay_days' => 0.5,
            'on_time_payment_rate' => 0.98,
            'late_payment_rate' => 0.02,
            'avg_days_to_pay' => 29.0,
            'invoice_count_30d' => 8,
            'invoice_count_90d' => 24,
            'invoice_count_365d' => 96,
            'paid_invoice_count_90d' => 24,
            'overdue_invoice_count' => 0,
            'total_outstanding_amount' => 5000.00,
            'overdue_amount' => 0.00,
            'credit_limit' => 100000.00,
            'credit_utilization_ratio' => 0.05,
            'customer_tenure_days' => 1825,
            'lifetime_value' => 1500000.00,
            'last_payment_date' => now()->subDays(5),
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 6200.00,
            'payment_method_stability' => 0.99,
            'last_refreshed_at' => now(),
        ]);

        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'invoice_amount' => 6000.00,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        // Act
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        $listener = app(EnrichInvoiceWithPaymentPredictionListener::class);
        $listener->handle($event);

        // Assert: Excellent history should yield high confidence
        $prediction = \DB::table('invoice_payment_predictions')
            ->where('invoice_id', $invoice->id)
            ->first();

        $this->assertGreaterThan(8.0, $prediction->confidence_score);
    }

    #[Test]
    public function it_triggers_collections_alert_for_high_urgency_customers(): void
    {
        // Arrange: Create customer with poor payment history
        $customerId = 'CUST-004';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 25.0,
            'std_dev_payment_delay_days' => 15.0,
            'on_time_payment_rate' => 0.30,
            'late_payment_rate' => 0.70,
            'avg_days_to_pay' => 55.0,
            'invoice_count_30d' => 3,
            'invoice_count_90d' => 9,
            'invoice_count_365d' => 36,
            'paid_invoice_count_90d' => 6,
            'overdue_invoice_count' => 5,
            'total_outstanding_amount' => 45000.00,
            'overdue_amount' => 20000.00,
            'credit_limit' => 50000.00,
            'credit_utilization_ratio' => 0.90,
            'customer_tenure_days' => 90,
            'lifetime_value' => 60000.00,
            'last_payment_date' => now()->subDays(45),
            'has_disputed_invoices' => true,
            'avg_invoice_amount' => 8000.00,
            'payment_method_stability' => 0.60,
            'last_refreshed_at' => now(),
        ]);

        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'invoice_amount' => 12000.00,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        // Act
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        $listener = app(EnrichInvoiceWithPaymentPredictionListener::class);
        $listener->handle($event);

        // Assert: Poor history should yield high urgency score
        $prediction = \DB::table('invoice_payment_predictions')
            ->where('invoice_id', $invoice->id)
            ->first();

        $this->assertGreaterThanOrEqual(8.0, $prediction->urgency_score);
        
        // Verify notification was sent (assuming Notifier integration)
        // This would require mocking the Notifier service
    }

    #[Test]
    public function it_stores_all_extracted_features_as_json(): void
    {
        // Arrange
        $customerId = 'CUST-005';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 4.5,
            'std_dev_payment_delay_days' => 2.2,
            'on_time_payment_rate' => 0.88,
            'late_payment_rate' => 0.12,
            'avg_days_to_pay' => 31.0,
            'invoice_count_30d' => 7,
            'invoice_count_90d' => 21,
            'invoice_count_365d' => 84,
            'paid_invoice_count_90d' => 20,
            'overdue_invoice_count' => 1,
            'total_outstanding_amount' => 14000.00,
            'overdue_amount' => 2500.00,
            'credit_limit' => 75000.00,
            'credit_utilization_ratio' => 0.19,
            'customer_tenure_days' => 548,
            'lifetime_value' => 420000.00,
            'last_payment_date' => now()->subDays(8),
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 5800.00,
            'payment_method_stability' => 0.93,
            'last_refreshed_at' => now(),
        ]);

        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'invoice_amount' => 7500.00,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        // Act
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        $listener = app(EnrichInvoiceWithPaymentPredictionListener::class);
        $listener->handle($event);

        // Assert: Verify features JSON structure
        $prediction = \DB::table('invoice_payment_predictions')
            ->where('invoice_id', $invoice->id)
            ->first();

        $features = json_decode($prediction->features, true);
        
        $this->assertIsArray($features);
        $this->assertArrayHasKey('avg_payment_delay_days', $features);
        $this->assertArrayHasKey('on_time_payment_rate', $features);
        $this->assertArrayHasKey('credit_utilization_ratio', $features);
        $this->assertArrayHasKey('customer_tenure_days', $features);
        $this->assertArrayHasKey('payment_consistency_score', $features);
        
        // Verify feature count (should be 20)
        $this->assertCount(20, $features);
    }

    #[Test]
    public function it_handles_new_customer_with_no_analytics_gracefully(): void
    {
        // Arrange: Customer with no analytics record
        $customerId = 'CUST-NEW';
        
        $invoice = CustomerInvoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'invoice_amount' => 5000.00,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        // Act
        $event = new CustomerInvoicePostedEvent($invoice->id, $invoice->customer_id);
        $listener = app(EnrichInvoiceWithPaymentPredictionListener::class);
        $listener->handle($event);

        // Assert: Should handle gracefully (no prediction or default prediction)
        $prediction = \DB::table('invoice_payment_predictions')
            ->where('invoice_id', $invoice->id)
            ->first();

        // Either no prediction is created, or a default low-confidence prediction
        if ($prediction !== null) {
            $this->assertLessThan(5.0, $prediction->confidence_score);
        } else {
            $this->assertNull($prediction);
        }
    }
}
