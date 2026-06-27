# Plugin Configuration Reference

All plugins are managed in `database/seeders/PluginSeeder.php` and stored in the `plugins` table. Each plugin has a global module flag in `config/modules.php` and is activated per-tenant via the `tenant_plugins` pivot table.

---

## Plugin Overview

| # | Plugin | Slug | Category | Free | Price | Plans | Modules |
|---|--------|------|----------|------|-------|-------|---------|
| 1 | Payment Processing | `payments` | billing | Yes | — | starter, growth, pro | `payments` |
| 2 | Customer Portal | `customer_portal` | engagement | Yes | — | growth, pro | `customer_portal` |
| 3 | Multiple Locations | `locations` | operations | Yes | — | starter, growth, pro | `locations` |
| 4 | API Access | `api` | developer | Yes | — | pro | `api` |
| 5 | CSV Exports | `exports` | reporting | Yes | — | growth, pro | `exports` |
| 6 | Advanced Analytics | `advanced_analytics` | reporting | No | $19.99/mo | — (add-on only) | — |
| 7 | White Label | `white_label` | general | No | $49.99/mo | — (add-on only) | — |
| 8 | Loyalty Program | `loyalty` | engagement | No | $9.99/mo | growth, pro | `loyalty`, `customer_portal` |

---

## How Plugins Work

### Activation Flow

1. **Auto-activation**: When `PluginSeeder` runs, plugins listed in `included_in_plans` are auto-activated for tenants on those plans.
2. **Manual activation**: Tenants can toggle plugins on/off in the Plugin Marketplace (`/admin/marketplace`).
3. **Paid plugins**: Require an active Stripe subscription. Tenants see a "Subscribe first" badge until they have billing set up. If included in their plan, the plugin is free; otherwise, it's billed as a subscription item.

### Gating Mechanism

```
hasModule('module_name')
```

This helper checks two layers:
1. **Global flag**: `config/modules.php` — if `false`, the module is completely disabled regardless of tenant.
2. **Tenant plugin**: Checks if the tenant has the corresponding plugin activated in `tenant_plugins`.

Used in routes, controllers, Filament resources, and Blade views to conditionally show/hide features.

### Required Modules

A plugin can declare `required_modules` — an array of global module flags (`config/modules.php`) that must ALL be enabled for the plugin to be available. If any required module is globally disabled, the plugin cannot be activated.

---

## Plugin Details

### 1. Payment Processing (`payments`)

- **Purpose**: Stripe payment processing, invoices, billing management.
- **Plans**: All plans (starter, growth, pro) — auto-activated.
- **Modules**: Requires `payments` in `config/modules.php`.
- **Gating**: Payment routes, billing controller, Stripe webhook handler.
- **Free**: Yes — core functionality.

### 2. Customer Portal (`customer_portal`)

- **Purpose**: Self-service portal for customers to view orders, payments, addresses, and profile.
- **Plans**: growth, pro — auto-activated. Starter tenants can activate manually (free).
- **Modules**: Requires `customer_portal` in `config/modules.php`.
- **Gating**: Portal routes (`/my-account/*`), `CustomerPortalController`, `CustomerResource` in Filament.
- **Free**: Yes — included for growth+, manually activatable for starter.
- **Note**: Other plugins (like Loyalty) may depend on this being active.

### 3. Multiple Locations (`locations`)

- **Purpose**: Manage multiple business locations with separate inventories.
- **Plans**: All plans — auto-activated.
- **Modules**: Requires `locations` in `config/modules.php`.
- **Gating**: `LocationResource` in Filament (sidebar hidden when disabled).
- **Free**: Yes — core functionality.

### 4. API Access (`api`)

- **Purpose**: RESTful API access for integrations and automation (Sanctum tokens).
- **Plans**: pro only — auto-activated.
- **Modules**: Requires `api` in `config/modules.php`.
- **Gating**: API routes, `ApiSettingsResource` in Filament, token generation.
- **Free**: Yes — but restricted to Pro plan.
- **Note**: Rate limits vary by plan (Pro: 300 req/min).

### 5. CSV Exports (`exports`)

- **Purpose**: Export orders, customers, and reports to CSV files.
- **Plans**: growth, pro — auto-activated.
- **Modules**: Requires `exports` in `config/modules.php`.
- **Gating**: Export routes (`/admin/exports/*`), export buttons in Filament.
- **Free**: Yes — included for growth+.

### 6. Advanced Analytics (`advanced_analytics`)

- **Purpose**: Detailed dashboards with cohort analysis, churn prediction, and revenue forecasting.
- **Plans**: None — pure paid add-on.
- **Modules**: None required (standalone feature).
- **Price**: $19.99/mo via Stripe (`price_advanced_analytics_monthly`).
- **Note**: Requires active Stripe subscription to activate. Not tied to any `config/modules.php` flag.

### 7. White Label (`white_label`)

- **Purpose**: Remove branding, use custom domain, and customize emails with tenant logo.
- **Plans**: None — pure paid add-on.
- **Modules**: None required (standalone feature).
- **Price**: $49.99/mo via Stripe (`price_white_label_monthly`).
- **Note**: Highest-price add-on. Custom domain and email customization.

### 8. Loyalty Program (`loyalty`)

- **Purpose**: Points, tiers (Bronze/Silver/Gold/VIP), and redeemable rewards to boost customer retention.
- **Plans**: growth, pro — auto-activated. Starter tenants pay $9.99/mo add-on.
- **Modules**: Requires BOTH `loyalty` AND `customer_portal` in `config/modules.php`.
- **Price**: $9.99/mo via Stripe (`price_loyalty_monthly`) for Starter plan.
- **Gating**: Portal loyalty tab, `LoyaltyRewardResource` in Filament, loyalty section in `CustomerResource`.
- **Dependencies**: Customer Portal must be active — the loyalty tab lives inside the portal.
- **Configurable per tenant**: Points per amount, first purchase bonus, referral bonus, coupon validity via `tenants.settings['loyalty']` JSON.

---

## Plan Comparison

| Feature | Starter | Growth | Pro |
|---------|---------|--------|-----|
| Payment Processing | Included | Included | Included |
| Multiple Locations | Included | Included | Included |
| Customer Portal | Manual | Included | Included |
| CSV Exports | — | Included | Included |
| API Access | — | — | Included |
| Loyalty Program | $9.99/mo | Included | Included |
| Advanced Analytics | $19.99/mo | $19.99/mo | $19.99/mo |
| White Label | $49.99/mo | $49.99/mo | $49.99/mo |

---

## Key Files

| File | Purpose |
|------|---------|
| `config/modules.php` | Global module flags (on/off per environment) |
| `database/seeders/PluginSeeder.php` | Plugin definitions and auto-activation logic |
| `app/Models/Plugin.php` | Plugin model with `isIncludedInPlan()`, `isAvailable()` |
| `app/helpers.php` → `hasModule()` | Two-layer gating check (global + tenant) |
| `app/Filament/Pages/Marketplace.php` | Tenant marketplace UI for toggling plugins |
| `app/Services/PluginBillingService.php` | Stripe subscription item management for paid plugins |

---

*Last updated: 2026-03-13*
