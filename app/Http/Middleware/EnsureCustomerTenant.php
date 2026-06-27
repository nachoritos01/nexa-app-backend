<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer instanceof Customer && $customer->tenant_id) {
            $tenant = $customer->tenant()->withoutGlobalScopes()->first();

            if ($tenant) {
                app()->instance('currentTenant', $tenant);
            }
        }

        return $next($request);
    }
}
