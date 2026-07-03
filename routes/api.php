<?php

use App\Http\Controllers\Api\Agency\ClientController as AgencyClientController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\V1\CustomerController as V1CustomerController;
use App\Http\Controllers\Api\V1\DashboardController as V1DashboardController;
use App\Http\Controllers\Api\V1\ItemController as V1ItemController;
use App\Http\Controllers\Api\V1\OrderController as V1OrderController;
use App\Http\Controllers\Api\V1\PaymentController as V1PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SaaS Platform API
| Base URL: /api
|
*/

// ==================
// Health Check
// ==================
Route::get('/', function () {
    return response()->json([
        'name' => config('business.name').' API',
        'version' => '1.0.0',
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::get('/health', HealthController::class);

// ==================
// Quotes (public)
// ==================
Route::post('quotes', [QuoteController::class, 'store']);
Route::get('quotes/{quote}/pdf', [QuoteController::class, 'downloadPdf']);
Route::post('quotes/{quote}/generate-pdf', [QuoteController::class, 'generatePdf']);

// ==================
// Content (FAQ, Policies)
// ==================
Route::get('content', [ContentController::class, 'index']);
Route::get('content/{type}', [ContentController::class, 'show'])
    ->where('type', 'faq|policies|sales-scripts|terms');

// ==================
// Auth (Mobile) & API v1 — requires api module
// ==================
if (hasModule('api')) {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('push-token', [AuthController::class, 'pushToken']);
        });
    });

    Route::prefix('v1')->middleware(['auth:sanctum', 'api.tenant', 'api.pro', 'throttle:api-tenant'])->group(function () {
        Route::apiResource('orders', V1OrderController::class)->only(['index', 'show', 'store']);
        Route::patch('orders/{order}/status', [V1OrderController::class, 'updateStatus']);
        Route::apiResource('customers', V1CustomerController::class)->only(['index', 'show', 'store', 'update']);
        Route::get('customers/{customer}/orders', [V1CustomerController::class, 'orders']);
        Route::apiResource('items', V1ItemController::class)->only(['index', 'show']);

        if (hasModule('payments')) {
            Route::apiResource('payments', V1PaymentController::class)->only(['index', 'show']);
        }

        Route::get('dashboard/stats', [V1DashboardController::class, 'stats']);
    });
}

// ==================
// Agency module (internal admin panel)
// Auth + tenant-scoped, but NOT behind the Pro gate (first-party endpoints).
// ==================
Route::prefix('agency')->middleware(['auth:sanctum', 'api.tenant', 'throttle:api-tenant'])->group(function () {
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
});
