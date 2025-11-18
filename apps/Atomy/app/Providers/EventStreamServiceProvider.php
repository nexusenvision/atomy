<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbEventStoreRepository;
use App\Repositories\DbSnapshotRepository;
use App\Repositories\DbStreamReaderRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\EventStream\Contracts\EventSerializerInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Core\Engine\JsonEventSerializer;
use Nexus\EventStream\Core\Engine\ProjectionEngine;
use Nexus\EventStream\Core\Engine\SnapshotManager;
use Nexus\EventStream\Services\EventStreamManager;
use Psr\Log\LoggerInterface;

/**
 * EventStreamServiceProvider
 *
 * Binds EventStream package interfaces to concrete Atomy implementations.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - ARC-EVS-7008: Support multiple event store backends via adapter pattern
 */
class EventStreamServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind EventStoreInterface to SQL implementation
        $this->app->bind(EventStoreInterface::class, function ($app) {
            $tenantId = $app->make('tenant.context')->getCurrentTenantId() ?? 'default';
            return new DbEventStoreRepository($tenantId);
        });

        // Bind StreamReaderInterface to SQL implementation
        $this->app->bind(StreamReaderInterface::class, function ($app) {
            $tenantId = $app->make('tenant.context')->getCurrentTenantId() ?? 'default';
            return new DbStreamReaderRepository($tenantId);
        });

        // Bind SnapshotRepositoryInterface to SQL implementation
        $this->app->bind(SnapshotRepositoryInterface::class, function ($app) {
            $tenantId = $app->make('tenant.context')->getCurrentTenantId() ?? 'default';
            return new DbSnapshotRepository($tenantId);
        });

        // Bind EventSerializerInterface to JSON serializer
        $this->app->singleton(EventSerializerInterface::class, JsonEventSerializer::class);

        // Bind SnapshotManager
        $this->app->singleton(SnapshotManager::class, function ($app) {
            return new SnapshotManager(
                snapshotRepository: $app->make(SnapshotRepositoryInterface::class),
                eventStore: $app->make(EventStoreInterface::class),
                logger: $app->make(LoggerInterface::class),
                snapshotThreshold: config('eventstream.snapshot_threshold', 100)
            );
        });

        // Bind ProjectionEngine
        $this->app->singleton(ProjectionEngine::class, function ($app) {
            return new ProjectionEngine(
                streamReader: $app->make(StreamReaderInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });

        // Bind EventStreamManager (main service)
        $this->app->singleton(EventStreamManager::class, function ($app) {
            return new EventStreamManager(
                eventStore: $app->make(EventStoreInterface::class),
                streamReader: $app->make(StreamReaderInterface::class),
                snapshotRepository: $app->make(SnapshotRepositoryInterface::class),
                projectionEngine: $app->make(ProjectionEngine::class),
                snapshotManager: $app->make(SnapshotManager::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/eventstream.php' => config_path('eventstream.php'),
        ], 'eventstream-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
