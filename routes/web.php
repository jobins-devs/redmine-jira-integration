<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldMappingController;
use App\Http\Controllers\ProjectMappingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
Route::post('/dashboard/sync-logs/{syncLog}/retry', [DashboardController::class, 'retrySyncLog'])->name('dashboard.retry');

// Connections
Route::prefix('connections')->name('connections.')->group(function (): void {
    Route::get('/', [ConnectionController::class, 'index'])->name('index');
    Route::post('/', [ConnectionController::class, 'store'])->name('store');
    Route::put('/{connection}', [ConnectionController::class, 'update'])->name('update');
    Route::delete('/{connection}', [ConnectionController::class, 'destroy'])->name('destroy');
    Route::post('/{connection}/test', [ConnectionController::class, 'test'])->name('test');
    Route::get('/{connection}/projects', [ConnectionController::class, 'getProjects'])->name('projects');
    Route::get('/{connection}/metadata', [ConnectionController::class, 'getFieldMetadata'])->name('metadata');
});

// Field Mappings
Route::prefix('field-mappings')->name('field-mappings.')->group(function (): void {
    Route::get('/', [FieldMappingController::class, 'index'])->name('index');
    Route::post('/', [FieldMappingController::class, 'store'])->name('store');
    Route::put('/{fieldMapping}', [FieldMappingController::class, 'update'])->name('update');
    Route::delete('/{fieldMapping}', [FieldMappingController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-import', [FieldMappingController::class, 'bulkImport'])->name('bulk-import');
});

// Project Mappings
Route::prefix('project-mappings')->name('project-mappings.')->group(function (): void {
    Route::get('/', [ProjectMappingController::class, 'index'])->name('index');
    Route::post('/', [ProjectMappingController::class, 'store'])->name('store');
    Route::put('/{projectMapping}', [ProjectMappingController::class, 'update'])->name('update');
    Route::delete('/{projectMapping}', [ProjectMappingController::class, 'destroy'])->name('destroy');
    Route::post('/{projectMapping}/toggle', [ProjectMappingController::class, 'toggleEnabled'])->name('toggle');
});

// Webhooks (no authentication required for external services)
Route::post('/webhooks/redmine', [WebhookController::class, 'redmine'])->name('webhooks.redmine');
Route::post('/webhooks/jira', [WebhookController::class, 'jira'])->name('webhooks.jira');
