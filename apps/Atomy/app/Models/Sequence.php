<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sequencing\Contracts\SequenceInterface;

/**
 * Eloquent model for sequence definitions.
 *
 * @property string $id
 * @property string $name
 * @property string|null $scope_identifier
 * @property string $pattern
 * @property string $reset_period
 * @property int $step_size
 * @property int|null $reset_limit
 * @property string $gap_policy
 * @property string $overflow_behavior
 * @property int $exhaustion_threshold
 * @property bool $is_locked
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Sequence extends Model implements SequenceInterface
{
    protected $table = 'sequences';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'scope_identifier',
        'pattern',
        'reset_period',
        'step_size',
        'reset_limit',
        'gap_policy',
        'overflow_behavior',
        'exhaustion_threshold',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'step_size' => 'integer',
        'reset_limit' => 'integer',
        'exhaustion_threshold' => 'integer',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'reset_period' => 'never',
        'step_size' => 1,
        'gap_policy' => 'allow_gaps',
        'overflow_behavior' => 'throw_exception',
        'exhaustion_threshold' => 90,
        'is_locked' => false,
        'is_active' => true,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Sequence $sequence): void {
            if (empty($sequence->id)) {
                $sequence->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    // Relationships

    public function counter(): HasMany
    {
        return $this->hasMany(SequenceCounter::class, 'sequence_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SequenceReservation::class, 'sequence_id');
    }

    public function gaps(): HasMany
    {
        return $this->hasMany(SequenceGap::class, 'sequence_id');
    }

    // SequenceInterface implementation

    public function getName(): string
    {
        return $this->name;
    }

    public function getScopeIdentifier(): ?string
    {
        return $this->scope_identifier;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getResetPeriod(): string
    {
        return $this->reset_period;
    }

    public function getStepSize(): int
    {
        return $this->step_size;
    }

    public function getResetLimit(): ?int
    {
        return $this->reset_limit;
    }

    public function getGapPolicy(): string
    {
        return $this->gap_policy;
    }

    public function getOverflowBehavior(): string
    {
        return $this->overflow_behavior;
    }

    public function getExhaustionThreshold(): int
    {
        return $this->exhaustion_threshold;
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
