<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Analytics Permission Model
 * 
 * RBAC for analytics queries
 * Satisfies: SEC-ANA-0485, BUS-ANA-0139, BUS-ANA-0143
 */
class AnalyticsPermission extends Model
{
    use HasUuids;

    protected $table = 'analytics_permissions';

    protected $fillable = [
        'query_id',
        'subject_type',
        'subject_id',
        'actions',
        'delegated_by',
        'delegation_level',
        'delegation_expires_at',
        'granted_by',
    ];

    protected $casts = [
        'actions' => 'array',
        'delegation_level' => 'integer',
        'delegation_expires_at' => 'datetime',
    ];

    /**
     * Check if permission is still valid (delegation not expired)
     */
    public function isValid(): bool
    {
        if ($this->delegation_expires_at === null) {
            return true;
        }

        return $this->delegation_expires_at->isFuture();
    }

    /**
     * Check if a specific action is granted
     */
    public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions, true);
    }

    /**
     * Get the query definition
     */
    public function queryDefinition()
    {
        return $this->belongsTo(AnalyticsQueryDefinition::class, 'query_id');
    }
}
