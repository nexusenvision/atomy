<?php

declare(strict_types=1);

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Report Distribution Log Eloquent Model
 *
 * Tracks delivery status for each recipient of a distributed report.
 */
class ReportDistributionLog extends Model
{
    use HasUuids;

    protected $table = 'reports_distribution_log';

    protected $fillable = [
        'report_generated_id',
        'recipient_id',
        'notification_id',
        'channel_type',
        'status',
        'delivered_at',
        'error',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the generated report for this distribution.
     */
    public function generatedReport()
    {
        return $this->belongsTo(ReportGenerated::class, 'report_generated_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by channel type.
     */
    public function scopeByChannel($query, string $channelType)
    {
        return $query->where('channel_type', $channelType);
    }

    /**
     * Scope to get failed distributions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get successful distributions.
     */
    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'read']);
    }
}
