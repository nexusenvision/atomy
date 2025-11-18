<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ComplianceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Compliance API Routes
|--------------------------------------------------------------------------
|
| API routes for Compliance and Segregation of Duties (SOD) management.
|
*/

Route::prefix('compliance')->group(function () {
    // Compliance Scheme Management
    Route::get('/schemes', [ComplianceController::class, 'getActiveSchemes'])
        ->name('api.compliance.schemes.index');
    
    Route::post('/schemes/activate', [ComplianceController::class, 'activateScheme'])
        ->name('api.compliance.schemes.activate');
    
    Route::post('/schemes/deactivate', [ComplianceController::class, 'deactivateScheme'])
        ->name('api.compliance.schemes.deactivate');
    
    // Configuration Auditing
    Route::post('/audit', [ComplianceController::class, 'auditConfiguration'])
        ->name('api.compliance.audit');
    
    // SOD (Segregation of Duties) Management
    Route::prefix('sod')->group(function () {
        Route::post('/rules', [ComplianceController::class, 'createSodRule'])
            ->name('api.compliance.sod.rules.create');
        
        Route::post('/validate', [ComplianceController::class, 'validateTransaction'])
            ->name('api.compliance.sod.validate');
    });
});
