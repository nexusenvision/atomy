<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EventProjection Model
 *
 * Eloquent model for tracking projection state and progress.
 *
 * Requirements satisfied:
 * - ARC-EVS-7006: All Eloquent models in application layer
 * - FUN-EVS-7218: Resume projection from last processed event on failure/restart
 *
 * @property int $id
 * @property string $projector_name
 * @property string|null $last_processed_event_id
 * @property int $processed_count
 * @property \Illuminate\Support\Carbon|null $last_processed_at
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $last_error_at
 * @property string|null $tenant_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class EventProjection extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'event_projections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'projector_name',
        'last_processed_event_id',
        'processed_count',
        'last_processed_at',
        'status',
        'error_message',
        'last_error_at',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed_count' => 'integer',
        'last_processed_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];
}
