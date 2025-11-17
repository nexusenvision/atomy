<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Nexus\Sequencing\Contracts\SequenceAuditInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Audit logger implementation using Laravel's logging system.
 */
final readonly class SequenceAuditLogger implements SequenceAuditInterface
{
    public function logPatternCreated(SequenceInterface $sequence, array $metadata = []): void
    {
        Log::info('Sequence pattern created', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'pattern' => $sequence->getPattern(),
            'metadata' => $metadata,
        ]);
    }

    public function logPatternModified(SequenceInterface $sequence, array $changes): void
    {
        Log::info('Sequence pattern modified', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'changes' => $changes,
        ]);
    }

    public function logCounterReset(SequenceInterface $sequence, int $oldValue, int $newValue, string $reason): void
    {
        Log::warning('Sequence counter reset', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
        ]);
    }

    public function logCounterOverridden(SequenceInterface $sequence, int $oldValue, int $newValue, ?string $performedBy = null): void
    {
        Log::warning('Sequence counter manually overridden', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'performed_by' => $performedBy,
        ]);
    }

    public function logExhaustionThresholdReached(SequenceInterface $sequence, int $currentValue, int $threshold): void
    {
        Log::critical('Sequence exhaustion threshold reached', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'current_value' => $currentValue,
            'threshold_percentage' => $threshold,
        ]);
    }

    public function logPatternVersionCreated(SequenceInterface $sequence, string $oldPattern, string $newPattern, \DateTimeInterface $effectiveFrom): void
    {
        Log::info('Sequence pattern version created', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'old_pattern' => $oldPattern,
            'new_pattern' => $newPattern,
            'effective_from' => $effectiveFrom->format('Y-m-d H:i:s'),
        ]);
    }

    public function logNumberGenerated(SequenceInterface $sequence, string $generatedNumber, array $context = []): void
    {
        Log::debug('Sequence number generated', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'generated_number' => $generatedNumber,
            'context' => $context,
        ]);
    }

    public function logGapReclaimed(SequenceInterface $sequence, string $number): void
    {
        Log::info('Sequence gap reclaimed', [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'number' => $number,
        ]);
    }

    public function logLockStatusChanged(SequenceInterface $sequence, bool $isLocked, ?string $performedBy = null): void
    {
        $action = $isLocked ? 'locked' : 'unlocked';
        Log::warning("Sequence {$action}", [
            'sequence_name' => $sequence->getName(),
            'scope' => $sequence->getScopeIdentifier(),
            'is_locked' => $isLocked,
            'performed_by' => $performedBy,
        ]);
    }
}
