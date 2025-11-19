<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Nexus\Connector\Contracts\RateLimiterStorageInterface;
use Nexus\Connector\ValueObjects\RateLimitConfig;

/**
 * Redis-based rate limiter storage implementation.
 *
 * Implements token bucket algorithm storage using Redis for global
 * rate limiting across all PHP-FPM workers and Laravel Octane processes.
 */
final class RedisRateLimiterStorage implements RateLimiterStorageInterface
{
    private const KEY_PREFIX_TOKENS = 'rate_limiter_tokens';
    private const KEY_PREFIX_REFILL = 'rate_limiter_refill';

    /**
     * Get current token count for a service.
     */
    public function getTokens(string $serviceName, RateLimitConfig $config): float
    {
        $key = $this->buildTokensKey($serviceName);
        $tokens = Cache::get($key);

        // Initialize bucket if it doesn't exist
        if ($tokens === null) {
            $this->initializeBucket($serviceName, $config);
            return (float) $config->maxRequests;
        }

        return (float) $tokens;
    }

    /**
     * Consume tokens for a service.
     */
    public function consumeTokens(string $serviceName, RateLimitConfig $config, float $tokens): bool
    {
        $current = $this->getTokens($serviceName, $config);

        if ($current < $tokens) {
            return false;
        }

        $key = $this->buildTokensKey($serviceName);
        $newValue = $current - $tokens;

        Cache::put(
            $key,
            $newValue,
            now()->addSeconds($config->windowSeconds)
        );

        return true;
    }

    /**
     * Refill tokens for a service based on elapsed time.
     */
    public function refillTokens(string $serviceName, RateLimitConfig $config): float
    {
        $lastRefill = $this->getLastRefillTime($serviceName);
        $currentTokens = $this->getTokens($serviceName, $config);

        // Initialize if needed
        if ($lastRefill === null) {
            $this->initializeBucket($serviceName, $config);
            return (float) $config->maxRequests;
        }

        $now = microtime(true);
        $elapsedSeconds = $now - $lastRefill;
        $tokensToAdd = $elapsedSeconds * $config->tokensPerSecond();

        $newTokens = min(
            (float) $config->maxRequests,
            $currentTokens + $tokensToAdd
        );

        // Update tokens and refill time
        Cache::put(
            $this->buildTokensKey($serviceName),
            $newTokens,
            now()->addSeconds($config->windowSeconds)
        );

        Cache::put(
            $this->buildRefillKey($serviceName),
            $now,
            now()->addSeconds($config->windowSeconds)
        );

        return $newTokens;
    }

    /**
     * Reset rate limiter state for a service.
     */
    public function reset(string $serviceName): void
    {
        Cache::forget($this->buildTokensKey($serviceName));
        Cache::forget($this->buildRefillKey($serviceName));
    }

    /**
     * Get last refill timestamp for a service.
     */
    public function getLastRefillTime(string $serviceName): ?float
    {
        $time = Cache::get($this->buildRefillKey($serviceName));
        return $time !== null ? (float) $time : null;
    }

    /**
     * Initialize bucket with full tokens.
     */
    private function initializeBucket(string $serviceName, RateLimitConfig $config): void
    {
        $now = microtime(true);

        Cache::put(
            $this->buildTokensKey($serviceName),
            (float) $config->maxRequests,
            now()->addSeconds($config->windowSeconds)
        );

        Cache::put(
            $this->buildRefillKey($serviceName),
            $now,
            now()->addSeconds($config->windowSeconds)
        );
    }

    /**
     * Build cache key for tokens.
     */
    private function buildTokensKey(string $serviceName): string
    {
        return sprintf('%s:%s', self::KEY_PREFIX_TOKENS, $serviceName);
    }

    /**
     * Build cache key for last refill time.
     */
    private function buildRefillKey(string $serviceName): string
    {
        return sprintf('%s:%s', self::KEY_PREFIX_REFILL, $serviceName);
    }
}
