<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * ConcurrencyException
 *
 * Thrown when an optimistic concurrency conflict is detected during event appending.
 * This indicates that another process has modified the stream since it was last read.
 *
 * Requirements satisfied:
 * - BUS-EVS-7105: Event streams MUST support optimistic concurrency control
 * - REL-EVS-7402: Optimistic concurrency control prevents lost updates
 * - REL-EVS-7403: Failed event append throws ConcurrencyException with retry guidance
 *
 * @package Nexus\EventStream\Exceptions
 */
class ConcurrencyException extends EventStreamException
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly int $expectedVersion,
        public readonly int $actualVersion,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Concurrency conflict detected for aggregate "%s". Expected version %d but found %d. Please retry.',
                $aggregateId,
                $expectedVersion,
                $actualVersion
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
