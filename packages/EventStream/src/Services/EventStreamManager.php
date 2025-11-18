<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Core\Engine\ProjectionEngine;
use Nexus\EventStream\Core\Engine\SnapshotManager;
use Psr\Log\LoggerInterface;

/**
 * EventStreamManager
 *
 * Main service for managing event streams. This is the primary public API
 * for the EventStream package.
 *
 * Requirements satisfied:
 * - ARC-EVS-7004: Business logic in service layer (EventStreamManager)
 * - FUN-EVS-7208: Replay event stream to rebuild aggregate state at specific point in time
 * - FUN-EVS-7222: Provide temporal query API: getStateAt(aggregateId, timestamp)
 *
 * @package Nexus\EventStream\Services
 */
final readonly class EventStreamManager
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private StreamReaderInterface $streamReader,
        private SnapshotRepositoryInterface $snapshotRepository,
        private ProjectionEngine $projectionEngine,
        private SnapshotManager $snapshotManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get the current version of a stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return int
     */
    public function getCurrentVersion(string $aggregateId): int
    {
        return $this->eventStore->getCurrentVersion($aggregateId);
    }

    /**
     * Check if a stream exists
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool
     */
    public function streamExists(string $aggregateId): bool
    {
        return $this->eventStore->streamExists($aggregateId);
    }

    /**
     * Replay events to rebuild aggregate state at a specific point in time (temporal query)
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $timestamp The point in time
     * @param ProjectorInterface $projector The projector to use for rebuilding state
     * @return void
     */
    public function getStateAt(
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ProjectorInterface $projector
    ): void {
        $this->logger->info('Temporal query: replaying events', [
            'aggregate_id' => $aggregateId,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'projector' => $projector->getName(),
        ]);

        // Read events up to the timestamp
        $events = $this->streamReader->readStreamUntil($aggregateId, $timestamp);

        // Reset projector
        $projector->reset();

        // Replay events
        foreach ($events as $event) {
            $projector->project($event);
        }

        $this->logger->info('Temporal query completed', [
            'aggregate_id' => $aggregateId,
            'events_replayed' => count($events),
        ]);
    }

    /**
     * Rebuild a projection from scratch
     *
     * @param ProjectorInterface $projector The projector to rebuild
     * @return void
     */
    public function rebuildProjection(ProjectorInterface $projector): void
    {
        $this->projectionEngine->rebuild($projector);
    }

    /**
     * Run a projector on all events of the types it handles
     *
     * @param ProjectorInterface $projector The projector to run
     * @return void
     */
    public function runProjection(ProjectorInterface $projector): void
    {
        $this->projectionEngine->run($projector);
    }

    /**
     * Create a snapshot for an aggregate if threshold is reached
     *
     * @param string $aggregateId The aggregate identifier
     * @param array<string, mixed> $state The aggregate state
     * @return bool True if snapshot was created
     */
    public function createSnapshotIfNeeded(string $aggregateId, array $state): bool
    {
        return $this->snapshotManager->createIfNeeded($aggregateId, $state);
    }

    /**
     * Get stream health metrics
     *
     * @param string $aggregateId The aggregate identifier
     * @return array<string, mixed>
     */
    public function getStreamHealth(string $aggregateId): array
    {
        $events = $this->streamReader->readStream($aggregateId);
        $currentVersion = $this->eventStore->getCurrentVersion($aggregateId);
        $hasSnapshot = $this->snapshotRepository->exists($aggregateId);

        return [
            'aggregate_id' => $aggregateId,
            'event_count' => count($events),
            'current_version' => $currentVersion,
            'has_snapshot' => $hasSnapshot,
            'stream_exists' => !empty($events),
        ];
    }
}
