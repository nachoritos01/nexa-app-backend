# Billing History — Historial de Facturación

**Date:** 2026-03-13
**Status:** Implemented
**Priority:** Medium
**Branch:** feature/loyalty-program
**Depends on:** Billing & Plan (Phase 25), Plugin Marketplace (Phase 27)

---

## Context

The tenant owner can see their current plan, usage, and active add-ons on `/admin/billing`, but there was no historical record of billing events. When a plan changes, a plugin is activated/deactivated, or a subscription starts/cancels, these events were lost.

## Solution

Created a `billing_events` table and `BillingHistoryService` to record billing-relevant events, displayed as a table section on the existing Billing page.

## Files

### New
| File | Description |
|------|-------------|
| `app/Enums/BillingEventType.php` | Event type enum with labels, icons, colors |
| `database/migrations/2026_03_13_000001_create_billing_events_table.php` | Migration |
| `app/Models/BillingEvent.php` | Model with BelongsToTenant |
| `app/Services/BillingHistoryService.php` | Service to record events |
| `database/seeders/BillingEventSeeder.php` | Backfill existing data |
| `tests/Feature/BillingHistoryTest.php` | Feature tests |

### Modified
| File | Change |
|------|--------|
| `app/Models/Tenant.php` | +billingEvents() relationship |
| `app/Providers/AppServiceProvider.php` | +PlanChanged event listener |
| `app/Http/Controllers/StripeWebhookController.php` | +record subscription start/cancel |
| `app/Services/PluginBillingService.php` | +record plugin activate/deactivate |
| `app/Filament/Pages/Billing.php` | +getBillingHistory() method |
| `resources/views/filament/pages/billing.blade.php` | +Billing History section UI |
| `app/Filament/SuperAdmin/Resources/TenantResource.php` | +record subscription on manual subscribe action |
| `database/seeders/DatabaseSeeder.php` | +BillingEventSeeder |
