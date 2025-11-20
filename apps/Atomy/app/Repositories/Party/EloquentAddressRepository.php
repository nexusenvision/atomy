<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyAddress;
use Nexus\Party\Contracts\AddressInterface;
use Nexus\Party\Contracts\AddressRepositoryInterface;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\Exceptions\AddressNotFoundException;

final readonly class EloquentAddressRepository implements AddressRepositoryInterface
{
    public function findById(string $id): AddressInterface
    {
        $address = PartyAddress::find($id);

        if (!$address) {
            throw AddressNotFoundException::forId($id);
        }

        return $address;
    }

    public function findByPartyId(string $partyId): array
    {
        return PartyAddress::where('party_id', $partyId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByType(string $partyId, AddressType $type): array
    {
        return PartyAddress::where('party_id', $partyId)
            ->where('address_type', $type->value)
            ->orderBy('is_primary', 'desc')
            ->get()
            ->all();
    }

    public function getPrimaryAddress(string $partyId): ?AddressInterface
    {
        return PartyAddress::where('party_id', $partyId)
            ->where('is_primary', true)
            ->first();
    }

    public function clearPrimaryFlag(string $partyId): void
    {
        PartyAddress::where('party_id', $partyId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    public function save(AddressInterface $address): void
    {
        if ($address instanceof PartyAddress) {
            $address->save();
        }
    }

    public function update(AddressInterface $address): void
    {
        if ($address instanceof PartyAddress) {
            $address->save();
        }
    }

    public function delete(string $id): void
    {
        $address = PartyAddress::find($id);

        if ($address) {
            $address->delete();
        }
    }
}
