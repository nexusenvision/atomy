<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sequencing API Routes
|--------------------------------------------------------------------------
|
| API endpoints for sequence management and number generation.
|
*/

Route::prefix('sequencing')->group(function () {
    
    // Sequence management
    Route::get('/sequences', function () {
        // List all sequences
    })->name('sequencing.sequences.index');

    Route::post('/sequences', function () {
        // Create new sequence definition
    })->name('sequencing.sequences.store');

    Route::get('/sequences/{name}', function (string $name) {
        // Get sequence details
    })->name('sequencing.sequences.show');

    Route::put('/sequences/{name}', function (string $name) {
        // Update sequence definition
    })->name('sequencing.sequences.update');

    Route::delete('/sequences/{name}', function (string $name) {
        // Delete sequence
    })->name('sequencing.sequences.destroy');

    // Number generation
    Route::post('/sequences/{name}/generate', function (string $name) {
        // Generate next number
    })->name('sequencing.sequences.generate');

    Route::post('/sequences/{name}/preview', function (string $name) {
        // Preview next number without consuming counter
    })->name('sequencing.sequences.preview');

    Route::post('/sequences/{name}/bulk-generate', function (string $name) {
        // Generate multiple numbers atomically
    })->name('sequencing.sequences.bulk-generate');

    // Counter management
    Route::post('/sequences/{name}/counter/reset', function (string $name) {
        // Reset counter
    })->name('sequencing.sequences.counter.reset');

    Route::post('/sequences/{name}/counter/override', function (string $name) {
        // Manually override counter value
    })->name('sequencing.sequences.counter.override');

    // Lock management
    Route::post('/sequences/{name}/lock', function (string $name) {
        // Lock sequence
    })->name('sequencing.sequences.lock');

    Route::post('/sequences/{name}/unlock', function (string $name) {
        // Unlock sequence
    })->name('sequencing.sequences.unlock');

    // Reservations
    Route::post('/sequences/{name}/reservations', function (string $name) {
        // Reserve numbers
    })->name('sequencing.sequences.reservations.store');

    Route::post('/sequences/{name}/reservations/release', function (string $name) {
        // Release reserved numbers
    })->name('sequencing.sequences.reservations.release');

    Route::post('/sequences/{name}/reservations/finalize', function (string $name) {
        // Finalize reserved numbers
    })->name('sequencing.sequences.reservations.finalize');

    Route::get('/sequences/{name}/reservations', function (string $name) {
        // Get active reservations
    })->name('sequencing.sequences.reservations.index');

    // Gap management
    Route::post('/sequences/{name}/gaps', function (string $name) {
        // Record a gap
    })->name('sequencing.sequences.gaps.store');

    Route::get('/sequences/{name}/gaps', function (string $name) {
        // Get gap report
    })->name('sequencing.sequences.gaps.index');

    Route::delete('/sequences/{name}/gaps', function (string $name) {
        // Clear all gaps
    })->name('sequencing.sequences.gaps.destroy');

    // Pattern versions
    Route::get('/sequences/{name}/versions', function (string $name) {
        // Get all pattern versions
    })->name('sequencing.sequences.versions.index');

    Route::post('/sequences/{name}/versions', function (string $name) {
        // Create new pattern version
    })->name('sequencing.sequences.versions.store');

    // Validation
    Route::post('/sequences/{name}/validate', function (string $name) {
        // Validate a number against pattern
    })->name('sequencing.sequences.validate');

    // Metrics
    Route::get('/sequences/{name}/metrics', function (string $name) {
        // Get sequence metrics
    })->name('sequencing.sequences.metrics');

    // Utilities
    Route::post('/validate-pattern', function () {
        // Validate pattern syntax
    })->name('sequencing.validate-pattern');

    Route::post('/detect-collisions', function () {
        // Detect pattern collisions
    })->name('sequencing.detect-collisions');
});
