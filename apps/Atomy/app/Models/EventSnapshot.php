<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Nexus\EventStream\Contracts\SnapshotInterface;

/**
 * EventSnapshot Model
 *
 * Eloquent model representing aggregate snapshots.
 * Implements SnapshotInterface from the EventStream package.
 *
 * Requirements satisfied:
 * - ARC-EVS-7006: All Eloquent models in application layer implementing package interfaces
 * - BUS-EVS-7106: Snapshots created periodically to optimize replay performance
 * - REL-EVS-7406: Snapshots validated before use (checksum verification)
 *
 * @property int $id
 * @property string $aggregate_id
 * @property string $aggregate_type
 * @property int $version
 * @property array $state
 * @property string $checksum
 * @property string $tenant_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class EventSnapshot extends Model implements SnapshotInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'event_snapshots';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'aggregate_id',
        'aggregate_type',
        'version',
        'state',
        'checksum',
        'tenant_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'state' => 'array',
        'version' => 'integer',
    ];

    // SnapshotInterface implementation

    public function getAggregateId(): string
    {
        return $this->aggregate_id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getState(): array
    {
        return $this->state ?? [];
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
