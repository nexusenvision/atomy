<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Nexus\Procurement\Exceptions\InvalidRequisitionDataException;
use App\Models\Requisition;
use App\Models\RequisitionLine;

/**
 * @covers \App\Repositories\DbRequisitionRepository
 * @covers \App\Models\Requisition
 * @covers \App\Models\RequisitionLine
 */
class RequisitionTest extends TestCase
{
    use RefreshDatabase;

    private ProcurementManagerInterface $procurementManager;
    private string $tenantId;
    private string $requesterId;
    private string $approverId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->procurementManager = app(ProcurementManagerInterface::class);
        $this->tenantId = 'tenant_' . bin2hex(random_bytes(8));
        $this->requesterId = 'user_' . bin2hex(random_bytes(8));
        $this->approverId = 'approver_' . bin2hex(random_bytes(8));
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::createRequisition
     */
    public function it_creates_requisition_with_line_items(): void
    {
        $requisitionData = [
            'description' => 'Monthly office supplies',
            'department' => 'IT',
            'lines' => [
                [
                    'line_number' => 1,
                    'item_code' => 'PAPER-A4',
                    'description' => 'A4 Paper 500 sheets',
                    'quantity' => 10.0,
                    'unit' => 'Box',
                    'estimated_unit_price' => 25.50,
                ],
                [
                    'line_number' => 2,
                    'item_code' => 'PEN-BLUE',
                    'description' => 'Blue Ballpoint Pen',
                    'quantity' => 50.0,
                    'unit' => 'Each',
                    'estimated_unit_price' => 1.25,
                ],
            ],
        ];

        $requisition = $this->procurementManager->createRequisition(
            $this->tenantId,
            $this->requesterId,
            $requisitionData
        );

        $this->assertNotNull($requisition->getId());
        $this->assertEquals('draft', $requisition->getStatus());
        $this->assertEquals($this->tenantId, $requisition->getTenantId());
        $this->assertEquals($this->requesterId, $requisition->getRequesterId());
        $this->assertEquals('Monthly office supplies', $requisition->getDescription());
        
        // Verify total estimate calculation: (10 * 25.50) + (50 * 1.25) = 255 + 62.5 = 317.50
        $this->assertEquals(317.50, $requisition->getTotalEstimate());
        
        // Verify lines persisted
        $requisitionModel = Requisition::find($requisition->getId());
        $this->assertCount(2, $requisitionModel->lines);
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::createRequisition
     */
    public function it_throws_exception_when_creating_requisition_without_lines(): void
    {
        $this->expectException(InvalidRequisitionDataException::class);

        $this->procurementManager->createRequisition(
            $this->tenantId,
            $this->requesterId,
            [
                'title' => 'Empty Requisition',
                'description' => 'No lines',
                'lines' => [],
            ]
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::approveRequisition
     */
    public function it_approves_requisition(): void
    {
        $requisition = $this->createTestRequisition();

        // Submit for approval first
        $this->procurementManager->submitRequisitionForApproval($requisition->getId());

        $approved = $this->procurementManager->approveRequisition(
            $requisition->getId(),
            $this->approverId
        );

        $this->assertEquals('approved', $approved->getStatus());
        $this->assertEquals($this->approverId, $approved->getApproverId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $approved->getApprovedAt());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::approveRequisition
     */
    public function it_prevents_requester_from_approving_own_requisition(): void
    {
        $requisition = $this->createTestRequisition();

        // Submit for approval first
        $this->procurementManager->submitRequisitionForApproval($requisition->getId());

        $this->expectException(UnauthorizedApprovalException::class);
        $this->expectExceptionMessage('requester cannot approve own requisition');

        // Try to approve with same user as requester
        $this->procurementManager->approveRequisition(
            $requisition->getId(),
            $this->requesterId
        );
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::rejectRequisition
     */
    public function it_rejects_requisition(): void
    {
        $requisition = $this->createTestRequisition();

        // Submit for approval first
        $this->procurementManager->submitRequisitionForApproval($requisition->getId());

        $rejected = $this->procurementManager->rejectRequisition(
            $requisition->getId(),
            $this->approverId,
            'Budget exceeded'
        );

        $this->assertEquals('rejected', $rejected->getStatus());
        $this->assertStringContainsString('Budget exceeded', $rejected->getRejectionReason() ?? '');
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::getRequisition
     */
    public function it_retrieves_requisition_by_id(): void
    {
        $created = $this->createTestRequisition();

        $retrieved = $this->procurementManager->getRequisition($created->getId());

        $this->assertEquals($created->getId(), $retrieved->getId());
        $this->assertEquals($created->getDescription(), $retrieved->getDescription());
    }

    /**
     * @test
     * @covers \Nexus\Procurement\Services\RequisitionManager::getRequisition
     */
    public function it_throws_exception_when_requisition_not_found(): void
    {
        $this->expectException(RequisitionNotFoundException::class);

        $this->procurementManager->getRequisition('nonexistent_id');
    }

    /**
     * Helper to create a test requisition.
     */
    private function createTestRequisition()
    {
        return $this->procurementManager->createRequisition(
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
    }
}
