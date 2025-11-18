<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventSnapshot;
use DateTimeImmutable;
use Nexus\EventStream\Contracts\SnapshotInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;

/**
 * DbSnapshotRepository
 *
 * SQL-based implementation of SnapshotRepositoryInterface using Eloquent.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - FUN-EVS-7204: Store/retrieve aggregate snapshots
 * - FUN-EVS-7210: Restore aggregate from latest snapshot + subsequent events
 */
final readonly class DbSnapshotRepository implements SnapshotRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {
    }

    public function save(string $aggregateId, int $version, array $state): void
    {
        $checksum = hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));

        EventSnapshot::create([
            'aggregate_id' => $aggregateId,
            'aggregate_type' => $this->extractAggregateType($aggregateId),
            'version' => $version,
            'state' => $state,
            'checksum' => $checksum,
            'tenant_id' => $this->tenantId,
        ]);
    }

    public function getLatest(string $aggregateId): ?SnapshotInterface
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->orderByDesc('version')
            ->first();
    }

    public function getAtVersion(string $aggregateId, int $version): ?SnapshotInterface
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->where('version', '<=', $version)
            ->orderByDesc('version')
            ->first();
    }

    public function deleteOlderThan(DateTimeImmutable $before): int
    {
        return EventSnapshot::where('tenant_id', $this->tenantId)
            ->where('created_at', '<', $before->format('Y-m-d H:i:s'))
            ->delete();
    }

    public function exists(string $aggregateId): bool
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->exists();
    }

    /**
     * Extract aggregate type from aggregate ID
     *
     * @param string $aggregateId
     * @return string
     */
    private function extractAggregateType(string $aggregateId): string
    {
        $parts = explode('-', $aggregateId, 2);
        return $parts[0] ?? 'unknown';
    }
}
