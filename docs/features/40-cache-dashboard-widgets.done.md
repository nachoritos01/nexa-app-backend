# Cache Dashboard Stats Widgets

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Performance
**Found by:** /audit all

---

## Problem

`StatsOverview` widget executes 4 COUNT/SUM queries on every dashboard page load without any caching, despite a TTL config value existing in `config/saas.php`:

```php
// StatsOverview.php
return [
    Stat::make('Items', Item::where('tenant_id', $tenantId)->count()),
    Stat::make('Orders', Order::where('tenant_id', $tenantId)->count()),
    Stat::make('Customers', Customer::where('tenant_id', $tenantId)->count()),
    Stat::make('Revenue', '$' . number_format((float) Payment::where('tenant_id', $tenantId)->sum('amount'), 2)),
];

// config/saas.php — exists but unused
'dashboard_stats_ttl' => 60,
```

## Impact

- 4 aggregate queries on every single dashboard page view
- With multiple admin users, database pressure multiplies
- Config value `dashboard_stats_ttl` was added but never implemented
- Unnecessary load for stats that don't change frequently

## Files Affected

| File | Issue |
|------|-------|
| `app/Filament/Widgets/StatsOverview.php` | Lines 25-36: 4 queries without cache |
| `config/saas.php` | `dashboard_stats_ttl` config exists but is unused |

## Proposed Solution

Use Laravel's cache with the existing TTL config:

```php
$ttl = config('saas.dashboard_stats_ttl', 60);
$cacheKey = "dashboard_stats_{$tenantId}";

$stats = Cache::remember($cacheKey, $ttl, function () use ($tenantId) {
    return [
        'items' => Item::where('tenant_id', $tenantId)->count(),
        'orders' => Order::where('tenant_id', $tenantId)->count(),
        'customers' => Customer::where('tenant_id', $tenantId)->count(),
        'revenue' => Payment::where('tenant_id', $tenantId)->sum('amount'),
    ];
});
```

Invalidate cache on relevant model events (order created, customer registered, etc.) or rely on TTL expiry.

## Acceptance Criteria

- [ ] Dashboard stats are cached using `dashboard_stats_ttl` from config
- [ ] Cache key is scoped per tenant
- [ ] Stats still update within the configured TTL
- [ ] Cache is invalidated or expires appropriately
- [ ] `composer test` passes
- [ ] `composer analyse` passes
