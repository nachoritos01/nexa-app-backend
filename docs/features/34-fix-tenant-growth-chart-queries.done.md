# Fix TenantGrowthChart Loop Queries

**Date:** 2026-03-16
**Status:** Done
**Priority:** HIGH
**Area:** Performance
**Found by:** /audit all

---

## Problem

`TenantGrowthChart` executes 8 separate COUNT queries in a loop (one per week) to build the growth chart:

```php
for ($i = 7; $i >= 0; $i--) {
    $weekStart = now()->subWeeks($i)->startOfWeek();
    $weekEnd = now()->subWeeks($i)->endOfWeek();

    $count = Tenant::whereBetween('created_at', [$weekStart, $weekEnd])->count();
    $weeks->push($count);
    $labels->push($weekStart->format('d M'));
}
```

## Impact

- 8 COUNT queries on every SuperAdmin dashboard load
- Unnecessary database pressure for a simple chart
- Latency adds up with other dashboard widgets

## Files Affected

| File | Issue |
|------|-------|
| `app/Filament/SuperAdmin/Widgets/TenantGrowthChart.php` | Lines 19-26: COUNT query inside `for` loop |

## Proposed Solution

Replace the loop with a single aggregated query using `GROUP BY` week:

```php
$startDate = now()->subWeeks(7)->startOfWeek();

$counts = Tenant::where('created_at', '>=', $startDate)
    ->selectRaw("DATE_TRUNC('week', created_at) as week, COUNT(*) as count")
    ->groupByRaw("DATE_TRUNC('week', created_at)")
    ->pluck('count', 'week');

// Fill missing weeks with 0
for ($i = 7; $i >= 0; $i--) {
    $weekStart = now()->subWeeks($i)->startOfWeek();
    $weeks->push($counts[$weekStart->toDateString()] ?? 0);
    $labels->push($weekStart->format('d M'));
}
```

This reduces 8 queries to 1.

## Acceptance Criteria

- [ ] TenantGrowthChart executes a single query instead of 8
- [ ] Chart data remains identical (same values, same week labels)
- [ ] `composer test` passes
- [ ] `composer analyse` passes
