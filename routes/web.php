<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\OrderPdfController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\SaasController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Home (SaaS Landing)
Route::get('/', [SaasController::class, 'landing'])->name('home');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Content Pages
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/policies', [PageController::class, 'policies'])->name('policies');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/usage-policies', [PageController::class, 'usagePolicies'])->name('usage-policies');

// Order PDF (public, signed URL)
Route::get('/orders/{order}/pdf', [OrderPdfController::class, 'download'])->name('orders.pdf');

// Payments module: payment status, billing, Stripe webhook
if (hasModule('payments')) {
    Route::get('/payment/success', [PaymentStatusController::class, 'success'])->name('payment.success');
    Route::get('/payment/failure', [PaymentStatusController::class, 'failure'])->name('payment.failure');

    Route::middleware('auth')->group(function () {
        Route::get('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        Route::get('/suspended', fn () => view('suspended'))->name('suspended');
    });

    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');
}

// Customer Portal module: auth + portal routes
if (hasModule('customer_portal')) {
    Route::get('/my-account/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/my-account/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
    Route::post('/my-account/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

    Route::middleware(['auth:customer', \App\Http\Middleware\EnsureCustomerTenant::class])->prefix('my-account')->group(function () {
        Route::get('/', [CustomerPortalController::class, 'index'])->name('customer.portal');
        Route::get('/orders', [CustomerPortalController::class, 'orders'])->name('customer.orders');
        Route::get('/orders/{order}', [CustomerPortalController::class, 'orderDetail'])->name('customer.order.detail');
        Route::get('/orders/{order}/pdf', [CustomerPortalController::class, 'orderPdf'])->name('customer.order.pdf');
        Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('customer.profile');
        Route::put('/profile', [CustomerPortalController::class, 'updateProfile'])->name('customer.profile.update');
        Route::put('/profile/password', [CustomerPortalController::class, 'updatePassword'])->name('customer.password.update');
        Route::get('/addresses', [CustomerPortalController::class, 'addresses'])->name('customer.addresses');
        Route::get('/payments', [CustomerPortalController::class, 'payments'])->name('customer.payments');
        Route::get('/settings', [CustomerPortalController::class, 'settings'])->name('customer.settings');
        Route::put('/settings/notifications', [CustomerPortalController::class, 'updateNotifications'])->name('customer.notifications.update');
        Route::delete('/settings/account', [CustomerPortalController::class, 'deleteAccount'])->name('customer.account.delete');

        // Loyalty routes (guarded by module check in controller)
        Route::get('/loyalty', [CustomerPortalController::class, 'loyalty'])->name('customer.loyalty');
        Route::post('/loyalty/redeem/{reward}', [CustomerPortalController::class, 'redeemReward'])->name('customer.loyalty.redeem');

        // Addresses CRUD
        Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
        Route::put('/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customer.addresses.update');
        Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.addresses.destroy');
        Route::patch('/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('customer.addresses.default');
    });
}

// SaaS Pricing + Legal
Route::get('/pricing', [SaasController::class, 'pricing'])->name('saas.pricing');
Route::get('/saas/terms', [SaasController::class, 'terms'])->name('saas.terms');
Route::get('/saas/policies', [SaasController::class, 'policies'])->name('saas.policies');

// Impersonation
Route::get('/impersonation/stop', [ImpersonationController::class, 'stop'])->middleware('auth')->name('impersonation.stop');

// CSV Exports (admin)
if (hasModule('exports')) {
    Route::get('/admin/exports/{type}', [ExportController::class, 'export'])->middleware('auth')->name('admin.exports');
}
