<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Analytics Query Definition Model
 * 
 * Stores analytics query definitions with model associations
 */
class AnalyticsQueryDefinition extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'analytics_query_definitions';

    protected $fillable = [
        'name',
        'type',
        'description',
        'model_type',
        'model_id',
        'parameters',
        'guards',
        'data_sources',
        'requires_transaction',
        'timeout',
        'supports_parallel_execution',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'guards' => 'array',
        'data_sources' => 'array',
        'requires_transaction' => 'boolean',
        'supports_parallel_execution' => 'boolean',
        'timeout' => 'integer',
    ];

    /**
     * Get all results for this query definition
     */
    public function results()
    {
        return $this->hasMany(AnalyticsQueryResult::class, 'query_id');
    }

    /**
     * Get permissions for this query
     */
    public function permissions()
    {
        return $this->hasMany(AnalyticsPermission::class, 'query_id');
    }
}
