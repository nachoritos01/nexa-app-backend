# Super Admin — Tenant Plugin Visibility

**Date:** 2026-03-13
**Status:** Done
**Priority:** Medium
**Branch:** feature/loyalty-program
**Depends on:** Plugin Marketplace (Phase 27)

---

## Problem

The Super Admin TenantResource (`/super-admin/tenants`) shows tenant info (name, plan, owner, health, users) but has **no visibility into which plugins each tenant has activated**. This makes it impossible for a super admin to:

1. Know which features a tenant is using
2. See which tenants are paying for add-on plugins
3. Diagnose plugin-related issues without querying the DB
4. Understand revenue from paid plugin subscriptions

---

## Solution

Added plugin visibility to the Super Admin tenant management in two layers:

1. **Table columns** — quick-glance plugin count + toggleable plugin list
2. **View page** — detailed per-tenant plugin breakdown with billing info

---

## Changes

### 1. "Plugins" count column in tenant table

After `users_count` column, shows active plugin count per tenant.

### 2. "Active Plugins" toggleable column

Comma-separated plugin names, hidden by default (toggleable).

### 3. ViewTenant page

Standard Filament `ViewRecord` page for dedicated tenant detail view.

### 4. Infolist with plugin section

Two sections:
- **Tenant Info** — name, slug, plan (badge), active (icon), trial_ends_at, subscribed_at, health_score (badge with color)
- **Active Plugins** — RepeatableEntry showing plugin name, billing type (badge), activated date, Stripe subscription item ID

### 5. ViewAction in table

Eye icon ViewAction added before EditAction in table actions.

---

## Files

### New (1)
| File | Description |
|------|-------------|
| `app/Filament/SuperAdmin/Resources/TenantResource/Pages/ViewTenant.php` | ViewRecord page for tenant detail |

### Modified (1)
| File | Change |
|------|--------|
| `app/Filament/SuperAdmin/Resources/TenantResource.php` | +active_plugins_count column, +active_plugins_list column, +infolist() method, +ViewAction, +ViewTenant page route |
