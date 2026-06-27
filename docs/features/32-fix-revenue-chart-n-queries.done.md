# Fix RevenueChart N+1 Queries

**Date:** 2026-03-16
**Status:** Done
**Priority:** HIGH
**Area:** Performance
**Found by:** /audit all

---

## Problem

`RevenueChart` widget executes 30 separate SUM queries (one per day) in a loop to build the last 30 days of revenue data:

```php
$data = collect(range(29, 0))->map(function ($daysAgo) {
    $date = now()->subDays($daysAgo);
    return [
        'date' => $date->format('m/d'),
        'total' => Order::whereDate('created_at', $date)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->sum('total'), // 1 query per day = 30 queries
    ];
});
```

## Impact

- 30 database queries on every dashboard load for this single widget
- Slow dashboard rendering, especially under load
- Unnecessary database pressure on every page view

## Files Affected

| File | Issue |
|------|-------|
| `app/Filament/Widgets/RevenueChart.php` | Lines 23-31: SUM query inside `collect()->map()` loop |

## Proposed Solution

Replace the loop with a single aggregated query using `GROUP BY DATE()`:

```php
$startDate = now()->subDays(29)->startOfDay();

$revenue = Order::where('created_at', '>=', $startDate)
    ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
    ->selectRaw('DATE(created_at) as date, SUM(total) as total')
    ->groupByRaw('DATE(created_at)')
    ->pluck('total', 'date');

// Fill missing days with 0
$data = collect(range(29, 0))->map(function ($daysAgo) use ($revenue) {
    $date = now()->subDays($daysAgo);
    return [
        'date' => $date->format('m/d'),
        'total' => $revenue[$date->toDateString()] ?? 0,
    ];
});
```

This reduces 30 queries to 1.

## Acceptance Criteria

- [ ] RevenueChart executes a single query instead of 30
- [ ] Chart data remains identical (same values, same date range)
- [ ] Dashboard loads noticeably faster
- [ ] `composer test` passes
- [ ] `composer analyse` passes
