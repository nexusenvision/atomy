<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Compliance\Contracts\SodViolationInterface;

/**
 * SodViolation Eloquent Model.
 * 
 * Implements the Nexus\Compliance\Contracts\SodViolationInterface
 * for the Atomy application using Laravel Eloquent ORM.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $rule_id
 * @property string $transaction_id
 * @property string $transaction_type
 * @property string $creator_id
 * @property string $approver_id
 * @property string|null $violation_details
 * @property \DateTimeImmutable $violated_at
 * @property bool $is_resolved
 * @property \DateTimeImmutable|null $resolved_at
 * @property string|null $resolution_notes
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read SodRule $rule
 */
final class SodViolation extends Model implements SodViolationInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sod_violations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'rule_id',
        'transaction_id',
        'transaction_type',
        'creator_id',
        'approver_id',
        'violation_details',
        'violated_at',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'violated_at' => 'immutable_datetime',
        'is_resolved' => 'boolean',
        'resolved_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this SOD violation.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the rule that was violated.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(SodRule::class, 'rule_id');
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
    public function getRuleId(): string
    {
        return $this->rule_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
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
    public function getCreatorId(): string
    {
        return $this->creator_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getApproverId(): string
    {
        return $this->approver_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getViolationDetails(): ?string
    {
        return $this->violation_details;
    }

    /**
     * {@inheritDoc}
     */
    public function getViolatedAt(): \DateTimeImmutable
    {
        return $this->violated_at;
    }

    /**
     * {@inheritDoc}
     */
    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    /**
     * {@inheritDoc}
     */
    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolved_at;
    }

    /**
     * {@inheritDoc}
     */
    public function getResolutionNotes(): ?string
    {
        return $this->resolution_notes;
    }

    /**
     * {@inheritDoc}
     */
    public function markResolved(string $notes): void
    {
        $this->is_resolved = true;
        $this->resolved_at = new \DateTimeImmutable();
        $this->resolution_notes = $notes;
        $this->save();
    }
}
