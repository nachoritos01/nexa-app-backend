<?php

use App\Http\Controllers\Api\Agency\ClientController as AgencyClientController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SaaS Platform API — first-party surfaces only.
| Base URL: /api
|
| Served surfaces: /api/auth (panel login) + /api/agency (panel resources).
| The public (quotes/content/health) and mobile V1 surfaces were retired —
| see docs/auditoria-y-plan-api-agency.md. Health checks use the native /up.
|
*/

// ==================
// Auth (panel) — outside any module gate: the panel's login must never be
// switched off by MODULE_API. push-token (mobile-only) was retired.
// ==================
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// ==================
// Agency module (internal admin panel)
// Auth + tenant-scoped, but NOT behind the Pro gate (first-party endpoints).
// ==================
Route::prefix('agency')->middleware(['auth:sanctum', 'api.tenant', 'agency.access', 'throttle:api-tenant'])->group(function () {
    Route::apiResource('clients', AgencyClientController::class);
    Route::apiResource('projects', \App\Http\Controllers\Api\Agency\ProjectController::class);
    Route::apiResource('services', \App\Http\Controllers\Api\Agency\ServiceController::class);
    Route::apiResource('suppliers', \App\Http\Controllers\Api\Agency\SupplierController::class);
    Route::apiResource('team', \App\Http\Controllers\Api\Agency\TeamMemberController::class);
    // PDF + email routes before apiResource so /{id}/pdf does not resolve as show.
    Route::get('quotes/{id}/pdf', [\App\Http\Controllers\Api\Agency\QuoteController::class, 'pdf']);
    Route::get('invoices/{id}/pdf', [\App\Http\Controllers\Api\Agency\InvoiceController::class, 'pdf']);
    Route::post('quotes/{id}/send', [\App\Http\Controllers\Api\Agency\QuoteController::class, 'send']);
    Route::post('invoices/{id}/send', [\App\Http\Controllers\Api\Agency\InvoiceController::class, 'send']);
    Route::apiResource('quotes', \App\Http\Controllers\Api\Agency\QuoteController::class);
    Route::apiResource('invoices', \App\Http\Controllers\Api\Agency\InvoiceController::class);
    Route::apiResource('expenses', \App\Http\Controllers\Api\Agency\ExpenseController::class);
    Route::get('settings', [\App\Http\Controllers\Api\Agency\SettingsController::class, 'show']);
    Route::put('settings', [\App\Http\Controllers\Api\Agency\SettingsController::class, 'update']);
    Route::get('activity', [\App\Http\Controllers\Api\Agency\ActivityController::class, 'index']);
});
