<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function stop(): RedirectResponse
    {
        $superAdminId = session('impersonating_from');

        if (! $superAdminId) {
            return redirect('/admin');
        }

        $superAdmin = User::find($superAdminId);

        if (! $superAdmin || ! $superAdmin->is_super_admin) {
            session()->forget('impersonating_from');

            return redirect('/admin');
        }

        Auth::login($superAdmin);
        session()->forget(['impersonating_from', 'tenant_id', 'password_hash_web']);

        return redirect('/super-admin');
    }
}
