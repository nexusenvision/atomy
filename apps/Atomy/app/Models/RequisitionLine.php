<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Procurement\Contracts\RequisitionLineInterface;

final class RequisitionLine extends Model implements RequisitionLineInterface
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'requisition_id',
        'line_number',
        'item_code',
        'description',
        'quantity',
        'unit',
        'estimated_unit_price',
        'line_total',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity' => 'decimal:4',
        'estimated_unit_price' => 'decimal:4',
        'line_total' => 'decimal:4',
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

    public function getRequisitionId(): string
    {
        return $this->requisition_id;
    }

    public function getLineNumber(): int
    {
        return $this->line_number;
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

    public function getEstimatedUnitPrice(): float
    {
        return (float) $this->estimated_unit_price;
    }

    public function getUnitPriceEstimate(): float
    {
        return (float) $this->estimated_unit_price;
    }

    public function getLineTotal(): float
    {
        return (float) $this->line_total;
    }

    public function getTotalEstimate(): float
    {
        return (float) $this->line_total;
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
