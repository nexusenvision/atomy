<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionLineInterface;

final class Requisition extends Model implements RequisitionInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'requester_id',
        'description',
        'department',
        'status',
        'total_estimate',
        'approver_id',
        'approved_at',
        'rejector_id',
        'rejected_at',
        'rejection_reason',
        'is_converted',
        'converted_po_id',
        'converted_at',
        'metadata',
    ];

    protected $casts = [
        'total_estimate' => 'decimal:4',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'is_converted' => 'boolean',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(RequisitionLine::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_po_id');
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

    public function getRequisitionNumber(): string
    {
        return $this->number;
    }

    public function getRequesterId(): string
    {
        return $this->requester_id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalEstimate(): float
    {
        return (float) $this->total_estimate;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getApproverId(): ?string
    {
        return $this->approver_id;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approver_id;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approved_at ? \DateTimeImmutable::createFromMutable($this->approved_at) : null;
    }

    public function getRejectorId(): ?string
    {
        return $this->rejector_id;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejected_at ? \DateTimeImmutable::createFromMutable($this->rejected_at) : null;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    public function isConverted(): bool
    {
        return $this->is_converted;
    }

    public function getConvertedPoId(): ?string
    {
        return $this->converted_po_id;
    }

    public function getConvertedAt(): ?\DateTimeImmutable
    {
        return $this->converted_at ? \DateTimeImmutable::createFromMutable($this->converted_at) : null;
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
