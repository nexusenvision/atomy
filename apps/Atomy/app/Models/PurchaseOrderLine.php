<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;

final class PurchaseOrderLine extends Model implements PurchaseOrderLineInterface
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'purchase_order_id',
        'line_reference',
        'line_number',
        'requisition_line_id',
        'item_code',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'quantity_received',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function requisitionLine(): BelongsTo
    {
        return $this->belongsTo(RequisitionLine::class);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPurchaseOrderId(): string
    {
        return $this->purchase_order_id;
    }

    public function getLineReference(): string
    {
        return $this->line_reference;
    }

    public function getLineNumber(): int
    {
        return $this->line_number;
    }

    public function getRequisitionLineId(): ?string
    {
        return $this->requisition_line_id;
    }

    public function getItemCode(): string
    {
        return $this->item_code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getItemDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getUom(): string
    {
        return $this->unit;
    }

    public function getUnitPrice(): float
    {
        return (float) $this->unit_price;
    }

    public function getLineTotal(): float
    {
        return (float) $this->line_total;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->line_total;
    }

    public function getReceivedQuantity(): float
    {
        return (float) $this->quantity_received;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
