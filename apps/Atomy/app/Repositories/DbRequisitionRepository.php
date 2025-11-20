<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Requisition;
use App\Models\RequisitionLine;
use Illuminate\Support\Str;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;

final readonly class DbRequisitionRepository implements RequisitionRepositoryInterface
{
    public function generateNextNumber(string $tenantId): string
    {
        // Placeholder: Returns ULID until Nexus\Sequencing integration is implemented
        return 'REQ-' . strtoupper((string) Str::ulid());
    }

    public function create(string $tenantId, string $requesterId, array $data): RequisitionInterface
    {
        $totalEstimate = 0.0;
        
        $requisition = Requisition::create([
            'tenant_id' => $tenantId,
            'number' => $data['number'],
            'requester_id' => $requesterId,
            'description' => $data['description'],
            'department' => $data['department'],
            'status' => 'draft',
            'total_estimate' => 0, // Will be updated after creating lines
            'metadata' => $data['metadata'] ?? [],
        ]);

        foreach ($data['lines'] as $index => $lineData) {
            $lineTotal = $lineData['quantity'] * $lineData['estimated_unit_price'];
            $totalEstimate += $lineTotal;

            RequisitionLine::create([
                'requisition_id' => $requisition->id,
                'line_number' => $index + 1,
                'item_code' => $lineData['item_code'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit' => $lineData['unit'],
                'estimated_unit_price' => $lineData['estimated_unit_price'],
                'line_total' => $lineTotal,
                'notes' => $lineData['notes'] ?? null,
                'metadata' => $lineData['metadata'] ?? [],
            ]);
        }

        $requisition->update(['total_estimate' => $totalEstimate]);
        $requisition->load('lines');

        return $requisition;
    }

    public function findById(string $id): ?RequisitionInterface
    {
        return Requisition::with('lines')->find($id);
    }

    public function findByNumber(string $tenantId, string $number): ?RequisitionInterface
    {
        return Requisition::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();
    }

    public function findByTenantId(string $tenantId, array $filters = []): array
    {
        $query = Requisition::with('lines')->where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return Requisition::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function updateStatus(string $id, string $status): RequisitionInterface
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->update(['status' => $status]);
        $requisition->load('lines');

        return $requisition;
    }

    public function approve(string $id, string $approverId): RequisitionInterface
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->update([
            'status' => 'approved',
            'approver_id' => $approverId,
            'approved_at' => now(),
        ]);
        $requisition->load('lines');

        return $requisition;
    }

    public function reject(string $id, string $rejectorId, string $reason): RequisitionInterface
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->update([
            'status' => 'rejected',
            'rejector_id' => $rejectorId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
        $requisition->load('lines');

        return $requisition;
    }

    public function markAsConverted(string $id, string $purchaseOrderId): RequisitionInterface
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->update([
            'status' => 'converted',
            'is_converted' => true,
            'converted_po_id' => $purchaseOrderId,
            'converted_at' => now(),
        ]);
        $requisition->load('lines');

        return $requisition;
    }

    public function save(RequisitionInterface $requisition): void
    {
        if ($requisition instanceof Requisition) {
            $requisition->save();
        }
    }

    public function delete(string $id): void
    {
        $requisition = Requisition::findOrFail($id);
        $requisition->delete();
    }
}
