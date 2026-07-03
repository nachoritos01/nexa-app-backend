<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin IP allowlist
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of IPs or CIDR ranges allowed to reach the Filament
    | admin panels (/admin, /super-admin). Empty = open (local/dev default).
    | Example: "203.0.113.4, 10.8.0.0/24"
    |
    | Enforced by App\Http\Middleware\AllowlistIp.
    |
    */
    'admin_ip_allowlist' => env('ADMIN_IP_ALLOWLIST', ''),

];
