<?php

declare(strict_types=1);

use App\Http\Controllers\Api\StatutoryReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Statutory Reporting API Routes
|--------------------------------------------------------------------------
|
| API routes for statutory report generation and management.
|
*/

Route::prefix('statutory')->group(function () {
    // Report Management
    Route::get('/reports', [StatutoryReportController::class, 'index'])
        ->name('api.statutory.reports.index');
    
    Route::get('/reports/{reportId}', [StatutoryReportController::class, 'show'])
        ->name('api.statutory.reports.show');
    
    // Report Generation
    Route::post('/reports/generate', [StatutoryReportController::class, 'generate'])
        ->name('api.statutory.reports.generate');
    
    Route::post('/reports/generate-with-metadata', [StatutoryReportController::class, 'generateWithMetadata'])
        ->name('api.statutory.reports.generate-metadata');
    
    // Metadata
    Route::get('/report-types', [StatutoryReportController::class, 'getReportTypes'])
        ->name('api.statutory.report-types');
});
