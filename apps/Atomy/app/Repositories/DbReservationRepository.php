<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sequence;
use App\Models\SequenceReservation;
use Illuminate\Support\Str;
use Nexus\Sequencing\Contracts\ReservationRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Database repository implementation for reservations.
 */
final readonly class DbReservationRepository implements ReservationRepositoryInterface
{
    public function reserve(SequenceInterface $sequence, array $numbers, int $ttlMinutes): string
    {
        /** @var Sequence $sequence */
        $reservationId = (string) Str::uuid();
        $expiresAt = now()->addMinutes($ttlMinutes);

        foreach ($numbers as $number) {
            SequenceReservation::create([
                'sequence_id' => $sequence->id,
                'reservation_id' => $reservationId,
                'number' => $number,
                'status' => 'reserved',
                'expires_at' => $expiresAt,
            ]);
        }

        return $reservationId;
    }

    public function release(SequenceInterface $sequence, array $numbers): void
    {
        /** @var Sequence $sequence */
        SequenceReservation::query()
            ->where('sequence_id', $sequence->id)
            ->whereIn('number', $numbers)
            ->where('status', 'reserved')
            ->update([
                'status' => 'released',
            ]);
    }

    public function finalize(SequenceInterface $sequence, array $numbers): void
    {
        /** @var Sequence $sequence */
        SequenceReservation::query()
            ->where('sequence_id', $sequence->id)
            ->whereIn('number', $numbers)
            ->where('status', 'reserved')
            ->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);
    }

    public function getActiveReservations(SequenceInterface $sequence): array
    {
        /** @var Sequence $sequence */
        return SequenceReservation::query()
            ->where('sequence_id', $sequence->id)
            ->active()
            ->get()
            ->map(fn($res) => [
                'reservation_id' => $res->reservation_id,
                'number' => $res->number,
                'expires_at' => $res->expires_at,
            ])
            ->all();
    }

    public function releaseExpired(): int
    {
        return SequenceReservation::query()
            ->expired()
            ->update([
                'status' => 'expired',
            ]);
    }

    public function isReserved(SequenceInterface $sequence, string $number): bool
    {
        /** @var Sequence $sequence */
        return SequenceReservation::query()
            ->where('sequence_id', $sequence->id)
            ->where('number', $number)
            ->active()
            ->exists();
    }
}
