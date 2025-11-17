<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbCounterRepository;
use App\Repositories\DbGapRepository;
use App\Repositories\DbPatternVersionRepository;
use App\Repositories\DbReservationRepository;
use App\Repositories\DbSequenceRepository;
use App\Services\SequenceAuditLogger;
use Illuminate\Support\ServiceProvider;
use Nexus\Sequencing\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Contracts\GapRepositoryInterface;
use Nexus\Sequencing\Contracts\PatternVersionRepositoryInterface;
use Nexus\Sequencing\Contracts\ReservationRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceAuditInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Services\BulkGeneratorService;
use Nexus\Sequencing\Services\CounterService;
use Nexus\Sequencing\Services\ExhaustionMonitor;
use Nexus\Sequencing\Services\GapManager;
use Nexus\Sequencing\Services\PatternMigrationService;
use Nexus\Sequencing\Services\PatternParser;
use Nexus\Sequencing\Services\PatternVersionManager;
use Nexus\Sequencing\Services\ReservationService;
use Nexus\Sequencing\Services\SequenceManager;
use Nexus\Sequencing\Services\SequenceMetricsService;
use Nexus\Sequencing\Services\SequenceValidationService;

/**
 * Service provider for Sequencing package bindings.
 */
class SequencingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sequencing.php',
            'sequencing'
        );

        // Bind repositories
        $this->app->singleton(SequenceRepositoryInterface::class, DbSequenceRepository::class);
        $this->app->singleton(CounterRepositoryInterface::class, DbCounterRepository::class);
        $this->app->singleton(ReservationRepositoryInterface::class, DbReservationRepository::class);
        $this->app->singleton(GapRepositoryInterface::class, DbGapRepository::class);
        $this->app->singleton(PatternVersionRepositoryInterface::class, DbPatternVersionRepository::class);
        $this->app->singleton(SequenceAuditInterface::class, SequenceAuditLogger::class);

        // Bind services (these are readonly classes with dependencies)
        $this->app->singleton(PatternParser::class);
        $this->app->singleton(CounterService::class);
        $this->app->singleton(PatternVersionManager::class);
        $this->app->singleton(PatternMigrationService::class);
        $this->app->singleton(ExhaustionMonitor::class);

        // Bind main manager
        $this->app->singleton(SequenceManager::class);
        $this->app->singleton(BulkGeneratorService::class);
        $this->app->singleton(GapManager::class);
        $this->app->singleton(ReservationService::class);
        $this->app->singleton(SequenceValidationService::class);
        $this->app->singleton(SequenceMetricsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/sequencing.php' => config_path('sequencing.php'),
        ], 'sequencing-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'sequencing-migrations');
    }
}
