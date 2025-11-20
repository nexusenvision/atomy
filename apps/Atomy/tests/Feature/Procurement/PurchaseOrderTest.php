<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use Nexus\Procurement\Exceptions\BudgetExceededException;
use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;

/**
 * @covers \App\Repositories\DbPurchaseOrderRepository
 * @covers \App\Models\PurchaseOrder
 * @covers \App\Models\PurchaseOrderLine
 */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private ProcurementManagerInterface $procurementManager;
    private string $tenantId;
    private string $requesterId;
    private string $approverId;
    private string $poCreatorId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->procurementManager = app(ProcurementManagerInterface::class);
        $this->tenantId = 'tenant_' . bin2hex(random_bytes(8));
        $this->requesterId = 'user_' . bin2hex(random_bytes(8));
        $this->approverId = 'approver_' . bin2hex(random_bytes(8));
        $this->poCreatorId = 'po_creator_' . bin2hex(random_bytes(8));
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::createFromRequisition
     */
    public function it_converts_approved_requisition_to_purchase_order(): void
    {
        $requisition = $this->createAndApproveRequisition();

        $poData = [
            'vendor_id' => 'vendor_001',
            'vendor_name' => 'ABC Supplies Ltd',
            'number' => 'PO-2025-001',
            'delivery_address' => '123 Main St',
            'payment_terms' => 'Net 30',
        ];

        $po = $this->procurementManager->convertRequisitionToPO(
            $this->tenantId,
            $requisition->getId(),
            $this->poCreatorId,
            $poData
        );

        $this->assertNotNull($po->getId());
        $this->assertEquals('standard', $po->getPoType());
        $this->assertEquals('draft', $po->getStatus());
        $this->assertEquals('vendor_001', $po->getVendorId());
        $this->assertEquals('PO-2025-001', $po->getNumber());
        
        // Verify PO total matches requisition estimate
        $this->assertEquals($requisition->getTotalEstimate(), $po->getTotalValue());
        
        // Verify line references generated
        $poModel = PurchaseOrder::find($po->getId());
        $this->assertCount(1, $poModel->lines);
        $firstLine = $poModel->lines->first();
        $this->assertStringStartsWith('PO-2025-001-L', $firstLine->line_reference);
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::validatePoAgainstRequisition
     */
    public function it_prevents_po_exceeding_requisition_budget_by_more_than_tolerance(): void
    {
        $requisition = $this->createAndApproveRequisition(); // Estimate: 500.00

        $this->expectException(BudgetExceededException::class);
        $this->expectExceptionMessage('exceeds requisition approved amount');

        $poData = [
            'vendor_id' => 'vendor_001',
            'vendor_name' => 'ABC Supplies Ltd',
            'number' => 'PO-2025-001',
            'lines' => [
                [
                    'line_number' => 1,
                    'item_code' => 'TEST-001',
                    'description' => 'Test Item',
                    'quantity' => 5.0,
                    'unit' => 'Each',
                    'unit_price' => 150.00, // Total: 750.00 (50% over 500.00, exceeds 10% tolerance)
                ],
            ],
        ];

        $this->procurementManager->convertRequisitionToPO(
            $this->tenantId,
            $requisition->getId(),
            $this->poCreatorId,
            $poData
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::createFromRequisition
     */
    public function it_throws_exception_when_converting_non_approved_requisition(): void
    {
        // Create requisition but don't approve it
        $requisition = $this->procurementManager->createRequisition(
            $this->tenantId,
            $this->requesterId,
            [
                'title' => 'Test Requisition',
                'description' => 'Test Description',
                'department' => 'IT',
                'lines' => [
                    [
                        'line_number' => 1,
                        'item_code' => 'TEST-001',
                        'description' => 'Test Item',
                        'quantity' => 5.0,
                        'unit' => 'Each',
                        'estimated_unit_price' => 100.00,
                    ],
                ],
            ]
        );

        $this->expectException(InvalidRequisitionStateException::class);

        $this->procurementManager->convertRequisitionToPO(
            $this->tenantId,
            $requisition->getId(),
            $this->poCreatorId,
            [
                'vendor_id' => 'vendor_001',
                'vendor_name' => 'ABC Supplies Ltd',
                'number' => 'PO-2025-001',
            ]
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::createBlanketPo
     */
    public function it_creates_direct_blanket_purchase_order(): void
    {
        $poData = [
            'number' => 'PO-2025-100',
            'vendor_id' => 'vendor_002',
            'vendor_name' => 'XYZ Corporation',
            'po_type' => 'blanket',
            'total_committed_value' => 10000.00,
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'lines' => [
                [
                    'line_number' => 1,
                    'item_code' => 'BLANKET-001',
                    'description' => 'Blanket item',
                    'quantity' => 100.0,
                    'unit' => 'Each',
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $po = $this->procurementManager->createDirectPO(
            $this->tenantId,
            $this->poCreatorId,
            $poData
        );

        $this->assertNotNull($po->getId());
        $this->assertEquals('blanket', $po->getPoType());
        $this->assertEquals(10000.00, $po->getTotalCommittedValue());
        $this->assertEquals(0.0, $po->getTotalReleasedValue());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::getPurchaseOrder
     */
    public function it_retrieves_purchase_order_by_id(): void
    {
        $requisition = $this->createAndApproveRequisition();
        
        $po = $this->procurementManager->convertRequisitionToPO(
            $this->tenantId,
            $requisition->getId(),
            $this->poCreatorId,
            [
                'vendor_id' => 'vendor_001',
                'vendor_name' => 'ABC Supplies Ltd',
                'number' => 'PO-2025-001',
            ]
        );

        $retrieved = $this->procurementManager->getPurchaseOrder($po->getId());

        $this->assertEquals($po->getId(), $retrieved->getId());
        $this->assertEquals($po->getNumber(), $retrieved->getNumber());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\PurchaseOrderManager::getPurchaseOrder
     */
    public function it_throws_exception_when_purchase_order_not_found(): void
    {
        $this->expectException(PurchaseOrderNotFoundException::class);

        $this->procurementManager->getPurchaseOrder('nonexistent_po_id');
    }

    /**
     * Helper to create and approve a requisition.
     */
    private function createAndApproveRequisition()
    {
        $requisition = $this->procurementManager->createRequisition(
            $this->tenantId,
            $this->requesterId,
            [
                'title' => 'Test Requisition for PO',
                'description' => 'Test Description',
                'department' => 'IT',
                'lines' => [
                    [
                        'line_number' => 1,
                        'item_code' => 'TEST-001',
                        'description' => 'Test Item',
                        'quantity' => 5.0,
                        'unit' => 'Each',
                        'estimated_unit_price' => 100.00,
                    ],
                ],
            ]
        );

        // Submit for approval first
        $this->procurementManager->submitRequisitionForApproval($requisition->getId());

        return $this->procurementManager->approveRequisition(
            $requisition->getId(),
            $this->approverId
        );
    }
}
