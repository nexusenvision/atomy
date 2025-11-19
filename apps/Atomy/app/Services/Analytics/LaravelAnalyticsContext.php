<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Nexus\Analytics\Contracts\AnalyticsContextInterface;

/**
 * Laravel implementation of analytics context
 * 
 * Provides current execution context (user, tenant, request data)
 */
final class LaravelAnalyticsContext implements AnalyticsContextInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * {@inheritdoc}
     */
    public function getUserId(): ?string
    {
        $user = auth()->user();

        if ($user) {
            return (string) $user->id;
        }

        return request()->header('X-User-Id');
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?string
    {
        $user = auth()->user();

        if ($user && property_exists($user, 'tenant_id')) {
            return (string) $user->tenant_id;
        }

        return request()->header('X-Tenant-Id') ?? config('app.default_tenant_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRoles(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        if (method_exists($user, 'roles')) {
            return $user->roles->pluck('name')->toArray();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getContextData(): array
    {
        return array_merge([
            'user_id' => $this->getUserId(),
            'tenant_id' => $this->getTenantId(),
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'roles' => $this->getUserRoles(),
        ], $this->contextData);
    }

    /**
     * {@inheritdoc}
     */
    public function getIpAddress(): ?string
    {
        return request()->ip();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAgent(): ?string
    {
        return request()->userAgent();
    }

    /**
     * {@inheritdoc}
     */
    public function setContextData(array $data): void
    {
        $this->contextData = array_merge($this->contextData, $data);
    }
}
