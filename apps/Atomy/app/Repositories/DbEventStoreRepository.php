<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventStream;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\EventStreamException;
use Illuminate\Support\Facades\DB;

/**
 * DbEventStoreRepository
 *
 * SQL-based implementation of EventStoreInterface using Eloquent.
 * Provides ACID compliance for event appending.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - ARC-EVS-7008: Support multiple event store backends via adapter pattern
 * - BUS-EVS-7105: Event streams MUST support optimistic concurrency control
 * - REL-EVS-7401: Event appending uses database transactions (ACID compliance)
 * - REL-EVS-7405: Support idempotent event appending (duplicate detection via EventId)
 */
final readonly class DbEventStoreRepository implements EventStoreInterface
{
    public function __construct(
        private string $tenantId
    ) {
    }

    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void {
        DB::transaction(function () use ($aggregateId, $event, $expectedVersion) {
            // Check optimistic concurrency if expected version provided
            if ($expectedVersion !== null) {
                $currentVersion = $this->getCurrentVersion($aggregateId);
                
                if ($currentVersion !== $expectedVersion) {
                    throw new ConcurrencyException(
                        $aggregateId,
                        $expectedVersion,
                        $currentVersion
                    );
                }
            }

            try {
                EventStream::create([
                    'event_id' => $event->getEventId(),
                    'aggregate_id' => $aggregateId,
                    'aggregate_type' => $this->extractAggregateType($aggregateId),
                    'version' => $event->getVersion(),
                    'event_type' => $event->getEventType(),
                    'payload' => $event->getPayload(),
                    'metadata' => $event->getMetadata(),
                    'causation_id' => $event->getCausationId(),
                    'correlation_id' => $event->getCorrelationId(),
                    'tenant_id' => $this->tenantId,
                    'user_id' => $event->getUserId(),
                    'occurred_at' => $event->getOccurredAt(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Duplicate event ID (idempotent check)
                if ($e->getCode() === '23000') {
                    // Silently ignore - event already exists
                    return;
                }

                throw new EventStreamException(
                    'Failed to append event: ' . $e->getMessage(),
                    previous: $e
                );
            }
        });
    }

    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void {
        DB::transaction(function () use ($aggregateId, $events, $expectedVersion) {
            // Check optimistic concurrency if expected version provided
            if ($expectedVersion !== null) {
                $currentVersion = $this->getCurrentVersion($aggregateId);
                
                if ($currentVersion !== $expectedVersion) {
                    throw new ConcurrencyException(
                        $aggregateId,
                        $expectedVersion,
                        $currentVersion
                    );
                }
            }

            foreach ($events as $event) {
                $this->append($aggregateId, $event, null); // No version check per event
            }
        });
    }

    public function getCurrentVersion(string $aggregateId): int
    {
        $maxVersion = EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->max('version');

        return $maxVersion ?? 0;
    }

    public function streamExists(string $aggregateId): bool
    {
        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $this->tenantId)
            ->exists();
    }

    /**
     * Extract aggregate type from aggregate ID
     * (This is a simple implementation - could be more sophisticated)
     *
     * @param string $aggregateId
     * @return string
     */
    private function extractAggregateType(string $aggregateId): string
    {
        // Example: "account-1000" -> "account"
        $parts = explode('-', $aggregateId, 2);
        return $parts[0] ?? 'unknown';
    }
}
