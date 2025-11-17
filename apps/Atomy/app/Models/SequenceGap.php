<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for tracking gaps in sequences.
 *
 * @property string $id
 * @property string $sequence_id
 * @property string $number
 * @property string $status
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $filled_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SequenceGap extends Model
{
    protected $table = 'sequence_gaps';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sequence_id',
        'number',
        'status',
        'reason',
        'filled_at',
    ];

    protected $casts = [
        'filled_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'unfilled',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SequenceGap $gap): void {
            if (empty($gap->id)) {
                $gap->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }

    public function scopeUnfilled($query)
    {
        return $query->where('status', 'unfilled');
    }

    public function scopeFilled($query)
    {
        return $query->where('status', 'filled');
    }
}
