<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConfigurationAuditCheckpoint Eloquent Model.
 * 
 * Represents a configuration audit checkpoint for compliance schemes.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $scheme_id
 * @property string $checkpoint_type
 * @property array $validation_rules
 * @property string $status
 * @property string|null $last_check_result
 * @property \DateTimeImmutable|null $last_checked_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read ComplianceScheme $scheme
 */
final class ConfigurationAuditCheckpoint extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configuration_audit_checkpoints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'scheme_id',
        'checkpoint_type',
        'validation_rules',
        'status',
        'last_check_result',
        'last_checked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validation_rules' => 'array',
        'last_checked_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this checkpoint.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the compliance scheme this checkpoint belongs to.
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ComplianceScheme::class, 'scheme_id');
    }
}
