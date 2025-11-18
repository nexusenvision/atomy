<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StatutoryRateTable Eloquent Model.
 * 
 * Stores statutory rate tables for country-specific calculations
 * (e.g., tax rates, social security rates).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $country_code
 * @property string $deduction_type
 * @property \DateTimeImmutable $effective_from
 * @property \DateTimeImmutable|null $effective_to
 * @property array $rate_config
 * @property bool $is_active
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 */
final class StatutoryRateTable extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statutory_rate_tables';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'country_code',
        'deduction_type',
        'effective_from',
        'effective_to',
        'rate_config',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'effective_from' => 'immutable_datetime',
        'effective_to' => 'immutable_datetime',
        'rate_config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this rate table.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
