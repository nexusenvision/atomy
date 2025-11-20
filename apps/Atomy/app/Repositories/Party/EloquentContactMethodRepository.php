<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyContactMethod;
use Nexus\Party\Contracts\ContactMethodInterface;
use Nexus\Party\Contracts\ContactMethodRepositoryInterface;
use Nexus\Party\Enums\ContactMethodType;
use Nexus\Party\Exceptions\ContactMethodNotFoundException;

final readonly class EloquentContactMethodRepository implements ContactMethodRepositoryInterface
{
    public function findById(string $id): ContactMethodInterface
    {
        $contactMethod = PartyContactMethod::find($id);

        if (!$contactMethod) {
            throw ContactMethodNotFoundException::forId($id);
        }

        return $contactMethod;
    }

    public function findByPartyId(string $partyId): array
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByType(string $partyId, ContactMethodType $type): array
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->where('contact_type', $type->value)
            ->orderBy('is_primary', 'desc')
            ->get()
            ->all();
    }

    public function getPrimaryContactMethod(string $partyId, ContactMethodType $type): ?ContactMethodInterface
    {
        return PartyContactMethod::where('party_id', $partyId)
            ->where('contact_type', $type->value)
            ->where('is_primary', true)
            ->first();
    }

    public function clearPrimaryFlag(string $partyId, ContactMethodType $type): void
    {
        PartyContactMethod::where('party_id', $partyId)
            ->where('contact_type', $type->value)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    public function save(ContactMethodInterface $contactMethod): void
    {
        if ($contactMethod instanceof PartyContactMethod) {
            $contactMethod->save();
        }
    }

    public function update(ContactMethodInterface $contactMethod): void
    {
        if ($contactMethod instanceof PartyContactMethod) {
            $contactMethod->save();
        }
    }

    public function delete(string $id): void
    {
        $contactMethod = PartyContactMethod::find($id);

        if ($contactMethod) {
            $contactMethod->delete();
        }
    }
}
