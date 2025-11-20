<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Budget\Contracts\BudgetTransactionInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Currency\ValueObjects\Money;

/**
 * BudgetTransaction Eloquent Model
 * 
 * Implements BudgetTransactionInterface from Nexus\Budget package.
 * 
 * @property string $id
 * @property string $budget_id
 * @property TransactionType $transaction_type
 * @property string $source_document_id
 * @property string|null $source_document_type
 * @property float $amount
 * @property string $currency
 * @property string|null $line_item_description
 * @property string|null $account_id
 * @property string|null $cost_center_id
 * @property bool $is_released
 * @property \DateTimeInterface|null $released_at
 * @property string|null $released_by
 * @property string|null $reversed_by_transaction_id
 * @property bool $is_reversal
 * @property string|null $created_by
 */
class BudgetTransaction extends Model implements BudgetTransactionInterface
{
    use HasUlids;

    public $timestamps = true;
    const UPDATED_AT = null; // Transactions are immutable after creation

    protected $fillable = [
        'budget_id',
        'transaction_type',
        'source_document_id',
        'source_document_type',
        'amount',
        'currency',
        'line_item_description',
        'account_id',
        'cost_center_id',
        'is_released',
        'released_at',
        'released_by',
        'reversed_by_transaction_id',
        'is_reversal',
        'created_by',
    ];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'amount' => 'decimal:4',
        'is_released' => 'boolean',
        'is_reversal' => 'boolean',
        'released_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // BudgetTransactionInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getBudgetId(): string
    {
        return $this->budget_id;
    }

    public function getType(): TransactionType
    {
        return $this->transaction_type;
    }

    public function getAmount(): Money
    {
        return Money::of($this->amount, $this->currency);
    }

    public function getSourceDocumentId(): string
    {
        return $this->source_document_id;
    }

    public function getSourceDocumentType(): ?string
    {
        return $this->source_document_type;
    }

    public function getDescription(): ?string
    {
        return $this->line_item_description;
    }

    public function isReleased(): bool
    {
        return $this->is_released;
    }

    public function isReversal(): bool
    {
        return $this->is_reversal;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    // Eloquent Relationships

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    // Scopes

    public function scopeCommitments($query)
    {
        return $query->where('transaction_type', TransactionType::Commitment)
                     ->where('is_released', false);
    }

    public function scopeActuals($query)
    {
        return $query->where('transaction_type', TransactionType::Actual);
    }

    public function scopeForSourceDocument($query, string $sourceDocumentId)
    {
        return $query->where('source_document_id', $sourceDocumentId);
    }
}
