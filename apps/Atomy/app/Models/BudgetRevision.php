<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Budget\Enums\BudgetStatus;

/**
 * BudgetRevision Eloquent Model
 * 
 * Audit trail for all budget changes.
 * 
 * @property string $id
 * @property string $budget_id
 * @property int $revision_number
 * @property float $previous_allocated_amount
 * @property float $new_allocated_amount
 * @property string $currency
 * @property BudgetStatus $previous_status
 * @property BudgetStatus $new_status
 * @property string $change_type
 * @property string $reason
 * @property string|null $justification
 * @property string|null $related_budget_id
 * @property string|null $workflow_approval_id
 * @property string|null $created_by
 */
class BudgetRevision extends Model
{
    use HasUlids;

    public $timestamps = false; // Only created_at
    protected $dates = ['created_at'];

    protected $fillable = [
        'budget_id',
        'revision_number',
        'previous_allocated_amount',
        'new_allocated_amount',
        'currency',
        'previous_status',
        'new_status',
        'change_type',
        'reason',
        'justification',
        'related_budget_id',
        'workflow_approval_id',
        'created_by',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'previous_allocated_amount' => 'decimal:4',
        'new_allocated_amount' => 'decimal:4',
        'previous_status' => BudgetStatus::class,
        'new_status' => BudgetStatus::class,
        'created_at' => 'datetime',
    ];

    // Eloquent Relationships

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function relatedBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'related_budget_id');
    }

    // Computed Accessors

    public function getAmountChangeAttribute(): float
    {
        return $this->new_allocated_amount - $this->previous_allocated_amount;
    }

    public function getAmountChangePercentageAttribute(): float
    {
        if ($this->previous_allocated_amount == 0) {
            return 0.0;
        }

        return (($this->new_allocated_amount - $this->previous_allocated_amount) / $this->previous_allocated_amount) * 100;
    }
}
