<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Str;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;

final readonly class DbGoodsReceiptNoteRepository implements GoodsReceiptRepositoryInterface
{
    public function generateNextNumber(string $tenantId): string
    {
        // Placeholder: Returns ULID until Nexus\Sequencing integration is implemented
        return 'GRN-' . strtoupper((string) Str::ulid());
    }

    public function findByPurchaseOrder(string $purchaseOrderId): array
    {
        return GoodsReceiptNote::where('purchase_order_id', $purchaseOrderId)
            ->with('lines')
            ->get()
            ->all();
    }

    public function create(string $tenantId, string $purchaseOrderId, string $receiverId, array $data): GoodsReceiptNoteInterface
    {
        $grn = GoodsReceiptNote::create([
            'tenant_id' => $tenantId,
            'number' => $data['number'],
            'purchase_order_id' => $purchaseOrderId,
            'receiver_id' => $receiverId,
            'received_date' => $data['received_date'],
            'status' => 'draft',
            'warehouse_location' => $data['warehouse_location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        foreach ($data['lines'] as $index => $lineData) {
            GoodsReceiptLine::create([
                'goods_receipt_note_id' => $grn->id,
                'line_number' => $index + 1,
                'po_line_reference' => $lineData['po_line_reference'],
                'quantity_received' => $lineData['quantity_received'],
                'unit' => $lineData['unit'],
                'notes' => $lineData['notes'] ?? null,
                'metadata' => $lineData['metadata'] ?? [],
            ]);

            // Update PO line quantity received
            $poLine = PurchaseOrderLine::where('line_reference', $lineData['po_line_reference'])->first();
            if ($poLine) {
                $poLine->increment('quantity_received', $lineData['quantity_received']);
            }
        }

        $grn->load('lines');

        return $grn;
    }

    public function findById(string $id): ?GoodsReceiptNoteInterface
    {
        return GoodsReceiptNote::with('lines')->find($id);
    }

    public function findByNumber(string $tenantId, string $number): ?GoodsReceiptNoteInterface
    {
        return GoodsReceiptNote::with('lines')
            ->where('tenant_id', $tenantId)
            ->where('number', $number)
            ->first();
    }

    public function findByTenantId(string $tenantId, array $filters = []): array
    {
        $query = GoodsReceiptNote::with('lines')->where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    public function findByPurchaseOrderId(string $purchaseOrderId): array
    {
        return GoodsReceiptNote::with('lines')
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findLineByReference(string $poLineReference): ?\Nexus\Procurement\Contracts\GoodsReceiptLineInterface
    {
        return GoodsReceiptLine::where('po_line_reference', $poLineReference)->first();
    }

    public function authorizePayment(string $id, string $authorizerId): GoodsReceiptNoteInterface
    {
        $grn = GoodsReceiptNote::findOrFail($id);
        $grn->update([
            'status' => 'payment_authorized',
            'payment_authorizer_id' => $authorizerId,
            'payment_authorized_at' => now(),
        ]);
        $grn->load('lines');

        return $grn;
    }

    public function save(GoodsReceiptNoteInterface $goodsReceiptNote): void
    {
        if ($goodsReceiptNote instanceof GoodsReceiptNote) {
            $goodsReceiptNote->save();
        }
    }

    public function delete(string $id): void
    {
        $grn = GoodsReceiptNote::findOrFail($id);
        $grn->delete();
    }
}
