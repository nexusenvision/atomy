<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

// Package Contracts
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface;
use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetApprovalWorkflowInterface;
use Nexus\Budget\Contracts\BudgetForecastInterface;
use Nexus\Budget\Contracts\BudgetSimulatorInterface;

// Package Services
use Nexus\Budget\Services\BudgetManager;
use Nexus\Budget\Services\BudgetRolloverHandler;
use Nexus\Budget\Services\BudgetForecastService;
use Nexus\Budget\Services\BudgetVarianceInvestigator;
use Nexus\Budget\Services\BudgetSimulator;
use Nexus\Budget\Services\UtilizationAlertManager;

// Package Event Listeners
use Nexus\Budget\Listeners\ProcurementEventListener;
use Nexus\Budget\Listeners\FinanceEventListener;
use Nexus\Budget\Listeners\PeriodEventListener;
use Nexus\Budget\Listeners\WorkflowEventListener;

// Application Repository Implementations
use App\Repositories\DbBudgetRepository;
use App\Repositories\DbBudgetTransactionRepository;
use App\Repositories\DbBudgetAnalyticsRepository;

// External Package Events
use Nexus\Procurement\Events\PurchaseOrderApprovedEvent;
use Nexus\Procurement\Events\PurchaseOrderCancelledEvent;
use Nexus\Procurement\Events\PurchaseOrderClosedEvent;
use Nexus\Finance\Events\JournalEntryPostedEvent;
use Nexus\Period\Events\PeriodClosedEvent;
use Nexus\Period\Events\PeriodOpenedEvent;
use Nexus\Workflow\Events\ApprovalCompletedEvent;

/**
 * Budget Service Provider
 *
 * Registers all Budget package services, repositories, and event listeners
 * within the Atomy application context.
 *
 * @package App\Providers
 */
final class BudgetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // ===============================================
        // Repository Bindings (Essential - Application-Specific)
        // ===============================================

        $this->app->singleton(BudgetRepositoryInterface::class, DbBudgetRepository::class);
        $this->app->singleton(BudgetTransactionRepositoryInterface::class, DbBudgetTransactionRepository::class);
        $this->app->singleton(BudgetAnalyticsRepositoryInterface::class, DbBudgetAnalyticsRepository::class);

        // ===============================================
        // Service Bindings (Essential - Using Package Defaults)
        // ===============================================

        // Main Budget Manager (orchestrator)
        $this->app->singleton(BudgetManagerInterface::class, BudgetManager::class);

        // Forecast Service (AI-powered predictions)
        $this->app->singleton(BudgetForecastInterface::class, BudgetForecastService::class);

        // Simulator Service (what-if analysis)
        $this->app->singleton(BudgetSimulatorInterface::class, BudgetSimulator::class);

        // ===============================================
        // Support Services (Auto-Resolvable but Singleton for Performance)
        // ===============================================

        $this->app->singleton(BudgetRolloverHandler::class);
        $this->app->singleton(BudgetVarianceInvestigator::class);
        $this->app->singleton(UtilizationAlertManager::class);

        // ===============================================
        // Event Listeners (Auto-Resolvable)
        // ===============================================

        $this->app->bind(ProcurementEventListener::class);
        $this->app->bind(FinanceEventListener::class);
        $this->app->bind(PeriodEventListener::class);
        $this->app->bind(WorkflowEventListener::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->publishConfiguration();
    }

    /**
     * Register event listeners for budget integration.
     */
    private function registerEventListeners(): void
    {
        // ===============================================
        // Procurement Events (PO Lifecycle)
        // ===============================================

        Event::listen(
            PurchaseOrderApprovedEvent::class,
            [ProcurementEventListener::class, 'handlePurchaseOrderApproved']
        );

        Event::listen(
            PurchaseOrderCancelledEvent::class,
            [ProcurementEventListener::class, 'handlePurchaseOrderCancelled']
        );

        Event::listen(
            PurchaseOrderClosedEvent::class,
            [ProcurementEventListener::class, 'handlePurchaseOrderClosed']
        );

        // ===============================================
        // Finance Events (Actual Expenditure)
        // ===============================================

        Event::listen(
            JournalEntryPostedEvent::class,
            [FinanceEventListener::class, 'handleJournalEntryPosted']
        );

        // ===============================================
        // Period Events (Fiscal Period Lifecycle)
        // ===============================================

        Event::listen(
            PeriodClosedEvent::class,
            [PeriodEventListener::class, 'handlePeriodClosed']
        );

        Event::listen(
            PeriodOpenedEvent::class,
            [PeriodEventListener::class, 'handlePeriodOpened']
        );

        // ===============================================
        // Workflow Events (Approval Workflows)
        // ===============================================

        Event::listen(
            ApprovalCompletedEvent::class,
            [WorkflowEventListener::class, 'handleApprovalCompleted']
        );
    }

    /**
     * Publish configuration files.
     */
    private function publishConfiguration(): void
    {
        // Optional: For future artisan publish command
        // $this->publishes([
        //     __DIR__.'/../../../config/budget.php' => config_path('budget.php'),
        // ], 'budget-config');
    }
}
