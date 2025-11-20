<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;

final class GoodsReceiptLine extends Model implements GoodsReceiptLineInterface
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'goods_receipt_note_id',
        'line_number',
        'po_line_reference',
        'quantity_received',
        'unit',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'quantity_received' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'po_line_reference', 'line_reference');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGoodsReceiptNoteId(): string
    {
        return $this->goods_receipt_note_id;
    }

    public function getLineNumber(): int
    {
        return $this->line_number;
    }

    public function getPoLineReference(): string
    {
        return $this->po_line_reference;
    }

    public function getLineReference(): string
    {
        return $this->po_line_reference;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity_received;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getUom(): string
    {
        return $this->unit;
    }

    public function getItemDescription(): string
    {
        return $this->purchaseOrderLine->getItemDescription();
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
