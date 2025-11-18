<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifier API Routes
|--------------------------------------------------------------------------
|
| API endpoints for notification management.
|
*/

Route::prefix('notifications')->group(function () {
    // Send notification
    Route::post('send', function () {
        return response()->json(['message' => 'Send notification endpoint']);
    });

    // Send batch notifications
    Route::post('send-batch', function () {
        return response()->json(['message' => 'Send batch notifications endpoint']);
    });

    // Schedule notification
    Route::post('schedule', function () {
        return response()->json(['message' => 'Schedule notification endpoint']);
    });

    // Cancel notification
    Route::delete('{notificationId}', function (string $notificationId) {
        return response()->json(['message' => "Cancel notification {$notificationId}"]);
    });

    // Get notification status
    Route::get('{notificationId}/status', function (string $notificationId) {
        return response()->json(['message' => "Get status for {$notificationId}"]);
    });

    // Notification history
    Route::get('history', function () {
        return response()->json(['message' => 'Get notification history']);
    });

    // Notification preferences
    Route::prefix('preferences')->group(function () {
        Route::get('{recipientId}', function (string $recipientId) {
            return response()->json(['message' => "Get preferences for {$recipientId}"]);
        });

        Route::put('{recipientId}', function (string $recipientId) {
            return response()->json(['message' => "Update preferences for {$recipientId}"]);
        });

        Route::post('{recipientId}/opt-out/{category}', function (string $recipientId, string $category) {
            return response()->json(['message' => "Opt out {$recipientId} from {$category}"]);
        });

        Route::post('{recipientId}/opt-in/{category}', function (string $recipientId, string $category) {
            return response()->json(['message' => "Opt in {$recipientId} to {$category}"]);
        });
    });

    // Templates
    Route::prefix('templates')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'List all templates']);
        });

        Route::get('{templateId}', function (string $templateId) {
            return response()->json(['message' => "Get template {$templateId}"]);
        });

        Route::post('/', function () {
            return response()->json(['message' => 'Create template']);
        });

        Route::put('{templateId}', function (string $templateId) {
            return response()->json(['message' => "Update template {$templateId}"]);
        });

        Route::delete('{templateId}', function (string $templateId) {
            return response()->json(['message' => "Delete template {$templateId}"]);
        });
    });
});
