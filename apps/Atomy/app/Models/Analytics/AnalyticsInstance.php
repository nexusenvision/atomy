<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Analytics Instance Model
 * 
 * One analytics instance per model instance
 * Satisfies: BUS-ANA-0141
 */
class AnalyticsInstance extends Model
{
    use HasUuids;

    protected $table = 'analytics_instances';

    protected $fillable = [
        'model_type',
        'model_id',
        'configuration',
        'last_query_at',
        'total_queries',
        'created_by',
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_query_at' => 'datetime',
        'total_queries' => 'integer',
    ];

    /**
     * Increment the total queries counter
     */
    public function incrementQueries(): void
    {
        $this->increment('total_queries');
        $this->update(['last_query_at' => now()]);
    }
}
