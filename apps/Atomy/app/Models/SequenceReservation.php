<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for sequence number reservations.
 *
 * @property string $id
 * @property string $sequence_id
 * @property string $reservation_id
 * @property string $number
 * @property string $status
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $finalized_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SequenceReservation extends Model
{
    protected $table = 'sequence_reservations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sequence_id',
        'reservation_id',
        'number',
        'status',
        'expires_at',
        'finalized_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'reserved',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SequenceReservation $reservation): void {
            if (empty($reservation->id)) {
                $reservation->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '<=', now());
    }
}
