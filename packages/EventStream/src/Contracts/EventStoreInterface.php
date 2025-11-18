<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\EventStreamException;

/**
 * EventStoreInterface
 *
 * Contract for appending events to event streams with optimistic concurrency control.
 * This is the primary interface for publishing domain events.
 *
 * Requirements satisfied:
 * - ARC-EVS-7003: All persistence operations via EventStoreInterface
 * - FUN-EVS-7201: Define EventStoreInterface for appending events to streams
 * - FUN-EVS-7205: Append events with automatic version increment and concurrency check
 * - BUS-EVS-7105: Event streams MUST support optimistic concurrency control
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventStoreInterface
{
    /**
     * Append a single event to the stream
     *
     * @param string $aggregateId The aggregate identifier
     * @param EventInterface $event The event to append
     * @param int|null $expectedVersion Expected current version for optimistic locking (null = no check)
     * @return void
     * @throws ConcurrencyException If version conflict detected
     * @throws EventStreamException If append fails
     */
    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void;

    /**
     * Append multiple events to the stream in a single transaction
     *
     * @param string $aggregateId The aggregate identifier
     * @param EventInterface[] $events The events to append
     * @param int|null $expectedVersion Expected current version for optimistic locking
     * @return void
     * @throws ConcurrencyException If version conflict detected
     * @throws EventStreamException If append fails
     */
    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void;

    /**
     * Get the current version of the stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return int Current version (0 if stream doesn't exist)
     */
    public function getCurrentVersion(string $aggregateId): int;

    /**
     * Check if a stream exists
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool
     */
    public function streamExists(string $aggregateId): bool;
}
