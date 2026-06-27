<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function __construct()
    {
        abort_unless(hasModule('customer_portal'), 404);
    }

    public function showLogin(): View
    {
        return view('customer.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Scope login by tenant to prevent cross-tenant authentication
        $tenant = currentTenant();
        if ($tenant) {
            $credentials['tenant_id'] = $tenant->id;
        }

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('customer.orders'));
        }

        return back()->withErrors([
            'phone' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
