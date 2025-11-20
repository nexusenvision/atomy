<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DbRequisitionRepository;
use App\Repositories\DbPurchaseOrderRepository;
use App\Repositories\DbGoodsReceiptNoteRepository;
use App\Repositories\DbVendorQuoteRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
use Nexus\Procurement\Services\ProcurementManager;
use Nexus\Procurement\Services\RequisitionManager;
use Nexus\Procurement\Services\PurchaseOrderManager;
use Nexus\Procurement\Services\GoodsReceiptManager;
use Nexus\Procurement\Services\VendorQuoteManager;
use Nexus\Procurement\Services\MatchingEngine;
use Psr\Log\LoggerInterface;

final class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(RequisitionRepositoryInterface::class, DbRequisitionRepository::class);
        $this->app->singleton(PurchaseOrderRepositoryInterface::class, DbPurchaseOrderRepository::class);
        $this->app->singleton(GoodsReceiptRepositoryInterface::class, DbGoodsReceiptNoteRepository::class);
        $this->app->singleton(VendorQuoteRepositoryInterface::class, DbVendorQuoteRepository::class);

        // Service bindings
        $this->app->singleton(RequisitionManager::class, function ($app) {
            return new RequisitionManager(
                $app->make(RequisitionRepositoryInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(PurchaseOrderManager::class, function ($app) {
            return new PurchaseOrderManager(
                $app->make(PurchaseOrderRepositoryInterface::class),
                $app->make(RequisitionRepositoryInterface::class),
                $app->make(RequisitionManager::class),
                $app->make(LoggerInterface::class),
                config('procurement.po_tolerance_percent', 10.0)
            );
        });

        $this->app->singleton(GoodsReceiptManager::class, function ($app) {
            return new GoodsReceiptManager(
                $app->make(GoodsReceiptRepositoryInterface::class),
                $app->make(PurchaseOrderRepositoryInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(VendorQuoteManager::class, function ($app) {
            return new VendorQuoteManager(
                $app->make(VendorQuoteRepositoryInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(MatchingEngine::class, function ($app) {
            return new MatchingEngine(
                $app->make(LoggerInterface::class),
                config('procurement.quantity_tolerance_percent', 5.0),
                config('procurement.price_tolerance_percent', 5.0)
            );
        });

        // Main orchestrator
        $this->app->singleton(ProcurementManagerInterface::class, ProcurementManager::class);
    }

    public function boot(): void
    {
        // Publish configuration if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/procurement.php' => config_path('procurement.php'),
            ], 'procurement-config');
        }
    }
}
