<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Compliance\Contracts\SodRuleInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;

/**
 * SodRule Eloquent Model.
 * 
 * Implements the Nexus\Compliance\Contracts\SodRuleInterface
 * for the Atomy application using Laravel Eloquent ORM.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $rule_name
 * @property string|null $description
 * @property string $transaction_type
 * @property string $severity_level
 * @property string|null $creator_role
 * @property string|null $approver_role
 * @property string|null $delegation_chain
 * @property array $constraints
 * @property bool $is_active
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<SodViolation> $violations
 */
final class SodRule extends Model implements SodRuleInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sod_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'rule_name',
        'description',
        'transaction_type',
        'severity_level',
        'creator_role',
        'approver_role',
        'delegation_chain',
        'constraints',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'constraints' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this SOD rule.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all violations for this SOD rule.
     */
    public function violations(): HasMany
    {
        return $this->hasMany(SodViolation::class, 'rule_id');
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
    public function getRuleName(): string
    {
        return $this->rule_name;
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
    public function getTransactionType(): string
    {
        return $this->transaction_type;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityLevel(): SeverityLevel
    {
        return SeverityLevel::from($this->severity_level);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatorRole(): ?string
    {
        return $this->creator_role;
    }

    /**
     * {@inheritDoc}
     */
    public function getApproverRole(): ?string
    {
        return $this->approver_role;
    }

    /**
     * {@inheritDoc}
     */
    public function getDelegationChain(): ?array
    {
        if ($this->delegation_chain === null) {
            return null;
        }

        return is_string($this->delegation_chain) 
            ? json_decode($this->delegation_chain, true) 
            : $this->delegation_chain;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraints(): array
    {
        return $this->constraints ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
