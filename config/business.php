<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business Configuration
    |--------------------------------------------------------------------------
    |
    | All business-specific values centralized here. To adapt this platform
    | for a different business, update these values and the corresponding
    | .env variables. No code changes needed.
    |
    */

    // Brand
    'name' => env('BUSINESS_NAME', 'MySaaS'),
    'slogan' => env('BUSINESS_SLOGAN', 'Your Business Platform'),
    'description' => env('BUSINESS_DESCRIPTION', 'Multi-tenant SaaS platform for managing items, orders, customers and payments.'),

    // Contact
    'contact' => [
        'email' => env('BUSINESS_EMAIL', 'hello@example.com'),
        'phone' => env('BUSINESS_PHONE', ''),
    ],

    // Business Rules
    'rules' => [
        'currency' => env('BUSINESS_CURRENCY', 'USD'),
        'quote_expiry_days' => (int) env('BUSINESS_QUOTE_EXPIRY_DAYS', 7),
    ],

    // Filament admin panel
    'admin' => [
        'color' => env('BUSINESS_ADMIN_COLOR', 'blue'),
    ],

];
