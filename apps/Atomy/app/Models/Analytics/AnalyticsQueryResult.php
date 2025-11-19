<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Analytics Query Result Model
 * 
 * Stores execution history and results
 */
class AnalyticsQueryResult extends Model
{
    use HasUuids;

    protected $table = 'analytics_query_results';

    protected $fillable = [
        'query_id',
        'query_name',
        'model_type',
        'model_id',
        'executed_by',
        'executed_at',
        'duration_ms',
        'is_successful',
        'error',
        'result_data',
        'metadata',
        'tenant_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'is_successful' => 'boolean',
        'result_data' => 'array',
        'metadata' => 'array',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the query definition
     */
    public function queryDefinition()
    {
        return $this->belongsTo(AnalyticsQueryDefinition::class, 'query_id');
    }
}
