<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sequence;
use Nexus\Sequencing\Contracts\SequenceInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Exceptions\SequenceNotFoundException;

/**
 * Database repository implementation for sequences.
 */
final readonly class DbSequenceRepository implements SequenceRepositoryInterface
{
    public function findByNameAndScope(string $name, ?string $scopeIdentifier = null): SequenceInterface
    {
        $sequence = Sequence::query()
            ->where('name', $name)
            ->where('scope_identifier', $scopeIdentifier)
            ->where('is_active', true)
            ->first();

        if ($sequence === null) {
            throw SequenceNotFoundException::byNameAndScope($name, $scopeIdentifier);
        }

        return $sequence;
    }

    public function findByNameAndScopeOrNull(string $name, ?string $scopeIdentifier = null): ?SequenceInterface
    {
        return Sequence::query()
            ->where('name', $name)
            ->where('scope_identifier', $scopeIdentifier)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): SequenceInterface
    {
        return Sequence::create($data);
    }

    public function update(SequenceInterface $sequence, array $data): SequenceInterface
    {
        /** @var Sequence $sequence */
        $sequence->update($data);
        return $sequence->fresh();
    }

    public function delete(SequenceInterface $sequence): void
    {
        /** @var Sequence $sequence */
        $sequence->delete();
    }

    public function lock(SequenceInterface $sequence): void
    {
        /** @var Sequence $sequence */
        $sequence->update(['is_locked' => true]);
    }

    public function unlock(SequenceInterface $sequence): void
    {
        /** @var Sequence $sequence */
        $sequence->update(['is_locked' => false]);
    }

    public function getAllForScope(?string $scopeIdentifier = null): array
    {
        return Sequence::query()
            ->where('scope_identifier', $scopeIdentifier)
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
