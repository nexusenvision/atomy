<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sequence;
use App\Models\SequenceCounter;
use Illuminate\Support\Facades\DB;
use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Database repository implementation for counter management.
 */
final readonly class DbCounterRepository implements CounterRepositoryInterface
{
    public function getCurrentValueWithLock(SequenceInterface $sequence): int
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);

        // Acquire row-level lock with SELECT FOR UPDATE
        return DB::transaction(function () use ($counter) {
            $locked = SequenceCounter::query()
                ->where('id', $counter->id)
                ->lockForUpdate()
                ->first();

            return $locked->current_value;
        });
    }

    public function getCurrentValue(SequenceInterface $sequence): int
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);
        return $counter->current_value;
    }

    public function increment(SequenceInterface $sequence, int $stepSize): int
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);

        $newValue = $counter->current_value + $stepSize;

        $counter->update([
            'current_value' => $newValue,
            'generation_count' => $counter->generation_count + 1,
            'last_generated_at' => now(),
        ]);

        return $newValue;
    }

    public function reset(SequenceInterface $sequence): void
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);

        $counter->update([
            'current_value' => 0,
            'generation_count' => 0,
            'last_reset_at' => now(),
        ]);
    }

    public function setCounterValue(SequenceInterface $sequence, int $value): void
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);

        $counter->update([
            'current_value' => $value,
        ]);
    }

    public function getLastResetAt(SequenceInterface $sequence): ?\DateTimeInterface
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);
        return $counter->last_reset_at;
    }

    public function needsReset(SequenceInterface $sequence): bool
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);
        $lastReset = $counter->last_reset_at;

        if ($lastReset === null) {
            return false;
        }

        $resetPeriod = $sequence->getResetPeriod();

        return match ($resetPeriod) {
            'never' => false,
            'daily' => $lastReset->format('Y-m-d') !== now()->format('Y-m-d'),
            'monthly' => $lastReset->format('Y-m') !== now()->format('Y-m'),
            'yearly' => $lastReset->format('Y') !== now()->format('Y'),
            default => false,
        };
    }

    public function getGenerationCount(SequenceInterface $sequence): int
    {
        /** @var Sequence $sequence */
        $counter = $this->getOrCreateCounter($sequence);
        return $counter->generation_count;
    }

    /**
     * Get or create counter for sequence.
     */
    private function getOrCreateCounter(Sequence $sequence): SequenceCounter
    {
        return SequenceCounter::query()
            ->firstOrCreate(
                ['sequence_id' => $sequence->id],
                [
                    'current_value' => 0,
                    'generation_count' => 0,
                ]
            );
    }
}
