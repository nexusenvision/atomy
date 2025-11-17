<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for sequence counter state.
 *
 * @property string $id
 * @property string $sequence_id
 * @property int $current_value
 * @property int $generation_count
 * @property \Illuminate\Support\Carbon|null $last_reset_at
 * @property \Illuminate\Support\Carbon|null $last_generated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SequenceCounter extends Model
{
    protected $table = 'sequence_counters';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sequence_id',
        'current_value',
        'generation_count',
        'last_reset_at',
        'last_generated_at',
    ];

    protected $casts = [
        'current_value' => 'integer',
        'generation_count' => 'integer',
        'last_reset_at' => 'datetime',
        'last_generated_at' => 'datetime',
    ];

    protected $attributes = [
        'current_value' => 0,
        'generation_count' => 0,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SequenceCounter $counter): void {
            if (empty($counter->id)) {
                $counter->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }
}
