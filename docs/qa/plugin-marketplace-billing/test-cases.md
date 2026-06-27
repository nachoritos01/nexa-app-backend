# Test Cases — Plugin Marketplace + Stripe Billing

**Date:** 2026-03-11
**Branch:** feature/plugin-marketplace

---

## TC-01: hasModule() backward compatibility

| Campo | Valor |
|-------|-------|
| **Prerequisito** | No plugin record exists for module |
| **Pasos** | 1. Call `hasModule('payments')` with config enabled, no Plugin row |
| **Resultado esperado** | Returns `true` (backward compatible) |
| **Test automatizado** | `test_has_module_backward_compatible_without_plugin_record` |
| **Estado** | PASS |

## TC-02: hasModule() global kill switch

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Plugin active for tenant, config disabled |
| **Pasos** | 1. Set `config('modules.payments', false)` 2. Call `hasModule('payments')` |
| **Resultado esperado** | Returns `false` even with active plugin |
| **Test automatizado** | `test_has_module_false_when_global_disabled` |
| **Estado** | PASS |

## TC-03: hasModule() memoization

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Plugin active for tenant |
| **Pasos** | 1. Call `hasModule('payments')` 2. Listen DB queries 3. Call again |
| **Resultado esperado** | Second call produces 0 DB queries |
| **Test automatizado** | `test_has_module_memoizes_plugin_query` |
| **Estado** | PASS |

## TC-04: Sidebar hidden when plugin disabled

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Locations plugin exists |
| **Pasos** | 1. Deactivate locations plugin 2. Check `LocationResource::canAccess()` 3. Reload page |
| **Resultado esperado** | `canAccess()` returns false, "Locations" not in sidebar |
| **Test automatizado** | `test_sidebar_hidden_when_plugin_disabled` |
| **Verificacion manual** | Marketplace toggle off → reload → sidebar verified via Chrome DevTools |
| **Estado** | PASS |

## TC-05: Marketplace toggle via Livewire

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Free plugin exists |
| **Pasos** | 1. Call `togglePlugin($id)` 2. Verify activated 3. Call again 4. Verify deactivated |
| **Resultado esperado** | Plugin state toggles correctly |
| **Test automatizado** | `test_marketplace_toggle_via_livewire` |
| **Estado** | PASS |

## TC-06: Tenant isolation

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Two tenants, one plugin |
| **Pasos** | 1. Activate for tenant A 2. Verify tenant B doesn't have it 3. Deactivate for A 4. Verify B unaffected |
| **Resultado esperado** | Each tenant has independent plugin state |
| **Test automatizado** | `test_tenant_isolation_on_plugins` |
| **Estado** | PASS |

## TC-07: Free plugin activation (no Stripe)

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Free plugin, tenant exists |
| **Pasos** | 1. Call `PluginBillingService::activatePlugin()` with free plugin |
| **Resultado esperado** | Plugin activated directly, no Stripe call, billing_type = included/free |
| **Test automatizado** | `test_free_plugin_activation_no_stripe_call` |
| **Estado** | PASS |

## TC-08: Paid plugin requires subscription

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Paid plugin with stripe_price_id, tenant without subscription |
| **Pasos** | 1. Call `PluginBillingService::activatePlugin()` with paid plugin |
| **Resultado esperado** | Throws RuntimeException |
| **Test automatizado** | `test_paid_plugin_requires_subscription` |
| **Estado** | PASS |

## TC-09: Subscription deletion deactivates paid plugins

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Tenant with paid plugin active |
| **Pasos** | 1. Call `deactivateAllPaidPlugins()` |
| **Resultado esperado** | All paid plugins deactivated, free/included untouched |
| **Test automatizado** | `test_subscription_deletion_deactivates_paid_plugins` |
| **Estado** | PASS |

## TC-10: Billing page shows add-ons

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Tenant with paid plugin active |
| **Pasos** | 1. Visit /admin/billing |
| **Resultado esperado** | "Active Add-ons" section visible with plugin name, price, total |
| **Test automatizado** | `test_billing_page_shows_plugin_addons` |
| **Estado** | PASS |

## TC-11: Marketplace accessibility

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Marketplace page loaded |
| **Pasos** | 1. Take a11y snapshot |
| **Resultado esperado** | Toggle has `role="switch"`, `aria-checked`, `aria-label="Toggle {name}"` |
| **Verificacion manual** | Chrome DevTools a11y snapshot verified |
| **Estado** | PASS |

## TC-12: Paid plugin "Subscribe first" badge

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Tenant without Stripe subscription, paid plugin exists |
| **Pasos** | 1. Visit marketplace |
| **Resultado esperado** | Paid plugins show "Subscribe first" link to billing, no toggle |
| **Verificacion manual** | Chrome DevTools — new starter tenant verified |
| **Estado** | PASS |

## TC-13: Marketplace permission check

| Campo | Valor |
|-------|-------|
| **Prerequisito** | User without `plugins.manage` permission |
| **Pasos** | 1. Visit /admin/marketplace |
| **Resultado esperado** | 403 Forbidden |
| **Test automatizado** | `test_marketplace_denied_without_permission` |
| **Estado** | PASS |

## TC-14: Seeder idempotency

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Empty database |
| **Pasos** | 1. Run PluginSeeder 2. Count plugins 3. Run again 4. Count again |
| **Resultado esperado** | Same count (7), no duplicates |
| **Test automatizado** | `test_plugin_seeder_idempotent` |
| **Estado** | PASS |

## TC-15: Plugin factory withStripePrice state

| Campo | Valor |
|-------|-------|
| **Prerequisito** | — |
| **Pasos** | 1. Create plugin with `withStripePrice('price_test')` |
| **Resultado esperado** | `is_free=false`, `price_monthly=999`, `stripe_price_id='price_test'` |
| **Test automatizado** | `test_plugin_factory_with_stripe_price_state` |
| **Estado** | PASS |
