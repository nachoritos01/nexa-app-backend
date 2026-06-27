# Fix Trial Commands Eager Loading

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Performance
**Found by:** /audit all

---

## Problem

Three Artisan commands query tenant owners and related data in loops without eager loading, causing N+1 query problems:

**CheckTrialExpiry:**
```php
foreach ($tenants as $tenant) {
    $owner = $tenant->owner; // N+1: lazy loads owner per tenant
    if (! $owner) continue;
    $owner->notify(...);
}
```

**SendRetentionAlerts:**
```php
foreach ($tenants as $tenant) {
    $owner = $tenant->owner; // N+1: lazy loads owner per tenant
}
```

**CalculateHealthScore:**
```php
$lastLogin = User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenant->id))
    ->whereNotNull('last_login_at')
    ->max('last_login_at'); // Subquery per tenant
```

## Impact

- N+1 queries scale linearly with tenant count — hundreds of extra queries in production
- Scheduled commands run on cron, adding periodic database pressure spikes
- `CalculateHealthScore` runs for ALL tenants, making the impact multiplicative

## Files Affected

| File | Issue |
|------|-------|
| `app/Console/Commands/CheckTrialExpiry.php` | `$tenant->owner` lazy loaded in loop (lines 39, 62, 83) |
| `app/Console/Commands/SendRetentionAlerts.php` | `$tenant->owner` lazy loaded in loop (lines 37, 55, 86) |
| `app/Console/Commands/CalculateHealthScore.php` | `whereHas()` subqueries per tenant without eager loading (lines 66, 121, 127) |

## Proposed Solution

Add `->with('owner')` to the initial tenant queries:

```php
// CheckTrialExpiry & SendRetentionAlerts
$tenants = Tenant::where(...)->with('owner')->get();

// CalculateHealthScore
$tenants = Tenant::with(['owner', 'users'])->get();
// Then filter users in memory instead of per-tenant queries
```

## Acceptance Criteria

- [ ] `CheckTrialExpiry` eager loads `owner` relationship
- [ ] `SendRetentionAlerts` eager loads `owner` relationship
- [ ] `CalculateHealthScore` eager loads `owner` and `users` relationships
- [ ] No N+1 queries when commands run
- [ ] `composer test` passes
- [ ] `composer analyse` passes
