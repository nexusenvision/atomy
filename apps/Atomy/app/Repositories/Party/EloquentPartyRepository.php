<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\Party;
use Nexus\Party\Contracts\PartyInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Enums\PartyType;
use Nexus\Party\Exceptions\PartyNotFoundException;
use Nexus\Party\ValueObjects\TaxIdentity;

final readonly class EloquentPartyRepository implements PartyRepositoryInterface
{
    public function findById(string $id): PartyInterface
    {
        $party = Party::find($id);

        if (!$party) {
            throw PartyNotFoundException::forId($id);
        }

        return $party;
    }

    public function findByIdOrNull(string $id): ?PartyInterface
    {
        return Party::find($id);
    }

    public function findByLegalName(string $tenantId, string $legalName): ?PartyInterface
    {
        return Party::where('tenant_id', $tenantId)
            ->where('legal_name', $legalName)
            ->first();
    }

    public function findByTaxIdentity(string $tenantId, TaxIdentity $taxIdentity): ?PartyInterface
    {
        return Party::where('tenant_id', $tenantId)
            ->whereJsonContains('tax_identity->country', $taxIdentity->country)
            ->whereJsonContains('tax_identity->number', $taxIdentity->number)
            ->first();
    }

    public function searchByName(string $tenantId, string $query, int $limit = 50): array
    {
        return Party::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('legal_name', 'LIKE', "%{$query}%")
                  ->orWhere('display_name', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->all();
    }

    public function findByType(string $tenantId, PartyType $type, int $limit = 100): array
    {
        return Party::where('tenant_id', $tenantId)
            ->where('party_type', $type->value)
            ->limit($limit)
            ->get()
            ->all();
    }

    public function save(PartyInterface $party): void
    {
        if ($party instanceof Party) {
            $party->save();
        }
    }

    public function update(PartyInterface $party): void
    {
        if ($party instanceof Party) {
            $party->save();
        }
    }

    public function delete(string $id): void
    {
        $party = Party::find($id);

        if ($party) {
            $party->delete();
        }
    }

    public function findPotentialDuplicates(
        string $tenantId,
        ?string $legalName = null,
        ?TaxIdentity $taxIdentity = null
    ): array {
        $query = Party::where('tenant_id', $tenantId);

        if ($legalName !== null) {
            $query->where('legal_name', 'LIKE', "%{$legalName}%");
        }

        if ($taxIdentity !== null) {
            $query->orWhere(function ($q) use ($taxIdentity) {
                $q->whereJsonContains('tax_identity->country', $taxIdentity->country)
                  ->whereJsonContains('tax_identity->number', $taxIdentity->number);
            });
        }

        return $query->limit(10)->get()->all();
    }

    public function getAllByTenant(string $tenantId, int $limit = 1000): array
    {
        return Party::where('tenant_id', $tenantId)
            ->limit($limit)
            ->get()
            ->all();
    }
}
