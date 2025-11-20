<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;

final class PurchaseOrder extends Model implements PurchaseOrderInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'vendor_id',
        'creator_id',
        'requisition_id',
        'status',
        'po_type',
        'blanket_po_id',
        'total_amount',
        'total_committed_value',
        'total_released_value',
        'expected_delivery_date',
        'valid_from',
        'valid_until',
        'payment_terms',
        'notes',
        'approver_id',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'total_committed_value' => 'decimal:4',
        'total_released_value' => 'decimal:4',
        'expected_delivery_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function blanketPo(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'blanket_po_id');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'blanket_po_id');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getPoNumber(): string
    {
        return $this->number;
    }

    public function getVendorId(): string
    {
        return $this->vendor_id;
    }

    public function getCreatorId(): string
    {
        return $this->creator_id;
    }

    public function getRequisitionId(): ?string
    {
        return $this->requisition_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPoType(): string
    {
        return $this->po_type;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->total_amount;
    }
    public function getCurrency(): string
    {
        return $this->currency ?? 'MYR'; // Default to MYR if null
    }
    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getExpectedDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->expected_delivery_date ? \DateTimeImmutable::createFromMutable($this->expected_delivery_date) : null;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->payment_terms;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getApproverId(): ?string
    {
        return $this->approver_id;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approved_at ? \DateTimeImmutable::createFromMutable($this->approved_at) : null;
    }

    public function getBlanketPoId(): ?string
    {
        return $this->blanket_po_id;
    }

    public function getTotalCommittedValue(): ?float
    {
        return $this->total_committed_value ? (float) $this->total_committed_value : null;
    }

    public function getTotalReleasedValue(): ?float
    {
        return $this->total_released_value ? (float) $this->total_released_value : null;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->valid_from ? \DateTimeImmutable::createFromMutable($this->valid_from) : null;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->valid_until ? \DateTimeImmutable::createFromMutable($this->valid_until) : null;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->released_at ? \DateTimeImmutable::createFromMutable($this->released_at) : null;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->updated_at);
    }
}
