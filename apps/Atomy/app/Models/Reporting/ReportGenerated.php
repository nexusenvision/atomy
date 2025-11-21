<?php

declare(strict_types=1);

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Generated Report Eloquent Model
 *
 * Stores the immutable execution history of report generation.
 */
class ReportGenerated extends Model
{
    use HasUuids;

    protected $table = 'reports_generated';

    protected $fillable = [
        'report_definition_id',
        'format',
        'file_path',
        'file_size_bytes',
        'retention_tier',
        'generated_at',
        'duration_ms',
        'is_successful',
        'error',
        'query_result_id',
        'generated_by',
        'tenant_id',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'is_successful' => 'boolean',
        'file_size_bytes' => 'integer',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the report definition for this generated report.
     */
    public function definition()
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }

    /**
     * Get distribution logs for this report.
     */
    public function distributionLogs()
    {
        return $this->hasMany(ReportDistributionLog::class, 'report_generated_id');
    }

    /**
     * Scope to filter by retention tier.
     */
    public function scopeByRetentionTier($query, string $tier)
    {
        return $query->where('retention_tier', $tier);
    }

    /**
     * Scope to filter by successful generation.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope to filter by failed generation.
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }
}
