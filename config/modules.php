<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle optional modules on or off. When a module is disabled, its routes,
    | menu items, and related functionality should be hidden. Use the
    | hasModule() helper to check module availability in code.
    |
    */

    'payments' => env('MODULE_PAYMENTS', true),
    'customer_portal' => env('MODULE_CUSTOMER_PORTAL', true),
    'locations' => env('MODULE_LOCATIONS', true),
    'api' => env('MODULE_API', true),
    'exports' => env('MODULE_EXPORTS', true),
    'loyalty' => env('MODULE_LOYALTY', true),
];
