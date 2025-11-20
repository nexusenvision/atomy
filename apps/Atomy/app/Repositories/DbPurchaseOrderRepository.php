<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Str;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;

final readonly class DbPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function generateNextNumber(string $tenantId): string
    {
        // Placeholder: Returns ULID until Nexus\Sequencing integration is implemented
        return 'PO-' . strtoupper((string) Str::ulid());
    }

    public function create(string $tenantId, string $requisitionId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $totalAmount = 0.0;

        $po = PurchaseOrder::create([
            'tenant_id' => $tenantId,
            'number' => $data['number'],
            'vendor_id' => $data['vendor_id'],
            'creator_id' => $creatorId,
            'requisition_id' => $requisitionId,
            'status' => 'draft',
            'po_type' => 'standard',
            'total_amount' => 0, // Will be updated after creating lines
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        foreach ($data['lines'] as $index => $lineData) {
            $lineTotal = $lineData['quantity'] * $lineData['unit_price'];
            $totalAmount += $lineTotal;

            $lineReference = $po->number . '-L' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);

            PurchaseOrderLine::create([
                'purchase_order_id' => $po->id,
                'line_reference' => $lineReference,
                'line_number' => $index + 1,
                'requisition_line_id' => $lineData['requisition_line_id'] ?? null,
                'item_code' => $lineData['item_code'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit' => $lineData['unit'],
                'unit_price' => $lineData['unit_price'],
                'line_total' => $lineTotal,
                'quantity_received' => 0,
                'notes' => $lineData['notes'] ?? null,
                'metadata' => $lineData['metadata'] ?? [],
            ]);
        }

        $po->update(['total_amount' => $totalAmount]);
        $po->load('lines');

        return $po;
    }

    public function createBlanket(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $po = PurchaseOrder::create([
            'tenant_id' => $tenantId,
            'number' => $data['number'],
            'vendor_id' => $data['vendor_id'],
            'creator_id' => $creatorId,
            'status' => 'draft',
            'po_type' => 'blanket',
            'total_committed_value' => $data['total_committed_value'],
            'total_released_value' => 0,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'],
            'payment_terms' => $data['payment_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        return $po;
    }

    public function createRelease(string $blanketPoId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $blanketPo = PurchaseOrder::findOrFail($blanketPoId);
        $totalAmount = 0.0;

        $release = PurchaseOrder::create([
            'tenant_id' => $blanketPo->tenant_id,
            'number' => $data['release_number'],
            'vendor_id' => $blanketPo->vendor_id,
            'creator_id' => $creatorId,
            'blanket_po_id' => $blanketPoId,
            'status' => 'draft',
            'po_type' => 'release',
            'total_amount' => 0,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        foreach ($data['lines'] as $index => $lineData) {
            $lineTotal = $lineData['quantity'] * $lineData['unit_price'];
            $totalAmount += $lineTotal;

            $lineReference = $release->number . '-L' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);

            PurchaseOrderLine::create([
                'purchase_order_id' => $release->id,
                'line_reference' => $lineReference,
                'line_number' => $index + 1,
                'item_code' => $lineData['item_code'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit' => $lineData['unit'],
                'unit_price' => $lineData['unit_price'],
                'line_total' => $lineTotal,
                'quantity_received' => 0,
                'notes' => $lineData['notes'] ?? null,
                'metadata' => $lineData['metadata'] ?? [],
            ]);
        }

        $release->update(['total_amount' => $totalAmount]);
        $release->load('lines');

        // Update blanket PO released value
        $blanketPo->increment('total_released_value', $totalAmount);

        return $release;
    }

    public function findById(string $id): ?PurchaseOrderInterface
    {
        return PurchaseOrder::with('lines')->find($id);
    }

    public function findByNumber(string $tenantId, string $number): ?PurchaseOrderInterface
    {
        return PurchaseOrder::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();
    }

    public function findByTenantId(string $tenantId, array $filters = []): array
    {
        $query = PurchaseOrder::with('lines')->where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['po_type'])) {
            $query->where('po_type', $filters['po_type']);
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    public function findByVendorId(string $tenantId, string $vendorId): array
    {
        return PurchaseOrder::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findLineByReference(string $lineReference): ?\Nexus\Procurement\Contracts\PurchaseOrderLineInterface
    {
        return PurchaseOrderLine::where('line_reference', $lineReference)->first();
    }

    public function updateStatus(string $id, string $status): PurchaseOrderInterface
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->update(['status' => $status]);
        $po->load('lines');

        return $po;
    }

    public function approve(string $id, string $approverId): PurchaseOrderInterface
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->update([
            'status' => 'approved',
            'approver_id' => $approverId,
            'approved_at' => now(),
        ]);
        $po->load('lines');

        return $po;
    }

    public function save(PurchaseOrderInterface $purchaseOrder): void
    {
        if ($purchaseOrder instanceof PurchaseOrder) {
            $purchaseOrder->save();
        }
    }

    public function delete(string $id): void
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->delete();
    }
}
