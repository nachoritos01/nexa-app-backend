# Fix Subscription FK Constraints

**Date:** 2026-03-16
**Status:** Done
**Priority:** HIGH
**Area:** Database
**Found by:** /audit all

---

## Problem

The `subscriptions` and `subscription_items` migrations declare foreign key columns without `->constrained()`, meaning no database-level FK constraints exist in PostgreSQL:

```php
// create_subscriptions_table migration
$table->foreignId('tenant_id'); // Missing ->constrained()

// create_subscription_items migration
$table->foreignId('subscription_id'); // Missing ->constrained()
```

This allows orphaned records — subscription rows can reference non-existent tenants, and subscription items can reference non-existent subscriptions.

## Impact

- Data integrity violations: orphaned subscriptions after tenant deletion
- Orphaned subscription_items after subscription deletion
- Billing inconsistencies and phantom records in reports
- No cascade delete protection at the database level

## Files Affected

| File | Issue |
|------|-------|
| `database/migrations/2026_02_20_191656_create_subscriptions_table.php` | `tenant_id` without `->constrained()->onDelete('cascade')` |
| `database/migrations/2026_02_20_191657_create_subscription_items_table.php` | `subscription_id` without `->constrained()->onDelete('cascade')` |

## Proposed Solution

Create a new migration to add the missing FK constraints:

```php
Schema::table('subscriptions', function (Blueprint $table) {
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});

Schema::table('subscription_items', function (Blueprint $table) {
    $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
});
```

Before running, verify no orphaned records exist with:
```sql
SELECT id FROM subscriptions WHERE tenant_id NOT IN (SELECT id FROM tenants);
SELECT id FROM subscription_items WHERE subscription_id NOT IN (SELECT id FROM subscriptions);
```

## Acceptance Criteria

- [ ] New migration adds FK constraint on `subscriptions.tenant_id` → `tenants.id` with cascade delete
- [ ] New migration adds FK constraint on `subscription_items.subscription_id` → `subscriptions.id` with cascade delete
- [ ] Migration handles orphaned records gracefully (clean up before adding constraints)
- [ ] `composer test` passes
- [ ] `composer analyse` passes
