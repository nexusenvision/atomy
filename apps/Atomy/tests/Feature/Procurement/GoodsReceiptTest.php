<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\GoodsReceiptNotFoundException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Nexus\Procurement\Exceptions\InvalidGoodsReceiptDataException;
use App\Models\GoodsReceiptNote;

/**
 * @covers \App\Repositories\DbGoodsReceiptNoteRepository
 * @covers \App\Models\GoodsReceiptNote
 * @covers \App\Models\GoodsReceiptLine
 */
class GoodsReceiptTest extends TestCase
{
    use RefreshDatabase;

    private ProcurementManagerInterface $procurementManager;
    private string $tenantId;
    private string $requesterId;
    private string $approverId;
    private string $poCreatorId;
    private string $receiverId;
    private string $authorizerId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->procurementManager = app(ProcurementManagerInterface::class);
        $this->tenantId = 'tenant_' . bin2hex(random_bytes(8));
        $this->requesterId = 'user_' . bin2hex(random_bytes(8));
        $this->approverId = 'approver_' . bin2hex(random_bytes(8));
        $this->poCreatorId = 'po_creator_' . bin2hex(random_bytes(8));
        $this->receiverId = 'receiver_' . bin2hex(random_bytes(8));
        $this->authorizerId = 'authorizer_' . bin2hex(random_bytes(8));
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::createGoodsReceipt
     */
    public function it_creates_goods_receipt_against_purchase_order(): void
    {
        $po = $this->createTestPurchaseOrder();

        $grnData = [
            'number' => 'GRN-2025-001',
            'received_date' => '2025-11-20',
            'delivery_note_number' => 'DN-12345',
            'lines' => [
                [
                    'po_line_reference' => 'PO-2025-001-L001',
                    'quantity_received' => 5.0,
                    'unit' => 'Each',
                    'notes' => 'All items in good condition',
                ],
            ],
        ];

        $grn = $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->receiverId,
            $grnData
        );

        $this->assertNotNull($grn->getId());
        $this->assertEquals('draft', $grn->getStatus());
        $this->assertEquals('GRN-2025-001', $grn->getNumber());
        $this->assertEquals($this->receiverId, $grn->getReceiverId());
        $this->assertEquals($po->getId(), $grn->getPurchaseOrderId());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::validateGrnQuantitiesAgainstPo
     */
    public function it_prevents_receiving_more_than_po_quantity(): void
    {
        $po = $this->createTestPurchaseOrder(); // PO has 5.0 quantity

        $this->expectException(InvalidGoodsReceiptDataException::class);
        $this->expectExceptionMessage('exceeds PO quantity');

        $grnData = [
            'number' => 'GRN-2025-001',
            'received_date' => '2025-11-20',
            'lines' => [
                [
                    'po_line_reference' => 'PO-2025-001-L001',
                    'quantity_received' => 10.0, // Exceeds PO quantity of 5.0
                    'unit' => 'Each',
                ],
            ],
        ];

        $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->receiverId,
            $grnData
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::createGoodsReceipt
     */
    public function it_prevents_po_creator_from_creating_grn_for_own_po(): void
    {
        $po = $this->createTestPurchaseOrder();

        $this->expectException(UnauthorizedApprovalException::class);
        $this->expectExceptionMessage('cannot create GRN for their own PO');

        $grnData = [
            'number' => 'GRN-2025-001',
            'received_date' => '2025-11-20',
            'lines' => [
                [
                    'po_line_reference' => 'PO-2025-001-L001',
                    'quantity_received' => 5.0,
                    'unit' => 'Each',
                ],
            ],
        ];

        // Try to create GRN with same user as PO creator
        $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->poCreatorId, // Same as PO creator
            $grnData
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::authorizePayment
     */
    public function it_authorizes_payment_for_goods_receipt(): void
    {
        $po = $this->createTestPurchaseOrder();
        
        $grn = $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->receiverId,
            [
                'number' => 'GRN-2025-001',
                'received_date' => '2025-11-20',
                'lines' => [
                    [
                        'po_line_reference' => 'PO-2025-001-L001',
                        'quantity_received' => 5.0,
                        'unit' => 'Each',
                    ],
                ],
            ]
        );

        $authorized = $this->procurementManager->authorizeGrnPayment(
            $grn->getId(),
            $this->authorizerId
        );

        $this->assertEquals('payment_authorized', $authorized->getStatus());
        $this->assertEquals($this->authorizerId, $authorized->getPaymentAuthorizerId());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::authorizePayment
     */
    public function it_prevents_grn_creator_from_authorizing_own_payment(): void
    {
        $po = $this->createTestPurchaseOrder();
        
        $grn = $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->receiverId,
            [
                'number' => 'GRN-2025-001',
                'received_date' => '2025-11-20',
                'lines' => [
                    [
                        'po_line_reference' => 'PO-2025-001-L001',
                        'quantity_received' => 5.0,
                        'unit' => 'Each',
                    ],
                ],
            ]
        );

        $this->expectException(UnauthorizedApprovalException::class);
        $this->expectExceptionMessage('cannot authorize payment for their own GRN');

        // Try to authorize with same user as GRN receiver
        $this->procurementManager->authorizeGrnPayment(
            $grn->getId(),
            $this->receiverId
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::getGoodsReceipt
     */
    public function it_retrieves_goods_receipt_by_id(): void
    {
        $po = $this->createTestPurchaseOrder();
        
        $created = $this->procurementManager->recordGoodsReceipt(
            $this->tenantId,
            $po->getId(),
            $this->receiverId,
            [
                'number' => 'GRN-2025-001',
                'received_date' => '2025-11-20',
                'lines' => [
                    [
                        'po_line_reference' => 'PO-2025-001-L001',
                        'quantity_received' => 5.0,
                        'unit' => 'Each',
                    ],
                ],
            ]
        );

        $retrieved = $this->procurementManager->getGoodsReceipt($created->getId());

        $this->assertEquals($created->getId(), $retrieved->getId());
        $this->assertEquals($created->getNumber(), $retrieved->getNumber());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\GoodsReceiptManager::getGoodsReceipt
     */
    public function it_throws_exception_when_goods_receipt_not_found(): void
    {
        $this->expectException(GoodsReceiptNotFoundException::class);

        $this->procurementManager->getGoodsReceipt('nonexistent_grn_id');
    }

    /**
     * Helper to create a test purchase order.
     */
    private function createTestPurchaseOrder()
    {
        $requisition = $this->procurementManager->createRequisition(
            $this->tenantId,
            $this->requesterId,
            [
                'title' => 'Test Requisition for GRN',
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

        $approved = $this->procurementManager->approveRequisition(
            $requisition->getId(),
            $this->approverId
        );

        return $this->procurementManager->convertRequisitionToPO(
            $this->tenantId,
            $approved->getId(),
            $this->poCreatorId,
            [
                'vendor_id' => 'vendor_001',
                'vendor_name' => 'ABC Supplies Ltd',
                'number' => 'PO-2025-001',
            ]
        );
    }
}
