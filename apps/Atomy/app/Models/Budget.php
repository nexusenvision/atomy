<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetingMethodology;
use Nexus\Budget\Enums\RolloverPolicy;
use Nexus\Currency\ValueObjects\Money;

/**
 * Budget Eloquent Model
 * 
 * Implements BudgetInterface from Nexus\Budget package.
 * 
 * @property string $id
 * @property string $name
 * @property string $period_id
 * @property BudgetType $budget_type
 * @property BudgetStatus $status
 * @property float $allocated_amount_functional
 * @property string $functional_currency
 * @property float|null $allocated_amount_presentation
 * @property string|null $presentation_currency
 * @property float|null $exchange_rate_snapshot
 * @property float $committed_amount
 * @property float $actual_amount
 * @property float $available_amount (computed)
 * @property string|null $department_id
 * @property string|null $project_id
 * @property string|null $account_id
 * @property string|null $parent_budget_id
 * @property int $hierarchy_level
 * @property RolloverPolicy $rollover_policy
 * @property BudgetingMethodology $budgeting_methodology
 * @property string|null $base_budget_id
 * @property bool $is_simulation
 * @property string|null $created_by
 * @property string|null $updated_by
 */
class Budget extends Model implements BudgetInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'period_id',
        'budget_type',
        'status',
        'allocated_amount_functional',
        'functional_currency',
        'allocated_amount_presentation',
        'presentation_currency',
        'exchange_rate_snapshot',
        'committed_amount',
        'actual_amount',
        'department_id',
        'project_id',
        'account_id',
        'parent_budget_id',
        'hierarchy_level',
        'rollover_policy',
        'budgeting_methodology',
        'base_budget_id',
        'is_simulation',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget_type' => BudgetType::class,
        'status' => BudgetStatus::class,
        'rollover_policy' => RolloverPolicy::class,
        'budgeting_methodology' => BudgetingMethodology::class,
        'allocated_amount_functional' => 'decimal:4',
        'allocated_amount_presentation' => 'decimal:4',
        'exchange_rate_snapshot' => 'decimal:6',
        'committed_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'available_amount' => 'decimal:4',
        'hierarchy_level' => 'integer',
        'is_simulation' => 'boolean',
    ];

    // BudgetInterface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPeriodId(): string
    {
        return $this->period_id;
    }

    public function getType(): BudgetType
    {
        return $this->budget_type;
    }

    public function getStatus(): BudgetStatus
    {
        return $this->status;
    }

    public function getAllocatedAmount(): Money
    {
        return Money::of($this->allocated_amount_functional, $this->functional_currency);
    }

    public function getCommittedAmount(): Money
    {
        return Money::of($this->committed_amount, $this->functional_currency);
    }

    public function getActualAmount(): Money
    {
        return Money::of($this->actual_amount, $this->functional_currency);
    }

    public function getAvailableAmount(): Money
    {
        return Money::of($this->available_amount, $this->functional_currency);
    }

    public function getCurrency(): string
    {
        return $this->functional_currency;
    }

    public function getPresentationAmount(): ?Money
    {
        if ($this->allocated_amount_presentation === null || $this->presentation_currency === null) {
            return null;
        }

        return Money::of($this->allocated_amount_presentation, $this->presentation_currency);
    }

    public function getExchangeRate(): ?float
    {
        return $this->exchange_rate_snapshot;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getProjectId(): ?string
    {
        return $this->project_id;
    }

    public function getAccountId(): ?string
    {
        return $this->account_id;
    }

    public function getParentBudgetId(): ?string
    {
        return $this->parent_budget_id;
    }

    public function getHierarchyLevel(): int
    {
        return $this->hierarchy_level;
    }

    public function getRolloverPolicy(): RolloverPolicy
    {
        return $this->rollover_policy;
    }

    public function getMethodology(): BudgetingMethodology
    {
        return $this->budgeting_methodology;
    }

    public function isRevenueBudget(): bool
    {
        return $this->budget_type === BudgetType::Revenue;
    }

    public function isSimulation(): bool
    {
        return $this->is_simulation;
    }

    public function getBaseBudgetId(): ?string
    {
        return $this->base_budget_id;
    }

    // Eloquent Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'parent_budget_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Budget::class, 'parent_budget_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'budget_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BudgetRevision::class, 'budget_id');
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(BudgetForecast::class, 'budget_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(BudgetUtilizationAlert::class, 'budget_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', BudgetStatus::Active);
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeNotSimulation($query)
    {
        return $query->where('is_simulation', false);
    }

    // Computed Accessors

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_amount_functional == 0) {
            return 0.0;
        }

        return (($this->actual_amount + $this->committed_amount) / $this->allocated_amount_functional) * 100;
    }
}
