# Fix TenantResource N+1 in Plugins

**Date:** 2026-03-16
**Status:** Done
**Priority:** HIGH
**Area:** Performance
**Found by:** /audit all

---

## Problem

`TenantResource` table executes 2 extra queries per row to display plugin information — one `count()` and one `pluck()` — using `getStateUsing()` callbacks without eager loading:

```php
Tables\Columns\TextColumn::make('active_plugins_count')
    ->label('Plugins')
    ->getStateUsing(fn (Tenant $record) => $record->plugins()->wherePivot('is_active', true)->count())
    ->alignCenter(),

Tables\Columns\TextColumn::make('active_plugins_list')
    ->label('Active Plugins')
    ->getStateUsing(fn (Tenant $record) => $record->plugins()->wherePivot('is_active', true)->pluck('name')->join(', '))
```

With 20 rows displayed, this means 40 extra queries on every table load.

## Impact

- 40+ extra queries for a 20-row tenant list in SuperAdmin
- SuperAdmin dashboard becomes slow as tenant count grows
- Database pressure from repeated pivot table queries

## Files Affected

| File | Issue |
|------|-------|
| `app/Filament/SuperAdmin/Resources/TenantResource.php` | Lines 103-111: Two `getStateUsing()` callbacks query plugins per row |

## Proposed Solution

Use `withCount` and eager loading on the table query to preload plugin data:

1. Override `getEloquentQuery()` in the ListTenants page to add:
   ```php
   ->withCount(['plugins as active_plugins_count' => fn ($q) => $q->wherePivot('is_active', true)])
   ->with(['plugins' => fn ($q) => $q->wherePivot('is_active', true)])
   ```

2. Update the columns to use the preloaded data instead of callbacks.

## Acceptance Criteria

- [ ] Tenant list table loads with constant query count regardless of row count
- [ ] Plugin count and list display correctly
- [ ] No N+1 queries visible in debug bar / query log
- [ ] `composer test` passes
- [ ] `composer analyse` passes
