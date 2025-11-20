<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetForecast Eloquent Model
 * 
 * AI-powered budget predictions and projections.
 * 
 * @property string $id
 * @property string $budget_id
 * @property string $period_id
 * @property float $projected_spending
 * @property float $projected_variance
 * @property float $overrun_probability
 * @property float $confidence_lower_bound
 * @property float $confidence_upper_bound
 * @property float $certainty_score
 * @property string|null $model_version
 * @property array|null $model_features
 * @property \DateTimeInterface $forecast_date
 * @property \DateTimeInterface|null $valid_until
 * @property bool $is_active
 * @property string|null $generated_by
 */
class BudgetForecast extends Model
{
    use HasUlids;

    protected $fillable = [
        'budget_id',
        'period_id',
        'projected_spending',
        'projected_variance',
        'overrun_probability',
        'confidence_lower_bound',
        'confidence_upper_bound',
        'certainty_score',
        'model_version',
        'model_features',
        'forecast_date',
        'valid_until',
        'is_active',
        'generated_by',
    ];

    protected $casts = [
        'projected_spending' => 'decimal:4',
        'projected_variance' => 'decimal:4',
        'overrun_probability' => 'decimal:2',
        'confidence_lower_bound' => 'decimal:4',
        'confidence_upper_bound' => 'decimal:4',
        'certainty_score' => 'decimal:2',
        'model_features' => 'array',
        'forecast_date' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Eloquent Relationships

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('valid_until')
                           ->orWhere('valid_until', '>', now());
                     });
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('forecast_date', 'desc');
    }

    // Computed Accessors

    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_until === null) {
            return true;
        }

        return $this->valid_until > now();
    }

    public function getConfidenceIntervalWidthAttribute(): float
    {
        return $this->confidence_upper_bound - $this->confidence_lower_bound;
    }

    public function getRiskLevelAttribute(): string
    {
        if ($this->overrun_probability >= 80) {
            return 'critical';
        } elseif ($this->overrun_probability >= 60) {
            return 'high';
        } elseif ($this->overrun_probability >= 40) {
            return 'medium';
        }
        
        return 'low';
    }
}
