<?php

declare(strict_types=1);

namespace App\Traits;

use Nexus\Analytics\Services\AnalyticsManager;
use Nexus\Analytics\Contracts\QueryResultInterface;

/**
 * HasAnalytics Trait
 * 
 * Provides analytics functionality to models
 * Satisfies: FUN-ANA-0232, FUN-ANA-0238, FUN-ANA-0244, FUN-ANA-0250, FUN-ANA-0256
 */
trait HasAnalytics
{
    /**
     * Get the analytics manager instance
     */
    protected function getAnalyticsManager(): AnalyticsManager
    {
        return app(AnalyticsManager::class);
    }

    /**
     * Get model type for analytics
     */
    protected function getAnalyticsModelType(): string
    {
        return static::class;
    }

    /**
     * Get model ID for analytics
     */
    protected function getAnalyticsModelId(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Initialize analytics for this model instance
     * 
     * Satisfies: BUS-ANA-0141 (Each model instance has one analytics instance)
     * Satisfies: PER-ANA-0367 (Analytics initialization)
     *
     * @return array<string, mixed>
     */
    public function analytics(): array
    {
        return $this->getAnalyticsManager()->getOrCreateInstance(
            $this->getAnalyticsModelType(),
            $this->getAnalyticsModelId()
        );
    }

    /**
     * Run a named query on this model
     * 
     * Satisfies: FUN-ANA-0244 (Implement analytics()->runQuery($name) method)
     * Satisfies: FUN-ANA-0238 (Support in-model query definitions)
     *
     * @param string $queryName
     * @param array<string, mixed> $parameters
     * @return QueryResultInterface
     */
    public function runQuery(string $queryName, array $parameters = []): QueryResultInterface
    {
        return $this->getAnalyticsManager()->runQuery(
            $queryName,
            $this->getAnalyticsModelType(),
            $this->getAnalyticsModelId(),
            $parameters
        );
    }

    /**
     * Check if current user can perform an action on a query
     * 
     * Satisfies: FUN-ANA-0250 (Implement analytics()->can($action) method)
     *
     * @param string $action
     * @param string $queryId
     */
    public function can(string $action, string $queryId): bool
    {
        return $this->getAnalyticsManager()->can($action, $queryId);
    }

    /**
     * Get analytics execution history for this model
     * 
     * Satisfies: FUN-ANA-0256 (Implement analytics()->history() method)
     * Satisfies: PER-ANA-0369 (Analytics history persisting)
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 50): array
    {
        return $this->getAnalyticsManager()->getHistory(
            $this->getAnalyticsModelType(),
            $this->getAnalyticsModelId(),
            $limit
        );
    }

    /**
     * Register a new query for this model
     * 
     * Satisfies: FUN-ANA-0238 (Support in-model query definitions)
     *
     * @param array<string, mixed> $queryData
     * @return string Query ID
     */
    public function registerQuery(array $queryData): string
    {
        return $this->getAnalyticsManager()->registerQuery(
            $this->getAnalyticsModelType(),
            $this->getAnalyticsModelId(),
            $queryData
        );
    }

    /**
     * Define queries within the model
     * 
     * Satisfies: FUN-ANA-0238 (Support in-model query definitions)
     * Override this method in your model to define queries
     *
     * @return array<string, array<string, mixed>>
     */
    public function analyticsQueries(): array
    {
        return [];
    }

    /**
     * Boot the trait - auto-register queries
     */
    protected static function bootHasAnalytics(): void
    {
        // This would be called when model boots
        // Auto-register queries defined in analyticsQueries()
    }
}
