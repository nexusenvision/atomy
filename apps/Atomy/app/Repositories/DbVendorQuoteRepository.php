<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\VendorQuote;
use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;

final readonly class DbVendorQuoteRepository implements VendorQuoteRepositoryInterface
{
    public function create(string $tenantId, string $requisitionId, array $data): VendorQuoteInterface
    {
        return VendorQuote::create([
            'tenant_id' => $tenantId,
            'rfq_number' => $data['rfq_number'],
            'requisition_id' => $requisitionId,
            'vendor_id' => $data['vendor_id'],
            'quote_reference' => $data['quote_reference'],
            'quoted_date' => $data['quoted_date'],
            'valid_until' => $data['valid_until'],
            'status' => 'pending',
            'payment_terms' => $data['payment_terms'] ?? null,
            'delivery_terms' => $data['delivery_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'lines' => $data['lines'] ?? [],
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function findById(string $id): ?VendorQuoteInterface
    {
        return VendorQuote::find($id);
    }

    public function findByRfqNumber(string $rfqNumber): array
    {
        return VendorQuote::where('rfq_number', $rfqNumber)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByRequisitionId(string $requisitionId): array
    {
        return VendorQuote::where('requisition_id', $requisitionId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function findByVendorId(string $tenantId, string $vendorId): array
    {
        return VendorQuote::where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function accept(string $id, string $acceptorId): VendorQuoteInterface
    {
        $quote = VendorQuote::findOrFail($id);
        $quote->update([
            'status' => 'accepted',
            'accepted_by' => $acceptorId,
            'accepted_at' => now(),
        ]);

        return $quote;
    }

    public function reject(string $id, string $reason): VendorQuoteInterface
    {
        $quote = VendorQuote::findOrFail($id);
        $quote->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $quote;
    }

    public function save(VendorQuoteInterface $quote): void
    {
        if ($quote instanceof VendorQuote) {
            $quote->save();
        }
    }

    public function delete(string $id): void
    {
        $quote = VendorQuote::findOrFail($id);
        $quote->delete();
    }
}
