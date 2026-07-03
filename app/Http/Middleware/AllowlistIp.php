<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route/panel to a configured allowlist of client IPs or CIDR ranges.
 *
 * An empty allowlist means "open" (the default — safe for local/dev). Populate
 * `config('security.admin_ip_allowlist')` to lock the surface down in prod.
 *
 * Requires trusted proxies (already set in bootstrap/app.php via trustProxies)
 * so that Request::ip() returns the real client IP behind the platform proxy.
 */
class AllowlistIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('security.admin_ip_allowlist'))
        )));

        if ($allowlist === [] || IpUtils::checkIp((string) $request->ip(), $allowlist)) {
            return $next($request);
        }

        // 404 (not 403) so the protected surface does not reveal it exists.
        abort(404);
    }
}
