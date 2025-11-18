<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Compliance\Contracts\ComplianceSchemeInterface;

/**
 * ComplianceScheme Eloquent Model.
 * 
 * Implements the Nexus\Compliance\Contracts\ComplianceSchemeInterface
 * for the Atomy application using Laravel Eloquent ORM.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $scheme_name
 * @property string|null $description
 * @property bool $is_active
 * @property array $configuration
 * @property \DateTimeImmutable $activated_at
 * @property \DateTimeImmutable|null $deactivated_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<ConfigurationAuditCheckpoint> $checkpoints
 */
final class ComplianceScheme extends Model implements ComplianceSchemeInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'compliance_schemes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'scheme_name',
        'description',
        'is_active',
        'configuration',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
        'activated_at' => 'immutable_datetime',
        'deactivated_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this compliance scheme.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all configuration audit checkpoints for this scheme.
     */
    public function checkpoints(): HasMany
    {
        return $this->hasMany(ConfigurationAuditCheckpoint::class, 'scheme_id');
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->scheme_name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getActivatedAt(): \DateTimeImmutable
    {
        return $this->activated_at;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeactivatedAt(): ?\DateTimeImmutable
    {
        return $this->deactivated_at;
    }

    /**
     * {@inheritDoc}
     */
    public function activate(): void
    {
        $this->is_active = true;
        $this->activated_at = new \DateTimeImmutable();
        $this->deactivated_at = null;
        $this->save();
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->deactivated_at = new \DateTimeImmutable();
        $this->save();
    }
}
