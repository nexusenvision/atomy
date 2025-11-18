<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Nexus\EventStream\Contracts\EventInterface;

/**
 * EventStream Model
 *
 * Eloquent model representing events in the event store.
 * Implements EventInterface from the EventStream package.
 *
 * Requirements satisfied:
 * - ARC-EVS-7006: All Eloquent models in application layer implementing package interfaces
 * - BUS-EVS-7103: Events are immutable (no updates/deletes allowed)
 * - BUS-EVS-7104: Each event contains aggregate ID, event type, version, timestamp, payload
 *
 * @property string $event_id
 * @property string $aggregate_id
 * @property string $aggregate_type
 * @property int $version
 * @property string $event_type
 * @property array $payload
 * @property array|null $metadata
 * @property string|null $causation_id
 * @property string|null $correlation_id
 * @property string $tenant_id
 * @property string|null $user_id
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class EventStream extends Model implements EventInterface
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'event_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'aggregate_id',
        'aggregate_type',
        'version',
        'event_type',
        'payload',
        'metadata',
        'causation_id',
        'correlation_id',
        'tenant_id',
        'user_id',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'version' => 'integer',
    ];

    /**
     * Events cannot be updated (immutable)
     */
    public static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new \RuntimeException('Events are immutable and cannot be updated');
        });

        static::deleting(function () {
            throw new \RuntimeException('Events are immutable and cannot be deleted');
        });
    }

    // EventInterface implementation

    public function getEventId(): string
    {
        return $this->event_id;
    }

    public function getAggregateId(): string
    {
        return $this->aggregate_id;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->occurred_at);
    }

    public function getPayload(): array
    {
        return $this->payload ?? [];
    }

    public function getCausationId(): ?string
    {
        return $this->causation_id;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlation_id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
