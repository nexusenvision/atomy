<?php

declare(strict_types=1);

namespace Nexus\EventStream\Core\Engine;

use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Exceptions\ProjectionException;
use Psr\Log\LoggerInterface;

/**
 * ProjectionEngine
 *
 * Internal engine for running and managing projections.
 * This is part of the internal Core/ folder and should not be directly accessed
 * by consuming applications.
 *
 * Requirements satisfied:
 * - ARC-EVS-7010: Separate Core/ folder for internal engine (ProjectionEngine)
 * - FUN-EVS-7215: Provide projection engine for building read models
 * - FUN-EVS-7217: Rebuild projections from scratch by replaying entire stream
 * - FUN-EVS-7218: Resume projection from last processed event on failure/restart
 *
 * @package Nexus\EventStream\Core\Engine
 */
final readonly class ProjectionEngine
{
    public function __construct(
        private StreamReaderInterface $streamReader,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Run a projector from its last processed position
     *
     * @param ProjectorInterface $projector
     * @return void
     */
    public function run(ProjectorInterface $projector): void
    {
        $lastEventId = $projector->getLastProcessedEventId();

        $this->logger->info('Running projection', [
            'projector' => $projector->getName(),
            'last_event_id' => $lastEventId,
        ]);

        foreach ($projector->getHandledEventTypes() as $eventType) {
            $events = $this->streamReader->readEventsByType($eventType);

            foreach ($events as $event) {
                // Skip events we've already processed
                if ($lastEventId !== null && $event->getEventId() <= $lastEventId) {
                    continue;
                }

                try {
                    $projector->project($event);
                    $projector->setLastProcessedEventId($event->getEventId());
                } catch (\Throwable $e) {
                    $this->logger->error('Projection failed', [
                        'projector' => $projector->getName(),
                        'event_id' => $event->getEventId(),
                        'error' => $e->getMessage(),
                    ]);

                    throw new ProjectionException(
                        $projector->getName(),
                        $event->getEventId(),
                        previous: $e
                    );
                }
            }
        }

        $this->logger->info('Projection completed', [
            'projector' => $projector->getName(),
        ]);
    }

    /**
     * Rebuild a projector from scratch by replaying all events
     *
     * @param ProjectorInterface $projector
     * @return void
     */
    public function rebuild(ProjectorInterface $projector): void
    {
        $this->logger->info('Rebuilding projection from scratch', [
            'projector' => $projector->getName(),
        ]);

        // Reset the projector
        $projector->reset();

        // Run from the beginning
        $this->run($projector);
    }
}
