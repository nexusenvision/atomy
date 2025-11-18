<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\ValueObjects\ReportFormat;

/**
 * StatutoryReport Eloquent Model.
 * 
 * Implements the Nexus\Statutory\Contracts\StatutoryReportInterface
 * for the Atomy application using Laravel Eloquent ORM.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $report_type
 * @property \DateTimeImmutable $start_date
 * @property \DateTimeImmutable $end_date
 * @property string $format
 * @property string $status
 * @property string|null $file_path
 * @property string|null $generated_by
 * @property \DateTimeImmutable|null $generated_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<StatutoryReportInstance> $instances
 */
final class StatutoryReport extends Model implements StatutoryReportInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statutory_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'report_type',
        'start_date',
        'end_date',
        'format',
        'status',
        'file_path',
        'generated_by',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'immutable_datetime',
        'end_date' => 'immutable_datetime',
        'generated_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns this report.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all instances for this report.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(StatutoryReportInstance::class, 'report_id');
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
    public function getReportType(): string
    {
        return $this->report_type;
    }

    /**
     * {@inheritDoc}
     */
    public function getStartDate(): \DateTimeImmutable
    {
        return $this->start_date;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDate(): \DateTimeImmutable
    {
        return $this->end_date;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormat(): ReportFormat
    {
        return ReportFormat::from($this->format);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeneratedBy(): ?string
    {
        return $this->generated_by;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generated_at;
    }
}
