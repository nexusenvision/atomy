<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Nexus\Connector\Contracts\CircuitBreakerStorageInterface;
use Nexus\Connector\ValueObjects\CircuitBreakerState;

/**
 * Redis-based circuit breaker storage implementation.
 *
 * Stores circuit breaker state in Redis for global synchronization
 * across all PHP-FPM workers and Laravel Octane processes.
 */
final class RedisCircuitBreakerStorage implements CircuitBreakerStorageInterface
{
    private const KEY_PREFIX = 'circuit_breaker';
    private const DEFAULT_TTL = 600; // 10 minutes

    /**
     * Retrieve circuit breaker state for a service.
     */
    public function getState(string $serviceName): CircuitBreakerState
    {
        $key = $this->buildKey($serviceName);
        $data = Cache::get($key);

        if ($data === null) {
            return CircuitBreakerState::closed();
        }

        return CircuitBreakerState::fromArray($data);
    }

    /**
     * Store circuit breaker state for a service.
     */
    public function setState(string $serviceName, CircuitBreakerState $state): void
    {
        $key = $this->buildKey($serviceName);
        
        Cache::put(
            $key,
            $state->toArray(),
            now()->addSeconds(self::DEFAULT_TTL)
        );
    }

    /**
     * Check if circuit breaker exists for a service.
     */
    public function hasState(string $serviceName): bool
    {
        return Cache::has($this->buildKey($serviceName));
    }

    /**
     * Reset circuit breaker state for a service.
     */
    public function resetState(string $serviceName): void
    {
        Cache::forget($this->buildKey($serviceName));
    }

    /**
     * Clean up expired circuit breaker states.
     *
     * Note: With Redis/Cache, expired keys are automatically cleaned up.
     * This method is provided for interface compliance.
     */
    public function cleanExpired(): int
    {
        // Redis automatically handles TTL expiration
        // This is a no-op for cache-based implementations
        return 0;
    }

    /**
     * Build cache key for service.
     */
    private function buildKey(string $serviceName): string
    {
        return sprintf('%s:%s', self::KEY_PREFIX, $serviceName);
    }
}
