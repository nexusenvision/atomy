<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sequence;
use App\Models\SequencePatternVersion;
use Nexus\Sequencing\Contracts\PatternVersionRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Exceptions\NoActivePatternException;
use Nexus\Sequencing\Exceptions\PatternVersionConflictException;

/**
 * Database repository implementation for pattern versions.
 */
final readonly class DbPatternVersionRepository implements PatternVersionRepositoryInterface
{
    public function getActivePattern(SequenceInterface $sequence, \DateTimeInterface $date): string
    {
        /** @var Sequence $sequence */
        $version = SequencePatternVersion::query()
            ->where('sequence_id', $sequence->id)
            ->activeOn($date)
            ->orderBy('effective_from', 'desc')
            ->first();

        if ($version === null) {
            throw NoActivePatternException::noActiveVersion($sequence->getName(), $date);
        }

        return $version->pattern;
    }

    public function createVersion(
        SequenceInterface $sequence,
        string $pattern,
        \DateTimeInterface $effectiveFrom,
        ?\DateTimeInterface $effectiveUntil = null
    ): void {
        /** @var Sequence $sequence */
        SequencePatternVersion::create([
            'sequence_id' => $sequence->id,
            'pattern' => $pattern,
            'effective_from' => $effectiveFrom,
            'effective_until' => $effectiveUntil,
        ]);
    }

    public function getAllVersions(SequenceInterface $sequence): array
    {
        /** @var Sequence $sequence */
        return SequencePatternVersion::query()
            ->where('sequence_id', $sequence->id)
            ->orderBy('effective_from', 'desc')
            ->get()
            ->map(fn($version) => [
                'pattern' => $version->pattern,
                'effective_from' => $version->effective_from,
                'effective_until' => $version->effective_until,
            ])
            ->all();
    }

    public function validateEffectiveDate(SequenceInterface $sequence, \DateTimeInterface $effectiveFrom): void
    {
        /** @var Sequence $sequence */
        $conflicts = SequencePatternVersion::query()
            ->where('sequence_id', $sequence->id)
            ->where(function ($query) use ($effectiveFrom) {
                $query->where(function ($q) use ($effectiveFrom) {
                    $q->where('effective_from', '<=', $effectiveFrom)
                        ->where(function ($q2) use ($effectiveFrom) {
                            $q2->whereNull('effective_until')
                                ->orWhere('effective_until', '>', $effectiveFrom);
                        });
                });
            })
            ->exists();

        if ($conflicts) {
            $existingVersion = SequencePatternVersion::query()
                ->where('sequence_id', $sequence->id)
                ->orderBy('effective_from', 'desc')
                ->first();

            throw PatternVersionConflictException::overlappingDates(
                $effectiveFrom,
                $existingVersion->effective_from
            );
        }
    }
}
