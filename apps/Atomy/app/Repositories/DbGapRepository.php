<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sequence;
use App\Models\SequenceGap;
use Nexus\Sequencing\Contracts\GapRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Database repository implementation for gap management.
 */
final readonly class DbGapRepository implements GapRepositoryInterface
{
    public function recordGap(SequenceInterface $sequence, string $number, ?string $reason = null): void
    {
        /** @var Sequence $sequence */
        SequenceGap::create([
            'sequence_id' => $sequence->id,
            'number' => $number,
            'status' => 'unfilled',
            'reason' => $reason,
        ]);
    }

    public function getGaps(SequenceInterface $sequence): array
    {
        /** @var Sequence $sequence */
        return SequenceGap::query()
            ->where('sequence_id', $sequence->id)
            ->get()
            ->map(fn($gap) => [
                'number' => $gap->number,
                'status' => $gap->status,
                'reason' => $gap->reason,
                'created_at' => $gap->created_at,
                'filled_at' => $gap->filled_at,
            ])
            ->all();
    }

    public function getNextGap(SequenceInterface $sequence): ?string
    {
        /** @var Sequence $sequence */
        $gap = SequenceGap::query()
            ->where('sequence_id', $sequence->id)
            ->unfilled()
            ->orderBy('created_at')
            ->first();

        return $gap?->number;
    }

    public function markGapFilled(SequenceInterface $sequence, string $number): void
    {
        /** @var Sequence $sequence */
        SequenceGap::query()
            ->where('sequence_id', $sequence->id)
            ->where('number', $number)
            ->update([
                'status' => 'filled',
                'filled_at' => now(),
            ]);
    }

    public function getGapReport(SequenceInterface $sequence): array
    {
        /** @var Sequence $sequence */
        return SequenceGap::query()
            ->where('sequence_id', $sequence->id)
            ->unfilled()
            ->pluck('number')
            ->all();
    }

    public function clearGaps(SequenceInterface $sequence): void
    {
        /** @var Sequence $sequence */
        SequenceGap::query()
            ->where('sequence_id', $sequence->id)
            ->delete();
    }
}
