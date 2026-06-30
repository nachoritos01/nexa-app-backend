<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn () => hasModule('customer_portal') ? route('customer.login') : route('filament.admin.auth.login'));
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureTenant::class,
        ]);
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureTenant::class,
            'customer.tenant' => \App\Http\Middleware\EnsureCustomerTenant::class,
            'api.tenant' => \App\Http\Middleware\ResolveApiTenant::class,
            'api.pro' => \App\Http\Middleware\EnsureApiAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e): void {
            if (app()->bound('sentry')) {
                $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
                $user = auth()->user();

                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($tenant, $user): void {
                    if ($tenant) {
                        $scope->setTag('tenant_id', (string) $tenant->id);
                        $scope->setTag('tenant_plan', $tenant->plan ?? 'unknown');
                        $scope->setContext('tenant', [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'plan' => $tenant->plan,
                        ]);
                    }
                    if ($user) {
                        $scope->setUser([
                            'id' => (string) $user->getKey(),
                            'email' => $user->email,
                        ]);
                    }
                });
            }
        });

        // For API/JSON requests, hide internal 5xx details behind a generic message.
        // Only when debug is OFF (prod/staging) — in local you keep the full trace.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug')) {
                return null;
            }

            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            // Leave 4xx alone — those already carry safe, meaningful messages
            // (422 validation, 401/403 auth, 404, etc.).
            if ($status < 500) {
                return null;
            }

            return response()->json([
                'message' => 'Ocurrió un error en el servidor. Intenta de nuevo más tarde.',
            ], $status);
        });
    })->create();
