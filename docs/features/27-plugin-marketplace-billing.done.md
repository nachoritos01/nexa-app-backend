# Plugin Marketplace + Stripe Billing

**Date:** 2026-03-11
**Status:** Done
**Priority:** High
**Branch:** feature/plugin-marketplace
**Depends on:** Phase 22 (Modular architecture), Billing infrastructure

---

## Problem

The plugin marketplace (Phase 1) allows tenants to toggle plugins, but:
1. **No billing integration** — paid plugins toggle without charging
2. **Performance** — `hasModule()` runs N DB queries per request (no memoization)
3. **Missing indexes** — `tenant_plugins` and `plugins` tables lack composite indexes
4. **Sidebar leak** — nav items visible even when their plugin is disabled
5. **Accessibility** — marketplace toggles lack aria-labels and loading indicators
6. **Seeder drift** — `firstOrCreate` ignores updates to plugin definitions on redeploy

---

## Solution

### Phase 1: Foundation Improvements

| Task | File | Description |
|------|------|-------------|
| Memoize `hasModule()` | `app/helpers.php` | Static array cache to avoid repeated DB queries per request |
| Add indexes | New migration | Composite indexes on `(tenant_id, is_active)` and `(is_active, sort_order)` |
| Seeder sync | `PluginSeeder.php` | `firstOrCreate` → `updateOrCreate` so definitions stay current |
| Sidebar gating | 4 Filament resources/pages | `canAccess()` checks `hasModule()` |
| Accessibility | `marketplace.blade.php` | `aria-label`, `wire:loading` spinner on toggle |

### Phase 2: Stripe Billing for Paid Plugins

| Task | File | Description |
|------|------|-------------|
| `PluginBillingService` | New service | `activatePlugin()` adds Stripe subscription item, `deactivatePlugin()` removes it, `syncPluginsFromSubscription()` reconciles |
| Marketplace UX | Marketplace page + blade | Confirmation modal for paid plugins, "Subscribe first" badge |
| Webhook sync | `StripeWebhookController` | Sync plugins on subscription update/delete |
| Billing add-ons | Billing page + blade | Show active paid plugins with cost |
| SuperAdmin form | `PluginResource` | `stripe_price_id` required for paid, `required_modules` as CheckboxList |

### Phase 3: Tests

| Test | Description |
|------|-------------|
| `test_has_module_memoizes_plugin_query` | Single DB query for repeated `hasModule()` calls |
| `test_sidebar_hidden_when_plugin_disabled` | `LocationResource::canAccess() === false` |
| `test_marketplace_toggle_via_livewire` | Livewire dispatch test |
| `test_tenant_isolation_on_plugins` | Tenant A toggle doesn't affect tenant B |
| `test_free_plugin_activation_no_stripe_call` | Free plugin skips Stripe |
| `test_paid_plugin_requires_subscription` | No subscription → error |
| `test_paid_plugin_adds_stripe_subscription_item` | Mock Cashier |
| `test_paid_plugin_deactivation_removes_stripe_item` | Mock Cashier |
| `test_subscription_deletion_deactivates_paid_plugins` | Webhook handler |
| `test_billing_page_shows_plugin_addons` | Add-ons section |

---

## Technical Notes

- Paid plugins use Cashier's `$subscription->addPriceAndInvoice()` to add line items
- `stripe_subscription_item_id` stored in `tenant_plugins` pivot for clean removal
- `PluginBillingService::syncPluginsFromSubscription()` called from webhook for reconciliation
- Memoization uses a static array (request-scoped), not long-lived cache

---

## Files

### New (~3)
- `app/Services/PluginBillingService.php`
- `database/migrations/..._add_indexes_to_plugins_and_tenant_plugins.php`
- `tests/Feature/PluginBillingTest.php`

### Modified (~14)
- `app/helpers.php`
- `database/seeders/PluginSeeder.php`
- `app/Filament/Pages/Marketplace.php`
- `resources/views/filament/pages/marketplace.blade.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Filament/Pages/Billing.php`
- `resources/views/filament/pages/billing.blade.php`
- `app/Filament/SuperAdmin/Resources/PluginResource.php`
- `app/Filament/Resources/LocationResource.php`
- `app/Filament/Pages/CashRegister.php`
- `app/Filament/Pages/ApiSettings.php`
- `app/Filament/Pages/WebhookSettings.php`
- `database/factories/PluginFactory.php`
- `tests/Feature/PluginMarketplaceTest.php`
