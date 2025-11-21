<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbReportRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Nexus\Reporting\Contracts\ReportDefinitionInterface;
use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportGeneratorInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Contracts\ReportRetentionInterface;
use Nexus\Reporting\Core\Engine\ReportDistributor;
use Nexus\Reporting\Core\Engine\ReportGenerator;
use Nexus\Reporting\Core\Engine\ReportJobHandler;
use Nexus\Reporting\Core\Engine\ReportRetentionManager;
use Nexus\Reporting\Services\ReportManager;

/**
 * Service provider for the Nexus\Reporting package.
 *
 * Binds interfaces to implementations and registers scheduled jobs.
 */
class ReportingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(
            ReportRepositoryInterface::class,
            DbReportRepository::class
        );

        // Core engine bindings
        $this->app->singleton(
            ReportGeneratorInterface::class,
            ReportGenerator::class
        );

        $this->app->singleton(
            ReportDistributorInterface::class,
            ReportDistributor::class
        );

        $this->app->singleton(
            ReportRetentionInterface::class,
            ReportRetentionManager::class
        );

        // Main service
        $this->app->singleton(ReportManager::class);

        // Job handler
        $this->app->singleton(ReportJobHandler::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register ReportJobHandler with Scheduler
        // Note: Actual implementation depends on Scheduler package API
        // This is a placeholder for the integration point
        if ($this->app->bound(\Nexus\Scheduler\Contracts\ScheduleManagerInterface::class)) {
            $scheduleManager = $this->app->make(\Nexus\Scheduler\Contracts\ScheduleManagerInterface::class);
            $reportJobHandler = $this->app->make(ReportJobHandler::class);

            // Register handler (assuming Scheduler has a registerHandler method)
            // $scheduleManager->registerHandler($reportJobHandler);
        }

        // Schedule daily retention policy application
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Run retention policy daily at 2 AM
            $schedule->call(function () {
                $retentionManager = $this->app->make(ReportRetentionInterface::class);
                $stats = $retentionManager->applyRetentionPolicy();

                // Log results
                $logger = $this->app->make(\Psr\Log\LoggerInterface::class);
                $logger->info('Daily retention policy executed', $stats);
            })->dailyAt('02:00')->name('reporting:retention-policy');
        });
    }
}
