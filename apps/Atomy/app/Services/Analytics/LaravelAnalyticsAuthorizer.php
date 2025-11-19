<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsPermission;
use Nexus\Analytics\Contracts\AnalyticsAuthorizerInterface;
use Nexus\Analytics\Exceptions\InvalidDelegationChainException;

/**
 * Laravel implementation of analytics authorization
 * 
 * Satisfies: SEC-ANA-0485 (RBAC integration), BUS-ANA-0139, BUS-ANA-0143
 */
final class LaravelAnalyticsAuthorizer implements AnalyticsAuthorizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function can(string $userId, string $action, string $queryId): bool
    {
        // Check direct user permission
        $permission = AnalyticsPermission::where('query_id', $queryId)
            ->where('subject_type', 'user')
            ->where('subject_id', $userId)
            ->first();

        if ($permission && $permission->isValid() && $permission->hasAction($action)) {
            return true;
        }

        // Check role-based permissions
        $userRoles = $this->getUserRoles($userId);

        foreach ($userRoles as $roleId) {
            $rolePermission = AnalyticsPermission::where('query_id', $queryId)
                ->where('subject_type', 'role')
                ->where('subject_id', $roleId)
                ->first();

            if ($rolePermission && $rolePermission->isValid() && $rolePermission->hasAction($action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkDelegationChain(string $userId, string $queryId): array
    {
        $permission = AnalyticsPermission::where('query_id', $queryId)
            ->where('subject_type', 'user')
            ->where('subject_id', $userId)
            ->first();

        if (!$permission) {
            return [
                'has_delegation' => false,
                'chain_length' => 0,
                'delegated_by' => null,
            ];
        }

        // BUS-ANA-0139: Maximum 3 levels depth
        if ($permission->delegation_level > 3) {
            throw new InvalidDelegationChainException(
                'Delegation chain exceeds maximum depth of 3 levels'
            );
        }

        return [
            'has_delegation' => $permission->delegated_by !== null,
            'chain_length' => $permission->delegation_level,
            'delegated_by' => $permission->delegated_by,
            'expires_at' => $permission->delegation_expires_at?->toIso8601String(),
            'is_valid' => $permission->isValid(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(string $userId, string $queryId): array
    {
        $permission = AnalyticsPermission::where('query_id', $queryId)
            ->where('subject_type', 'user')
            ->where('subject_id', $userId)
            ->first();

        if (!$permission || !$permission->isValid()) {
            return [];
        }

        return $permission->actions;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyTenantIsolation(string $tenantId, string $queryId): bool
    {
        // SEC-ANA-0482: Enforce tenant isolation
        // Implementation would check if query definition belongs to tenant
        // For now, return true (assumes tenant context is enforced elsewhere)
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canViewSensitiveData(string $userId, string $dataType): bool
    {
        // BUS-ANA-0135: Users cannot view sensitive data about themselves
        // This would integrate with the RBAC system to check permissions
        // For now, basic implementation
        
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return false;
        }

        // Check if user has sensitive data permission
        return $user->hasPermission('view_sensitive_analytics_data');
    }

    /**
     * Get user's role IDs
     *
     * @param string $userId
     * @return array<int, string>
     */
    private function getUserRoles(string $userId): array
    {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return [];
        }

        // Assuming users have a roles relationship
        if (method_exists($user, 'roles')) {
            return $user->roles->pluck('id')->map(fn($id) => (string) $id)->toArray();
        }

        return [];
    }
}
