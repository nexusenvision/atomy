<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Procurement\Contracts\VendorQuoteInterface;

final class VendorQuote extends Model implements VendorQuoteInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'rfq_number',
        'requisition_id',
        'vendor_id',
        'quote_reference',
        'quoted_date',
        'valid_until',
        'status',
        'payment_terms',
        'delivery_terms',
        'notes',
        'accepted_by',
        'accepted_at',
        'rejection_reason',
        'lines',
        'metadata',
    ];

    protected $casts = [
        'quoted_date' => 'date',
        'valid_until' => 'date',
        'accepted_at' => 'datetime',
        'lines' => 'array',
        'metadata' => 'array',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getRfqNumber(): string
    {
        return $this->rfq_number;
    }

    public function getRequisitionId(): string
    {
        return $this->requisition_id;
    }

    public function getVendorId(): string
    {
        return $this->vendor_id;
    }

    public function getQuoteReference(): string
    {
        return $this->quote_reference;
    }

    public function getQuotedDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->quoted_date);
    }

    public function getValidUntil(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->valid_until);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLines(): array
    {
        return $this->lines ?? [];
    }

    public function getPaymentTerms(): ?string
    {
        return $this->payment_terms;
    }

    public function getDeliveryTerms(): ?string
    {
        return $this->delivery_terms;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getAcceptedBy(): ?string
    {
        return $this->accepted_by;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->accepted_at ? \DateTimeImmutable::createFromMutable($this->accepted_at) : null;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
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
