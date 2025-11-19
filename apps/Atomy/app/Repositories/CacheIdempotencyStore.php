<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Nexus\Connector\Contracts\IdempotencyStoreInterface;
use Nexus\Connector\ValueObjects\IdempotencyKey;

/**
 * Cache-based idempotency store implementation.
 *
 * Stores idempotency keys and response data in cache to prevent
 * duplicate request processing.
 */
final class CacheIdempotencyStore implements IdempotencyStoreInterface
{
    private const KEY_PREFIX = 'idempotency';

    /**
     * Store idempotency key with response data.
     */
    public function store(IdempotencyKey $key, array $response, string $serviceName): void
    {
        $cacheKey = $this->buildKey($serviceName, (string) $key);
        
        Cache::put(
            $cacheKey,
            $response,
            $key->expiresAt
        );
    }

    /**
     * Retrieve cached response for idempotency key.
     */
    public function retrieve(IdempotencyKey $key, string $serviceName): ?array
    {
        $cacheKey = $this->buildKey($serviceName, (string) $key);
        $data = Cache::get($cacheKey);

        return is_array($data) ? $data : null;
    }

    /**
     * Check if idempotency key exists.
     */
    public function exists(IdempotencyKey $key, string $serviceName): bool
    {
        return Cache::has($this->buildKey($serviceName, (string) $key));
    }

    /**
     * Remove idempotency key.
     */
    public function forget(IdempotencyKey $key, string $serviceName): void
    {
        Cache::forget($this->buildKey($serviceName, (string) $key));
    }

    /**
     * Clean up expired idempotency keys.
     *
     * Note: Cache drivers automatically handle TTL expiration.
     */
    public function cleanExpired(): int
    {
        // Cache automatically handles TTL expiration
        return 0;
    }

    /**
     * Build cache key.
     */
    private function buildKey(string $serviceName, string $idempotencyKey): string
    {
        return sprintf('%s:%s:%s', self::KEY_PREFIX, $serviceName, $idempotencyKey);
    }
}
