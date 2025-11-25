<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\ChangeOrderManagerInterface;
use Nexus\Manufacturing\Contracts\ChangeOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\ChangeOrderInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Exceptions\ChangeOrderNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Change Order Manager implementation.
 *
 * Manages engineering change orders (ECOs) for BOMs and Routings.
 */
final readonly class ChangeOrderManager implements ChangeOrderManagerInterface
{
    public function __construct(
        private ChangeOrderRepositoryInterface $repository,
        private BomManagerInterface $bomManager,
        private RoutingManagerInterface $routingManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): ChangeOrderInterface
    {
        $this->validateChangeOrderData($data);

        $changeOrder = $this->repository->create($data);

        $this->logger?->info('Change order created', [
            'id' => $changeOrder->getId(),
            'number' => $changeOrder->getNumber(),
            'type' => $data['type'] ?? 'general',
        ]);

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ChangeOrderInterface
    {
        $changeOrder = $this->repository->findById($id);

        if ($changeOrder === null) {
            throw ChangeOrderNotFoundException::withId($id);
        }

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function findByNumber(string $number): ChangeOrderInterface
    {
        $changeOrder = $this->repository->findByNumber($number);

        if ($changeOrder === null) {
            throw ChangeOrderNotFoundException::withNumber($number);
        }

        return $changeOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        // Cannot update approved or implemented change orders
        if (in_array($changeOrder->getStatus(), ['approved', 'implemented'], true)) {
            throw new \RuntimeException("Cannot update change order in status: {$changeOrder->getStatus()}");
        }

        $updated = $this->repository->update($id, $data);

        $this->logger?->info('Change order updated', [
            'id' => $id,
            'changes' => array_keys($data),
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $id): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Only draft change orders can be submitted');
        }

        $updated = $this->repository->update($id, [
            'status' => 'pending_approval',
            'submittedAt' => new \DateTimeImmutable(),
        ]);

        $this->logger?->info('Change order submitted for approval', [
            'id' => $id,
            'number' => $changeOrder->getNumber(),
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function approve(string $id, string $approverId, ?string $comments = null): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'pending_approval') {
            throw new \RuntimeException('Only pending change orders can be approved');
        }

        $updated = $this->repository->update($id, [
            'status' => 'approved',
            'approvedAt' => new \DateTimeImmutable(),
            'approvedBy' => $approverId,
            'approvalComments' => $comments,
        ]);

        $this->logger?->info('Change order approved', [
            'id' => $id,
            'approvedBy' => $approverId,
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function reject(string $id, string $rejectorId, string $reason): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'pending_approval') {
            throw new \RuntimeException('Only pending change orders can be rejected');
        }

        $updated = $this->repository->update($id, [
            'status' => 'rejected',
            'rejectedAt' => new \DateTimeImmutable(),
            'rejectedBy' => $rejectorId,
            'rejectionReason' => $reason,
        ]);

        $this->logger?->info('Change order rejected', [
            'id' => $id,
            'rejectedBy' => $rejectorId,
            'reason' => $reason,
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function implement(string $id): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'approved') {
            throw new \RuntimeException('Only approved change orders can be implemented');
        }

        // Apply changes to BOMs and/or Routings
        $this->applyChanges($changeOrder);

        $updated = $this->repository->update($id, [
            'status' => 'implemented',
            'implementedAt' => new \DateTimeImmutable(),
        ]);

        $this->logger?->info('Change order implemented', [
            'id' => $id,
            'number' => $changeOrder->getNumber(),
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $id, string $reason): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if (in_array($changeOrder->getStatus(), ['implemented', 'cancelled'], true)) {
            throw new \RuntimeException("Cannot cancel change order in status: {$changeOrder->getStatus()}");
        }

        $updated = $this->repository->update($id, [
            'status' => 'cancelled',
            'cancelledAt' => new \DateTimeImmutable(),
            'cancellationReason' => $reason,
        ]);

        $this->logger?->info('Change order cancelled', [
            'id' => $id,
            'reason' => $reason,
        ]);

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function addBomChange(string $id, array $bomChange): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Can only add changes to draft change orders');
        }

        $existingChanges = $changeOrder->getBomChanges();
        $existingChanges[] = $this->validateBomChange($bomChange);

        return $this->repository->update($id, ['bomChanges' => $existingChanges]);
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutingChange(string $id, array $routingChange): ChangeOrderInterface
    {
        $changeOrder = $this->findById($id);

        if ($changeOrder->getStatus() !== 'draft') {
            throw new \RuntimeException('Can only add changes to draft change orders');
        }

        $existingChanges = $changeOrder->getRoutingChanges();
        $existingChanges[] = $this->validateRoutingChange($routingChange);

        return $this->repository->update($id, ['routingChanges' => $existingChanges]);
    }

    /**
     * {@inheritdoc}
     */
    public function findPending(): array
    {
        return $this->repository->findByStatus('pending_approval');
    }

    /**
     * {@inheritdoc}
     */
    public function findByProduct(string $productId): array
    {
        return $this->repository->findByProduct($productId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->repository->findByDateRange($startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getImpactAnalysis(string $id): array
    {
        $changeOrder = $this->findById($id);
        $impact = [
            'affectedBoms' => [],
            'affectedRoutings' => [],
            'affectedWorkOrders' => [],
            'costImpact' => 0.0,
            'timeImpact' => 0.0,
        ];

        // Analyze BOM changes
        foreach ($changeOrder->getBomChanges() as $bomChange) {
            $bomId = $bomChange['bomId'] ?? null;
            if ($bomId) {
                try {
                    $bom = $this->bomManager->findById($bomId);
                    $impact['affectedBoms'][] = [
                        'bomId' => $bomId,
                        'productId' => $bom->getProductId(),
                        'version' => $bom->getVersion(),
                        'changeType' => $bomChange['changeType'] ?? 'modify',
                    ];
                } catch (\Exception) {
                    // BOM not found
                }
            }
        }

        // Analyze Routing changes
        foreach ($changeOrder->getRoutingChanges() as $routingChange) {
            $routingId = $routingChange['routingId'] ?? null;
            if ($routingId) {
                try {
                    $routing = $this->routingManager->findById($routingId);
                    $impact['affectedRoutings'][] = [
                        'routingId' => $routingId,
                        'productId' => $routing->getProductId(),
                        'version' => $routing->getVersion(),
                        'changeType' => $routingChange['changeType'] ?? 'modify',
                    ];
                } catch (\Exception) {
                    // Routing not found
                }
            }
        }

        return $impact;
    }

    /**
     * Apply changes from a change order to BOMs and Routings.
     */
    private function applyChanges(ChangeOrderInterface $changeOrder): void
    {
        $effectiveDate = $changeOrder->getEffectiveDate() ?? new \DateTimeImmutable();

        // Apply BOM changes
        foreach ($changeOrder->getBomChanges() as $bomChange) {
            $this->applyBomChange($bomChange, $effectiveDate);
        }

        // Apply Routing changes
        foreach ($changeOrder->getRoutingChanges() as $routingChange) {
            $this->applyRoutingChange($routingChange, $effectiveDate);
        }
    }

    /**
     * Apply a single BOM change.
     */
    private function applyBomChange(array $change, \DateTimeImmutable $effectiveDate): void
    {
        $changeType = $change['changeType'] ?? 'modify';
        $bomId = $change['bomId'] ?? null;

        if (!$bomId) {
            return;
        }

        match ($changeType) {
            'add_line' => $this->bomManager->addLine($bomId, $change['line']),
            'remove_line' => $this->bomManager->removeLine($bomId, $change['lineNumber']),
            'modify_line' => $this->bomManager->updateLine(
                $bomId,
                $change['lineNumber'],
                $change['lineData']
            ),
            'new_version' => $this->bomManager->createNewVersion($bomId, [
                'effectiveFrom' => $effectiveDate,
            ]),
            default => null,
        };
    }

    /**
     * Apply a single Routing change.
     */
    private function applyRoutingChange(array $change, \DateTimeImmutable $effectiveDate): void
    {
        $changeType = $change['changeType'] ?? 'modify';
        $routingId = $change['routingId'] ?? null;

        if (!$routingId) {
            return;
        }

        match ($changeType) {
            'add_operation' => $this->routingManager->addOperation($routingId, $change['operation']),
            'remove_operation' => $this->routingManager->removeOperation(
                $routingId,
                $change['operationNumber']
            ),
            'modify_operation' => $this->routingManager->updateOperation(
                $routingId,
                $change['operationNumber'],
                $change['operationData']
            ),
            'new_version' => $this->routingManager->createNewVersion($routingId, [
                'effectiveFrom' => $effectiveDate,
            ]),
            default => null,
        };
    }

    /**
     * Validate change order data.
     */
    private function validateChangeOrderData(array $data): void
    {
        $required = ['number', 'description', 'productId'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required for change order");
            }
        }
    }

    /**
     * Validate BOM change data.
     */
    private function validateBomChange(array $change): array
    {
        if (empty($change['bomId']) && empty($change['productId'])) {
            throw new \InvalidArgumentException('BOM change must have bomId or productId');
        }

        if (empty($change['changeType'])) {
            throw new \InvalidArgumentException('BOM change must specify changeType');
        }

        return $change;
    }

    /**
     * Validate Routing change data.
     */
    private function validateRoutingChange(array $change): array
    {
        if (empty($change['routingId']) && empty($change['productId'])) {
            throw new \InvalidArgumentException('Routing change must have routingId or productId');
        }

        if (empty($change['changeType'])) {
            throw new \InvalidArgumentException('Routing change must specify changeType');
        }

        return $change;
    }
}
