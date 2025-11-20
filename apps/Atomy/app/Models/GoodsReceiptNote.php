<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;

final class GoodsReceiptNote extends Model implements GoodsReceiptNoteInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'purchase_order_id',
        'receiver_id',
        'received_date',
        'status',
        'warehouse_location',
        'notes',
        'payment_authorizer_id',
        'payment_authorized_at',
        'metadata',
    ];

    protected $casts = [
        'received_date' => 'date',
        'payment_authorized_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function getGrnNumber(): string
    {
        return $this->number;
    }

    public function getPurchaseOrderId(): string
    {
        return $this->purchase_order_id;
    }

    public function getReceiverId(): string
    {
        return $this->receiver_id;
    }

    public function getReceivedBy(): string
    {
        return $this->receiver_id;
    }

    public function getReceivedDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->received_date);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getWarehouseLocation(): ?string
    {
        return $this->warehouse_location;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPaymentAuthorizerId(): ?string
    {
        return $this->payment_authorizer_id;
    }

    public function getPaymentAuthorizedAt(): ?\DateTimeImmutable
    {
        return $this->payment_authorized_at ? \DateTimeImmutable::createFromMutable($this->payment_authorized_at) : null;
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
