<?php

declare(strict_types=1);

namespace App\Repositories\Party;

use App\Models\PartyRelationship;
use Illuminate\Support\Facades\DB;
use Nexus\Party\Contracts\PartyRelationshipInterface;
use Nexus\Party\Contracts\PartyRelationshipRepositoryInterface;
use Nexus\Party\Enums\RelationshipType;
use Nexus\Party\Exceptions\RelationshipNotFoundException;

final readonly class EloquentPartyRelationshipRepository implements PartyRelationshipRepositoryInterface
{
    public function findById(string $id): PartyRelationshipInterface
    {
        $relationship = PartyRelationship::find($id);

        if (!$relationship) {
            throw RelationshipNotFoundException::forId($id);
        }

        return $relationship;
    }

    public function findByParties(string $fromPartyId, string $toPartyId): array
    {
        return PartyRelationship::where('from_party_id', $fromPartyId)
            ->where('to_party_id', $toPartyId)
            ->get()
            ->all();
    }

    public function findActiveRelationships(string $partyId): array
    {
        $now = new \DateTimeImmutable('now');

        return PartyRelationship::where(function ($query) use ($partyId) {
                $query->where('from_party_id', $partyId)
                      ->orWhere('to_party_id', $partyId);
            })
            ->where(function ($query) use ($now) {
                $query->where('effective_from', '<=', $now->format('Y-m-d'))
                      ->where(function ($q) use ($now) {
                          $q->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $now->format('Y-m-d'));
                      });
            })
            ->get()
            ->all();
    }

    public function findByType(string $partyId, RelationshipType $type): array
    {
        return PartyRelationship::where(function ($query) use ($partyId) {
                $query->where('from_party_id', $partyId)
                      ->orWhere('to_party_id', $partyId);
            })
            ->where('relationship_type', $type->value)
            ->get()
            ->all();
    }

    public function getCurrentEmployer(string $individualPartyId): ?PartyRelationshipInterface
    {
        $now = new \DateTimeImmutable('now');

        return PartyRelationship::where('from_party_id', $individualPartyId)
            ->where('relationship_type', RelationshipType::EMPLOYMENT_AT->value)
            ->where('effective_from', '<=', $now->format('Y-m-d'))
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $now->format('Y-m-d'));
            })
            ->first();
    }

    public function getOrganizationalChain(string $partyId, int $maxDepth = 10): array
    {
        // Use recursive CTE to get the complete organizational hierarchy
        $results = DB::select("
            WITH RECURSIVE org_chain AS (
                -- Base case: start with the given party
                SELECT 
                    id,
                    from_party_id,
                    to_party_id,
                    relationship_type,
                    effective_from,
                    effective_to,
                    1 as depth
                FROM party_relationships
                WHERE from_party_id = :party_id
                  AND relationship_type = :rel_type
                  AND effective_from <= CURRENT_DATE
                  AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
                
                UNION ALL
                
                -- Recursive case: get parent relationships
                SELECT 
                    pr.id,
                    pr.from_party_id,
                    pr.to_party_id,
                    pr.relationship_type,
                    pr.effective_from,
                    pr.effective_to,
                    oc.depth + 1
                FROM party_relationships pr
                INNER JOIN org_chain oc ON pr.from_party_id = oc.to_party_id
                WHERE pr.relationship_type = :rel_type
                  AND pr.effective_from <= CURRENT_DATE
                  AND (pr.effective_to IS NULL OR pr.effective_to >= CURRENT_DATE)
                  AND oc.depth < :max_depth
            )
            SELECT * FROM org_chain ORDER BY depth
        ", [
            'party_id' => $partyId,
            'rel_type' => RelationshipType::PART_OF->value,
            'max_depth' => $maxDepth,
        ]);

        // Convert stdClass results to PartyRelationship models
        return array_map(function ($row) {
            return PartyRelationship::find($row->id);
        }, $results);
    }

    public function save(PartyRelationshipInterface $relationship): void
    {
        if ($relationship instanceof PartyRelationship) {
            $relationship->save();
        }
    }

    public function update(PartyRelationshipInterface $relationship): void
    {
        if ($relationship instanceof PartyRelationship) {
            $relationship->save();
        }
    }

    public function delete(string $id): void
    {
        $relationship = PartyRelationship::find($id);

        if ($relationship) {
            $relationship->delete();
        }
    }
}
