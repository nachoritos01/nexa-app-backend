# Fix: Impersonate Session Bug

**Status:** Done
**Date:** 2026-03-16

## Problem

The super-admin "Impersonate" feature was completely broken. Clicking Impersonate on any tenant redirected to `/admin/login` instead of the admin dashboard.

### Root Cause

`AuthenticateSession` middleware stores the current user's HMAC'd password hash in `session('password_hash_web')`. When `Auth::login()` switches users during impersonation, the session regenerates but **preserves all existing data**, including the stale hash from the previous user.

**Chain of failure:**
1. Super-admin panel loads → `AuthenticateSession` stores super-admin's password hash as `password_hash_web`
2. Impersonate action calls `Auth::login($owner)` → session ID regenerates but keeps all data
3. Redirect to `/admin` → admin panel's `AuthenticateSession` compares owner's password vs stale super-admin hash → **mismatch** → logout → redirect to login

The same bug existed in `ImpersonationController::stop()` (return to super-admin).

## Solution

Clear `password_hash_web` from the session immediately after `Auth::login()` in both impersonation flows. The middleware will then regenerate the correct hash for the new user on the next request.

### Files Changed

| File | Change |
|------|--------|
| `app/Filament/SuperAdmin/Resources/TenantResource.php` | Added `session()->forget('password_hash_web')` after `Auth::login($owner)` |
| `app/Http/Controllers/ImpersonationController.php` | Added `'password_hash_web'` to `session()->forget()` array |
| `tests/Feature/SuperAdminTest.php` | Added 2 tests: admin panel access post-impersonation, super-admin panel access after stop |

## Tests

- `test_impersonated_user_can_access_admin_panel` — verifies owner can access `/admin` after impersonation
- `test_stop_impersonation_super_admin_can_access_panel` — verifies super-admin can access `/super-admin` after stopping impersonation
