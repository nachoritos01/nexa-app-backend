# Fix Customer Auth Tenant Scoping

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Security
**Found by:** /audit all

---

## Problem

Customer authentication via `Auth::guard('customer')->attempt()` does not explicitly scope by `tenant_id`. The `Customer` model uses `BelongsToTenant` which adds a global scope, but `Auth::attempt()` may bypass global scopes during credential validation:

```php
// CustomerAuthController.php
if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
    // No explicit tenant_id check in credentials
}
```

The `EnsureCustomerTenant` middleware sets tenant context AFTER authentication, not during. This creates a potential cross-tenant login vulnerability if two customers across different tenants share the same phone number.

## Impact

- Cross-tenant customer login: Customer A (tenant 1) could authenticate as Customer B (tenant 2) if they share credentials
- Data leakage across tenants through the customer portal
- Violation of multi-tenant isolation guarantees

## Files Affected

| File | Issue |
|------|-------|
| `app/Http/Controllers/CustomerAuthController.php` | `Auth::attempt()` without explicit `tenant_id` in credentials |
| `app/Http/Middleware/EnsureCustomerTenant.php` | Tenant context set after auth, not during |

## Proposed Solution

Add `tenant_id` to the credentials array before calling `Auth::attempt()`:

```php
$tenant = Tenant::where('slug', $request->route('tenant'))->firstOrFail();

$credentials = array_merge(
    $request->only('phone', 'password'),
    ['tenant_id' => $tenant->id]
);

if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
    // Now scoped by tenant
}
```

This ensures the WHERE clause includes `tenant_id`, preventing cross-tenant authentication.

## Acceptance Criteria

- [ ] `Auth::attempt()` includes `tenant_id` in credentials
- [ ] Customer cannot authenticate with credentials from a different tenant
- [ ] Feature test verifies cross-tenant login is blocked
- [ ] Existing login flow continues to work for valid customers
- [ ] `composer test` passes
- [ ] `composer analyse` passes
