<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingRepositoryInterface;
use Nexus\Manufacturing\Contracts\RoutingInterface;
use Nexus\Manufacturing\Exceptions\RoutingNotFoundException;
use Nexus\Manufacturing\Exceptions\InvalidRoutingVersionException;
use Nexus\Manufacturing\ValueObjects\Operation;

/**
 * Routing Manager implementation.
 *
 * Manages manufacturing routings lifecycle, versioning, and calculations.
 */
final readonly class RoutingManager implements RoutingManagerInterface
{
    public function __construct(
        private RoutingRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $productId,
        array $operations,
        string $version = '1.0',
        ?\DateTimeImmutable $effectiveFrom = null
    ): RoutingInterface {
        // Sort operations by operation number
        usort($operations, fn (Operation $a, Operation $b) => $a->operationNumber <=> $b->operationNumber);

        return $this->repository->create([
            'productId' => $productId,
            'version' => $version,
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $operations),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): RoutingInterface
    {
        $routing = $this->repository->findById($id);

        if ($routing === null) {
            throw RoutingNotFoundException::withId($id);
        }

        return $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function findByProductId(string $productId, ?\DateTimeImmutable $effectiveDate = null): RoutingInterface
    {
        $routing = $this->repository->findByProductId($productId, $effectiveDate);

        if ($routing === null) {
            if ($effectiveDate !== null) {
                throw RoutingNotFoundException::noEffectiveRouting($productId, $effectiveDate->format('Y-m-d'));
            }
            throw RoutingNotFoundException::withProductId($productId);
        }

        return $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): RoutingInterface
    {
        $routing = $this->findById($id);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($id, 'released');
        }

        // If updating operations, sort them
        if (isset($data['operations'])) {
            usort($data['operations'], fn (array $a, array $b) =>
                ($a['operationNumber'] ?? 0) <=> ($b['operationNumber'] ?? 0)
            );
        }

        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addOperation(string $routingId, Operation $operation): RoutingInterface
    {
        $routing = $this->findById($routingId);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($routingId, 'released');
        }

        $operations = $routing->getOperations();
        $operations[] = $operation;

        // Sort by operation number
        usort($operations, fn (Operation $a, Operation $b) => $a->operationNumber <=> $b->operationNumber);

        return $this->repository->update($routingId, [
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $operations),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeOperation(string $routingId, int $operationNumber): RoutingInterface
    {
        $routing = $this->findById($routingId);

        if ($routing->getStatus() === 'released') {
            throw InvalidRoutingVersionException::cannotModify($routingId, 'released');
        }

        $operations = array_filter(
            $routing->getOperations(),
            fn (Operation $op) => $op->operationNumber !== $operationNumber
        );

        return $this->repository->update($routingId, [
            'operations' => array_map(fn (Operation $op) => $op->toArray(), array_values($operations)),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createVersion(string $routingId, string $newVersion, ?\DateTimeImmutable $effectiveFrom = null): RoutingInterface
    {
        $sourceRouting = $this->findById($routingId);
        $productId = $sourceRouting->getProductId();

        // Check if version already exists
        $existingVersions = $this->repository->findAllVersions($productId);
        foreach ($existingVersions as $existing) {
            if ($existing->getVersion() === $newVersion) {
                throw InvalidRoutingVersionException::versionExists($productId, $newVersion);
            }
        }

        return $this->repository->create([
            'productId' => $productId,
            'version' => $newVersion,
            'operations' => array_map(fn (Operation $op) => $op->toArray(), $sourceRouting->getOperations()),
            'effectiveFrom' => $effectiveFrom?->format('Y-m-d'),
            'status' => 'draft',
            'previousVersionId' => $routingId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function release(string $routingId, \DateTimeImmutable $effectiveFrom): RoutingInterface
    {
        $routing = $this->findById($routingId);

        if (count($routing->getOperations()) === 0) {
            throw InvalidRoutingVersionException::cannotRelease($routingId, 'Routing has no operations');
        }

        return $this->repository->update($routingId, [
            'status' => 'released',
            'effectiveFrom' => $effectiveFrom->format('Y-m-d'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function obsolete(string $routingId, \DateTimeImmutable $effectiveTo): RoutingInterface
    {
        $this->findById($routingId);

        return $this->repository->update($routingId, [
            'status' => 'obsolete',
            'effectiveTo' => $effectiveTo->format('Y-m-d'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateLeadTime(string $productId, float $quantity = 1.0, ?\DateTimeImmutable $effectiveDate = null): float
    {
        $routing = $this->findByProductId($productId, $effectiveDate);
        $effectiveOperations = $this->getEffectiveOperations($routing->getId(), $effectiveDate);

        $totalMinutes = 0.0;
        $previousOverlap = 0.0;

        foreach ($effectiveOperations as $operation) {
            $operationTime = $operation->calculateTotalTime($quantity);

            // Apply overlap from previous operation
            $overlapReduction = ($operationTime * $previousOverlap / 100);
            $adjustedTime = max(0, $operationTime - $overlapReduction);

            $totalMinutes += $adjustedTime;
            $previousOverlap = $operation->overlapPercentage;
        }

        // Convert minutes to hours
        return $totalMinutes / 60;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateCapacityRequirement(string $productId, float $quantity, ?\DateTimeImmutable $effectiveDate = null): array
    {
        $routing = $this->findByProductId($productId, $effectiveDate);
        $effectiveOperations = $this->getEffectiveOperations($routing->getId(), $effectiveDate);

        $requirements = [];

        foreach ($effectiveOperations as $operation) {
            $workCenterId = $operation->workCenterId;
            $hours = $operation->getCapacityTimeHours($quantity);

            if (!isset($requirements[$workCenterId])) {
                $requirements[$workCenterId] = [
                    'workCenterId' => $workCenterId,
                    'totalHours' => 0.0,
                    'setupHours' => 0.0,
                    'runHours' => 0.0,
                    'operations' => [],
                ];
            }

            $requirements[$workCenterId]['totalHours'] += $hours;
            $requirements[$workCenterId]['setupHours'] += $operation->setupTimeMinutes / 60;
            $requirements[$workCenterId]['runHours'] += ($operation->runTimeMinutes * $quantity) / 60;
            $requirements[$workCenterId]['operations'][] = $operation->operationNumber;
        }

        return array_values($requirements);
    }

    /**
     * {@inheritdoc}
     */
    public function getEffectiveOperations(string $routingId, ?\DateTimeImmutable $effectiveDate = null): array
    {
        $routing = $this->findById($routingId);
        $effectiveDate ??= new \DateTimeImmutable();

        return array_filter(
            $routing->getOperations(),
            fn (Operation $operation) => $operation->isEffectiveAt($effectiveDate)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkCenterOperations(string $workCenterId, ?\DateTimeImmutable $effectiveDate = null): array
    {
        return $this->repository->findOperationsByWorkCenter($workCenterId, $effectiveDate);
    }
}
