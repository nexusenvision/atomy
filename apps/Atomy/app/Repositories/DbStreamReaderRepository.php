<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventStream;
use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;

/**
 * DbStreamReaderRepository
 *
 * SQL-based implementation of StreamReaderInterface using Eloquent.
 * Provides efficient querying of event streams.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - FUN-EVS-7206: Read event stream by aggregate ID with version range filtering
 * - FUN-EVS-7207: Read event stream by event type
 * - FUN-EVS-7208: Replay event stream to rebuild aggregate state at specific point in time
 */
final readonly class DbStreamReaderRepository implements StreamReaderInterface
{
    public function __construct(
        private string $tenantId
    ) {
    }

    public function readStream(string $aggregateId): array
    {
        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->orderBy('version')
            ->get()
            ->all();
    }

    public function readStreamFromVersion(
        string $aggregateId,
        int $fromVersion,
        ?int $toVersion = null
    ): array {
        $query = EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->where('version', '>=', $fromVersion);

        if ($toVersion !== null) {
            $query->where('version', '<=', $toVersion);
        }

        return $query->orderBy('version')->get()->all();
    }

    public function readStreamUntil(
        string $aggregateId,
        DateTimeImmutable $timestamp
    ): array {
        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->where('occurred_at', '<=', $timestamp->format('Y-m-d H:i:s'))
            ->orderBy('version')
            ->get()
            ->all();
    }

    public function readEventsByType(string $eventType, ?int $limit = null): array
    {
        $query = EventStream::where('event_type', $eventType)
            ->where('tenant_id', $this->tenantId)
            ->orderBy('occurred_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }

    public function readEventsByTypeAndDateRange(
        string $eventType,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        return EventStream::where('event_type', $eventType)
            ->where('tenant_id', $this->tenantId)
            ->whereBetween('occurred_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ])
            ->orderBy('occurred_at')
            ->get()
            ->all();
    }
}
