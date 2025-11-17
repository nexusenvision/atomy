<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for pattern versions.
 *
 * @property string $id
 * @property string $sequence_id
 * @property string $pattern
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SequencePatternVersion extends Model
{
    protected $table = 'sequence_pattern_versions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sequence_id',
        'pattern',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SequencePatternVersion $version): void {
            if (empty($version->id)) {
                $version->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }

    public function scopeActiveOn($query, \DateTimeInterface $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>', $date);
            });
    }
}
