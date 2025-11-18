<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StatutoryReportInstance Eloquent Model.
 * 
 * Represents a specific instance/version of a statutory report.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $report_id
 * @property int $version
 * @property string $generated_by
 * @property string $checksum
 * @property string|null $file_path
 * @property array|null $metadata
 * @property \DateTimeImmutable $generated_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read StatutoryReport $report
 */
final class StatutoryReportInstance extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statutory_report_instances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'report_id',
        'version',
        'generated_by',
        'checksum',
        'file_path',
        'metadata',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'version' => 'integer',
        'metadata' => 'array',
        'generated_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this report instance.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the report this instance belongs to.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(StatutoryReport::class, 'report_id');
    }
}
