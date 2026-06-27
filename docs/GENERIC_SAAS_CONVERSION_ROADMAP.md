# Generic SaaS Template — Full Roadmap

> **Status:** 100% COMPLETE — All 30 phases done (22 conversion + 8 post-conversion)
> **Tests:** 301 tests, 773 assertions — ALL PASSING
> **PHPStan:** Level 5, 0 errors
> **Created:** 2026-03-09 | **Last Updated:** 2026-03-16

This document covers the full conversion of the original vertical SaaS into a **100% generic multi-tenant SaaS template** (Phases 1–22), plus all post-conversion features (Phases 23–30).

---

## Timeline & PRs

| PR | Branch | Date Merged | Phases | Description |
|----|--------|-------------|--------|-------------|
| #2 | `feature/generic-saas-template` | 2026-03-11 | 1–22 | Convert vertical SaaS to generic template |
| #3 | `feature/plugin-marketplace` | 2026-03-13 | 23–25 | Plugin marketplace with Stripe billing |
| #4 | `feature/activate-module-flags` | 2026-03-13 | 26 | Wire hasModule() into routes/Filament |
| #5 | `feature/billing-history` | 2026-03-16 | 29–30 | Billing history + Cashier migration |
| #6 | `feature/loyalty-program` | OPEN | 27–28 | Loyalty program with points/tiers/rewards |

---

## Table of Contents

### Completed (Phases 1–13)
1. [Phase 1 — Delete Domain-Specific Files](#phase-1--delete-domain-specific-files)
2. [Phase 2 — Create Generalized Models](#phase-2--create-generalized-models)
3. [Phase 3 — Write New Migrations](#phase-3--write-new-migrations)
4. [Phase 4 — Rewrite Filament Resources](#phase-4--rewrite-filament-resources)
5. [Phase 5 — Rewrite Filament Widgets](#phase-5--rewrite-filament-widgets)
6. [Phase 6 — Rewrite Filament Pages](#phase-6--rewrite-filament-pages)
7. [Phase 7 — Update Controllers & Views](#phase-7--update-controllers--views)
8. [Phase 8 — Update Factories, Seeders & Tests](#phase-8--update-factories-seeders--tests)
9. [Phase 9 — Simplify Configs](#phase-9--simplify-configs)
10. [Phase 10 — Clean .env.example](#phase-10--clean-envexample)
11. [Phase 11 — Update Enums](#phase-11--update-enums)
12. [Phase 12 — Broken Reference Sweep](#phase-12--broken-reference-sweep)
13. [Phase 13 — Validation](#phase-13--validation)

### Completed (Phases 14–22)
14. [Phase 14 — Fix Critical Bugs](#phase-14--fix-critical-bugs)
15. [Phase 15 — Translate Config & Backend Strings](#phase-15--translate-config--backend-strings)
16. [Phase 16 — Translate Customer Portal Views](#phase-16--translate-customer-portal-views)
17. [Phase 17 — Translate Public Views & Markdown Content](#phase-17--translate-public-views--markdown-content)
18. [Phase 18 — Genericize Routes](#phase-18--genericize-routes)
19. [Phase 19 — Remove Domain-Specific Hardcoding](#phase-19--remove-domain-specific-hardcoding)
20. [Phase 20 — Delete Domain-Specific Documentation](#phase-20--delete-domain-specific-documentation)
21. [Phase 21 — Update Context & Meta Files](#phase-21--update-context--meta-files)
22. [Phase 22 — Module/Extension Infrastructure](#phase-22--moduleextension-infrastructure)
23. [Priority Matrix](#priority-matrix)

---

## Completed Phases (1–13)

### Phase 1 — Delete Domain-Specific Files

**Status:** DONE

Deleted 79 domain-specific files:

| Category | Files deleted | Examples |
|----------|-------------|----------|
| Models | 6 | `Product.php`, `Branch.php`, `OrderItem.php`, `ProductModel.php`, `DimensionLimit.php`, `GarmentType.php` |
| Services | 8 | `PricingCalculator.php`, `ProductionTracker.php`, `DeliveryService.php`, `LoyaltyService.php` |
| Livewire components | 12 | `ProductCatalog.php`, `OrderWizard.php`, `ProductionPanel.php`, `LoyaltyDashboard.php` |
| Blade views | 20+ | Domain-specific views for products, production, loyalty, delivery tracking |
| Filament Resources | 5 | `ProductResource.php`, `BranchResource.php` + relation managers |
| Enums | 3 | `DeliveryType.php`, `GarmentCategory.php`, `ProductionStage.php` |
| Observers/Policies | 4 | Domain-specific observers and policies |
| Migrations | 15+ | Old domain-specific migration files |

### Phase 2 — Create Generalized Models

**Status:** DONE

Created 3 new generalized models to replace domain-specific ones:

| New Model | Replaces | Key design decisions |
|-----------|----------|---------------------|
| `Item` | `Product` | JSON columns for `variants`, `photos`, `tags`, `metadata` — works for any product type |
| `Location` | `Branch` | Added `type`, `country`, `email`, `metadata` — works for stores, warehouses, offices |
| `OrderLine` | `OrderItem` | Added `variant`, `metadata` — generic line item for any order |

Updated existing models:

| Model | Changes |
|-------|---------|
| `Order` | Removed 16 shipping/delivery fields, added `location_id`, `tax`, `metadata` |
| `Customer` | Removed loyalty-specific fields, added `tags`, `metadata`, `is_active` |
| `Invoice` | Removed CFDI/SAT fields, added `number`, `customer_tax_id`, `metadata` |
| `Quote` | Removed `ProductModel` dependency, added `title`, `items` (JSON), `tax`, `total` |

### Phase 3 — Write New Migrations

**Status:** DONE

Wrote 9 new migrations:
- `create_items_table` — replaces products
- `create_locations_table` — replaces branches
- `create_order_lines_table` — replaces order_items
- `modify_orders_table` — drop shipping fields, add location_id/tax/metadata
- `modify_customers_table` — drop loyalty fields, add tags/metadata/is_active
- `modify_invoices_table` — drop CFDI fields, add number/customer_tax_id/metadata
- `modify_quotes_table` — drop product_model_id, add title/items/tax/total
- Drop tables: `products`, `branches`, `order_items`, `product_models`, `dimension_limits`, `garment_types`

### Phase 4 — Rewrite Filament Resources

**Status:** DONE

| Resource | Change |
|----------|--------|
| `ItemResource` | New — replaces `ProductResource`. Generic form with variants/photos/tags JSON fields |
| `LocationResource` | New — replaces `BranchResource`. Generic form with type selector, country, metadata |
| `OrderResource` | Updated — references `OrderLine` instead of `OrderItem`, uses `location_id` |
| `CustomerResource` | Updated — removed loyalty columns, added tags/metadata |
| `InvoiceResource` | Updated — removed CFDI fields |
| `QuoteResource` | Updated — uses items JSON instead of ProductModel relation |

### Phase 5 — Rewrite Filament Widgets

**Status:** DONE

Updated all dashboard widgets to use new model names and relationships. Translated widget labels to English.

### Phase 6 — Rewrite Filament Pages

**Status:** DONE

Updated Filament pages (Dashboard, TenantSettings, Billing, Onboarding) to reference new models and use English labels.

### Phase 7 — Update Controllers & Views

**Status:** DONE

| Controller | Changes |
|------------|---------|
| `DashboardController` | Updated queries to use `Item`, `Location`, `OrderLine` |
| `PdfGenerator` | Updated PDF generation for new Quote/Order structure |
| `ExportController` | Updated CSV exports for new model fields |
| `OrderController` (API) | Updated to use `OrderLine`, `location_id` |
| `CustomerPortalController` | Updated to use new relationships |
| `BillingController` | Updated plan limit labels |

Updated Blade views for order detail, customer portal, PDF templates.

### Phase 8 — Update Factories, Seeders & Tests

**Status:** DONE

| Category | Changes |
|----------|---------|
| Factories | Created `ItemFactory`, `LocationFactory`, `OrderLineFactory`. Updated `OrderFactory`, `CustomerFactory` |
| Seeders | Created `ItemSeeder`, `LocationSeeder`. Updated `OrderSeeder`, `CustomerSeeder` |
| Tests | Updated 260 tests to use new model names and relationships |

### Phase 9 — Simplify Configs

**Status:** DONE

| Config | Changes |
|--------|---------|
| `config/business.php` | Removed DTF-specific keys (techniques, materials, sizes). Generic business config |
| `config/saas.php` | Updated limit keys: `max_products` → `max_items`, `max_branches` → `max_locations` |

### Phase 10 — Clean .env.example

**Status:** DONE

Removed domain-specific env vars (Conekta keys, DTF settings, delivery config). Added generic placeholder vars.

### Phase 11 — Update Enums

**Status:** DONE

| Enum | Change |
|------|--------|
| `OrderStatus` | 6 English states: `Draft`, `Pending`, `Confirmed`, `InProgress`, `Completed`, `Cancelled` (was 7 Spanish) |
| `OrderPriority` | String-backed: `low`, `normal`, `high`, `urgent` (was int-backed) |
| `PaymentMethod` | `Cash`, `Card`, `Transfer`, `Other` (removed `Oxxo`, `Spei`) |
| `DeliveryType` | **DELETED** entirely |

### Phase 12 — Broken Reference Sweep

**Status:** DONE

Exhaustive sweep to fix all broken references after model renames:

| File | Fix |
|------|-----|
| `DashboardController` | `Product::` → `Item::`, `Branch::` → `Location::` |
| `PdfGenerator` | Updated quote PDF to use `$quote->items` JSON |
| Order PDF view | `$order->orderItems` → `$order->lines` |
| Customer portal views | Updated all relation references |
| `suspended.blade.php` | Updated plan feature display |
| `ExportController` | `products` → `items`, `branches` → `locations` |
| `OrderController` (API) | `orderItems` → `lines` |
| `CustomerPortalController` | Updated all model references |

### Phase 13 — Validation

**Status:** DONE

| Check | Result |
|-------|--------|
| PHPStan level 5 | **PASS** — 0 new errors (87 in baseline, all pre-existing larastan false positives) |
| PHPStan baseline | Regenerated — removed 27 stale entries for deleted files |
| Tests | **242 passed**, 18 failures (environment-only: missing Vite manifest, Filament tenant context in test) |
| Manual review | All Filament resources load, no broken references in views |

---

## Completed Phases (14–22)

### Phase 14 — Fix Critical Bugs

**Status:** DONE

**Objective:** Fix breaking issues that prevent `migrate:fresh --seed` and tests from passing.

### 14.1 ItemSeeder column mismatch

| File | Change |
|------|--------|
| `database/seeders/ItemSeeder.php` | Replace `'title'` with `'name'` in `firstOrCreate` key and data arrays (~3 occurrences) |

The `items` migration defines a `name` column, but the seeder uses `title`. This causes seeder failure.

### 14.2 Fix test failures (18 remaining)

| Issue | Files | Fix |
|-------|-------|-----|
| Missing Vite manifest | `tests/` setup | Add `npm run build` to CI or mock Vite in `TestCase.php` with `$this->withoutVite()` |
| Filament tenant context | Feature tests | Set tenant context in test setup via `Filament::setTenant()` |

### Acceptance Criteria
- [x] `php artisan migrate:fresh --seed` runs without errors
- [x] `composer test` — all 301 tests pass (0 failures)

---

## Phase 15 — Translate Config & Backend Strings

**Status:** DONE

**Objective:** Replace all remaining Spanish strings in PHP backend code with English equivalents.

### 15.1 Config: `config/saas.php` (18 strings)

Lines 29–77 — Plan feature labels:

| Spanish | English |
|---------|---------|
| `50 pedidos/mes` | `50 orders/month` |
| `2 usuarios` | `2 users` |
| `1 sucursal` | `1 location` |
| `20 productos` | `20 items` |
| `200 clientes` | `200 customers` |
| `200 pedidos/mes` | `200 orders/month` |
| `5 usuarios` | `5 users` |
| `3 sucursales` | `3 locations` |
| `100 productos` | `100 items` |
| `1,000 clientes` | `1,000 customers` |
| `Exportar reportes CSV` | `CSV report export` |
| `Pedidos ilimitados` | `Unlimited orders` |
| `Usuarios ilimitados` | `Unlimited users` |
| `Sucursales ilimitadas` | `Unlimited locations` |
| `Productos ilimitados` | `Unlimited items` |
| `Clientes ilimitados` | `Unlimited customers` |
| `Soporte prioritario` | `Priority support` |

### 15.2 Controllers (5 files, ~25 strings)

| File | Strings to translate |
|------|---------------------|
| `app/Http/Controllers/PageController.php` | 4 — page titles (`Preguntas Frecuentes`, `Policies`, `Terms y Condiciones`, `Policies de Uso`) |
| `app/Http/Controllers/PaymentStatusController.php` | 2 — flash messages (`Tu pago fue recibido...`, `Hubo un problema...`) |
| `app/Http/Controllers/BillingController.php` | 5 — flash messages + limit labels (`usuarios`, `sucursales`, `productos`, `clientes`, `Plan no disponible...`, `Plan actualizado...`, `No puedes bajar...`) |
| `app/Http/Controllers/CustomerAddressController.php` | 5 — flash messages (`Direccion agregada`, `Direccion actualizada`, `Direccion eliminada`, `Direccion predeterminada actualizada`, `Maximo 5 direcciones...`) |
| `app/Http/Controllers/ExportController.php` | 4 arrays — CSV column headers (orders, customers, payments, product profitability — all Spanish) |

### 15.3 Notifications (6 files, ~40 strings)

| File | Content |
|------|---------|
| `app/Notifications/TrialExpiringNotification.php` | Subject, greeting, body lines — `Tu prueba gratuita termina en...` |
| `app/Notifications/TrialExpiredNotification.php` | Subject, greeting, body, action — `Tu periodo de prueba ha terminado` |
| `app/Notifications/TenantSuspendedNotification.php` | Subject, greeting, body, action — `Tu cuenta ha sido suspendida` |
| `app/Notifications/InactivityReminderNotification.php` | Subject, greeting, body, action — `Te echamos de menos` |
| `app/Notifications/WinbackNotification.php` | Subject, greeting, body, action — `Vuelve — 1 mes gratis` |
| `app/Notifications/TeamInviteNotification.php` | Role label array (`Propietario`, `Administrador`, `Ventas`, `Produccion`, `Contabilidad`), subject, greeting, body, actions |

### 15.4 Observer (1 file, 2 strings)

| File | Strings |
|------|---------|
| `app/Observers/OrderObserver.php` | `"Pedido #{$order->id}"` → `"Order #{$order->id}"`, `"Estado actualizado: ..."` → `"Status updated: ..."` |

### Acceptance Criteria
- [x] `grep -ri 'pedido\|usuario\|sucursal\|producto\|cliente\|direccion' app/ config/` returns zero hits (excluding comments/docs)
- [x] `composer analyse` — 0 new errors
- [x] CSV exports have English column headers

---

## Phase 16 — Translate Customer Portal Views

**Status:** DONE

**Objective:** Translate all customer-facing Blade templates from Spanish to English.

### Files & estimated string counts

| File | Strings | Key content |
|------|---------|-------------|
| `resources/views/customer/portal.blade.php` | ~15 | Tab labels (`Mis Pedidos`, `Pagos`, `Direcciones`, `Perfil`, `Configuracion`), footer links, sign-out |
| `resources/views/customer/login.blade.php` | ~8 | Page title, form labels (`Telefono`), button (`Iniciar Sesion`), back link |
| `resources/views/customer/tabs/orders.blade.php` | ~12 | Section title, filter labels (`Todos`, `Activos`, `Entregados`, `Cancelados`), empty states, order card labels |
| `resources/views/customer/tabs/payments.blade.php` | ~20 | Summary labels, table headers, payment methods info section (includes OXXO/SPEI — replace with generic) |
| `resources/views/customer/tabs/addresses.blade.php` | ~25 | Title, buttons, form labels (full address form), empty state, confirm dialog |
| `resources/views/customer/tabs/profile.blade.php` | ~15 | Title, form labels, password change section |
| `resources/views/customer/tabs/settings.blade.php` | ~20 | Title, notification labels, security section, danger zone (delete account modal) |

### Special attention

- `payments.blade.php` lines 96–98: Remove hardcoded OXXO/SPEI references. Replace with generic payment method descriptions from `PaymentMethod` enum or config.
- `orders.blade.php` line 23: Remove WhatsApp-specific CTA (`Hacer un pedido por WhatsApp`). Replace with generic contact link using `config('business.contact.url')`.

### Acceptance Criteria
- [x] `grep -ri '[áéíóúñ¿¡]' resources/views/customer/` returns zero hits
- [x] All customer portal tabs render correctly in browser
- [x] No Spanish visible when navigating the customer portal

---

## Phase 17 — Translate Public Views & Markdown Content

**Status:** DONE

**Objective:** Translate remaining public-facing views and legal/marketing markdown content.

### 17.1 Public views (3 files)

| File | Strings | Key content |
|------|---------|-------------|
| `resources/views/suspended.blade.php` | ~15 | Title (`Cuenta Suspendida`), body text, plan feature labels, subscribe buttons, help link, sign-out |
| `resources/views/pages/payment-status.blade.php` | ~12 | Title (`Estado de Pago`), success/error headings, order detail labels, WhatsApp CTA |
| `resources/views/saas/pricing.blade.php` | ~20 | Hero title/subtitle, toggle labels (`Mensual`/`Anual`), CTA (`Empezar prueba gratis`), FAQ section |

### 17.2 PDF template (1 file)

| File | Key changes |
|------|-------------|
| `resources/views/pdf/quote.blade.php` | Replace hardcoded `MXN` with `{{ config('business.currency') }}` (~4 occurrences). Translate `📱 WhatsApp:` label to generic `📱 Phone:`. Translate `¡Gracias por cotizar con nosotros!` → `Thank you for your quote!` |

### 17.3 Footer (1 file)

| File | Key changes |
|------|-------------|
| `resources/views/livewire/layout/footer.blade.php` | `Todos los derechos reservados.` → `All rights reserved.` Replace WhatsApp URL (`wa.me/...`) with generic contact config |

### 17.4 Markdown content files (6 files — full rewrite)

| File | Action |
|------|--------|
| `resources/markdown/faq.md` | Rewrite as generic SaaS FAQ template (English) |
| `resources/markdown/policies.md` | Rewrite as generic privacy policy template (English) |
| `resources/markdown/terminos.md` | **Rename** to `terms.md`. Rewrite as generic terms template (English) |
| `resources/markdown/politicas-uso.md` | **Rename** to `usage-policies.md`. Rewrite as generic usage policies (English) |
| `resources/markdown/saas-terminos.md` | **Rename** to `saas-terms.md`. Rewrite as generic SaaS terms (English) |
| `resources/markdown/saas-politicas.md` | **Rename** to `saas-policies.md`. Rewrite as generic SaaS policies (English) |

> **Note:** After renaming, update all references in routes, controllers, and views that load these files.

### Acceptance Criteria
- [x] No Spanish text visible on any public page (suspended, pricing, payment-status)
- [x] PDF quote uses dynamic currency from config
- [x] All 6 markdown files are in English with generic placeholder content
- [x] Renamed markdown files have updated references everywhere

---

## Phase 18 — Genericize Routes

**Status:** DONE

**Objective:** Replace all Spanish URL paths with English equivalents.

### Route changes in `routes/web.php`

| Current path | New path | Route name (keep as-is or update) |
|-------------|----------|-----|
| `/contacto` | `/contact` | `contact` |
| `/politicas` | `/policies` | `policies` |
| `/terminos` | `/terms` | `terms` |
| `/politicas-uso` | `/usage-policies` | `usage-policies` |
| `/pago/exitoso` | `/payment/success` | `payment.success` |
| `/pago/fallido` | `/payment/failure` | `payment.failure` |
| `/mi-cuenta/login` | `/my-account/login` | `customer.login` |
| `/mi-cuenta` (prefix) | `/my-account` (prefix) | — |
| `/pedidos` | `/orders` | `customer.orders` |
| `/pedidos/{order}` | `/orders/{order}` | `customer.orders.show` |
| `/pedidos/{order}/pdf` | `/orders/{order}/pdf` | `customer.orders.pdf` |
| `/perfil` | `/profile` | `customer.profile` |
| `/direcciones` | `/addresses` | `customer.addresses` |
| `/pagos` | `/payments` | `customer.payments` |
| `/configuracion` | `/settings` | `customer.settings` |
| `/saas/precios` | `/saas/pricing` | `saas.pricing` |
| `/saas/terminos` | `/saas/terms` | `saas.terms` |
| `/saas/politicas` | `/saas/policies` | `saas.policies` |

### Post-change sweep

After updating routes, grep for any hardcoded old paths:
```bash
grep -rn 'mi-cuenta\|pedidos\|pago/\|precios\|terminos\|politicas\|direcciones\|configuracion\|perfil\|contacto' resources/ app/ --include="*.php" --include="*.blade.php"
```

### Acceptance Criteria
- [x] All URL paths are English
- [x] `php artisan route:list` shows no Spanish paths
- [x] All internal links/redirects updated to new paths
- [x] Customer portal navigation works end-to-end

---

## Phase 19 — Remove Domain-Specific Hardcoding

**Status:** DONE

**Objective:** Eliminate all business-specific references (WhatsApp, Conekta, MXN, domain-specific branding).

### 19.1 WhatsApp references (5 files)

| File | Change |
|------|--------|
| `app/Filament/Pages/TenantSettings.php` | Rename `whatsappPhone` property → `contactPhone`. Update `whatsapp_phone` setting key → `contact_phone` |
| `app/Filament/Pages/Auth/Register.php` | Update `'whatsapp_phone' => $data['phone']` → `'contact_phone' => $data['phone']` |
| `resources/views/filament/pages/tenant-settings.blade.php` | Update `wire:model="whatsappPhone"` → `wire:model="contactPhone"` |
| `resources/views/pdf/quote.blade.php` | Change `📱 WhatsApp:` → `📱 Phone:`. Use `{{ $business['phone'] }}` |
| `resources/views/livewire/layout/footer.blade.php` | Replace `wa.me/{{ config('business.contact.whatsapp_number') }}` with generic `{{ config('business.contact.url') }}` or `tel:{{ config('business.contact.phone') }}` |

### 19.2 Conekta references (2 files)

| File | Change |
|------|--------|
| `app/Models/Concerns/HasEncryptedSettings.php` | Rename `'conekta_private_key'` → `'payment_gateway_key'` in encrypted settings array |
| `README.md` | Remove Conekta as default payment provider, mention Stripe (already used for billing) |

### 19.3 Currency hardcoding (3 files)

| File | Change |
|------|--------|
| `app/Constants/BusinessRules.php` | Change `public const CURRENCY = 'MXN'` → `'USD'` (or better: remove constant, use `config('business.currency')` throughout) |
| `config/business.php` | Change default from `'MXN'` → `'USD'` |
| `resources/views/pdf/quote.blade.php` | Replace 4× hardcoded `MXN` → `{{ config('business.currency') }}` |

### 19.4 Missing config keys

Add to `config/business.php`:

```php
'contact' => [
    'phone' => env('BUSINESS_CONTACT_PHONE', ''),
    'email' => env('BUSINESS_CONTACT_EMAIL', ''),
    'url' => env('BUSINESS_CONTACT_URL', ''),
],
'hours' => [
    'weekdays' => env('BUSINESS_HOURS_WEEKDAYS', '9:00 AM - 6:00 PM'),
],
'location' => [
    'city' => env('BUSINESS_LOCATION_CITY', ''),
    'state' => env('BUSINESS_LOCATION_STATE', ''),
    'country' => env('BUSINESS_LOCATION_COUNTRY', ''),
],
```

Update `.env.example` with corresponding variables.

### Acceptance Criteria
- [x] `grep -ri 'whatsapp\|conekta\|oxxo\|spei' app/ config/ resources/views/` returns zero hits
- [x] `grep -r 'MXN' app/ resources/views/` returns zero hits (currency is dynamic)
- [x] All config keys referenced in views exist in `config/business.php`

---

## Phase 20 — Delete Domain-Specific Documentation

**Status:** DONE

**Objective:** Remove all documentation that is specific to the original vertical (Claramente CRM, etc.).

### Directories to delete entirely

| Directory | Files | Reason |
|-----------|-------|--------|
| `docs/negocio/` | 8 files | Spanish business rules for the original vertical |
| `docs/growth/` | 9 + 21 prompt files = 30 files | Marketing strategy for the original brand |
| `docs/claramente/` | 10 files | Alternate CRM business context |

### Individual files to delete

| File | Reason |
|------|--------|
| `docs/TEMPLATE_CONVERSION_PROMPT.md` | One-time conversion prompt (already executed) |
| `docs/prompt-migracion-angular-a-laravel.md` | Migration prompt (historical) |
| `docs/prompt-security-hardening-saas.md` | One-time prompt |
| `docs/herramientas-desarrollo.md` | Spanish dev tools list |
| `docs/backoffice-analysis.md` | Domain-specific backoffice analysis |
| `docs/backoffice-roadmap.md` | Domain-specific backoffice roadmap |
| `docs/fase4-livewire-plan.md` | Historical phase plan |
| `docs/laravel-migration-guide.md` | Angular → Laravel migration (historical) |
| `docs/laravel-migration-roadmap.md` | Angular → Laravel migration (historical) |
| `docs/comands.md` | Likely superseded by `docs/guides/09-commands-reference.md` |
| `docs/models-guide.md` | Likely outdated (old model names) |
| `docs/sql/integridad-tenant.md` | Spanish SQL docs |
| `docs/sql/usuarios-y-roles.md` | Spanish SQL docs |

### Files to review (keep if still relevant, update if outdated)

| File | Action |
|------|--------|
| `docs/features/*.md` | Review each — delete domain-specific ones (DTF, garment-types, bundles, loyalty, CFDI, Shopify, Conekta). Keep generic SaaS features (RBAC, billing, onboarding, team management) |
| `docs/guides/16-conekta-setup.md` | Delete (Conekta-specific) |
| `docs/guides/15-zorin-os-setup.md` | Delete (developer-machine-specific) |
| `docs/saas/*.md` | Review — may contain old brand references but SaaS architecture is relevant. Update references |
| `docs/qa/` | Keep test plans (useful reference) but verify they don't reference deleted features |
| `docs/mobile/` | Keep if mobile roadmap is still relevant |

### Acceptance Criteria
- [x] `docs/negocio/`, `docs/growth/`, `docs/claramente/` directories deleted
- [x] All individual files listed above deleted
- [x] `grep -ri 'playera\|dtf\|estampado' docs/` returns zero hits
- [x] Remaining docs are generic or SaaS-infrastructure-relevant

---

## Phase 21 — Update Context & Meta Files

**Status:** DONE

**Objective:** Update all project context files to reflect the generic template state.

### 21.1 `CLAUDE.md` (root)

| Section | Change |
|---------|--------|
| Title (line 1) | `# [Old Brand] Laravel - Development Context` → `# Generic Multi-Tenant SaaS Template` |
| Description (line 3) | Old vertical description → `Multi-tenant SaaS template. Monolith MVC: Laravel 12 + Livewire 4.1 + Filament 3.3 + PostgreSQL 15.` |
| Deploy URL (line 4) | Remove old production URL |

### 21.2 `README.md`

Full rewrite needed:
- Remove Conekta/Envia.com references
- Remove DTF-specific feature list
- Add generic template description, setup instructions, architecture overview
- List included SaaS features (multi-tenancy, billing, RBAC, onboarding, referrals, customer portal)

### 21.3 `.claude/context/architecture.md`

| Section | Change |
|---------|--------|
| Database name | Remove old database name reference |
| Model documentation | Update to reflect current models: `Item`, `Location`, `OrderLine`, `Order`, `Customer`, `Invoice`, `Quote` with current field lists |
| Deleted models | Remove references to `Product`, `Branch`, `ProductModel`, `DimensionLimit` |

### 21.4 `.claude/context/conventions.md`

| Section | Change |
|---------|--------|
| Naming examples (lines 18–22) | Replace `Product` examples with `Item`, `Order`, `Location` |

### 21.5 `.claude/context/saas-completed.md`

| Section | Change |
|---------|--------|
| Line 61 | `Product, Branch` → `Item, Location` in CacheInvalidationObserver list |
| General | Sweep for other old model name references |

### 21.6 `.claude/context/current.md`

Will be updated at end of session per session protocol.

### 21.7 `.claude/context/docs-index.md`

Update to reflect deleted/renamed docs from Phase 20.

### Acceptance Criteria
- [x] `grep -ri 'playera\|dtf' CLAUDE.md README.md .claude/` returns zero hits
- [x] `grep -ri '\bProduct\b\|\bBranch\b' .claude/context/` returns zero hits (except historical notes)
- [x] README describes a generic SaaS template

---

## Phase 22 — Module/Extension Infrastructure

**Status:** DONE

**Objective:** Add lightweight extensibility hooks so the template can be customized per-vertical without modifying core code.

### What was done:

1. **PaymentGatewayInterface** (`app/Contracts/PaymentGatewayInterface.php`) — `charge()` and `refund()` methods with typed array returns
2. **Module config** (`config/modules.php`) — 5 feature flags: payments, customer_portal, locations, api, exports
3. **hasModule() helper** — added to `app/helpers.php` (already autoloaded via composer.json)
4. **3 new events** dispatched from real code paths:
   - `TenantCreated` — dispatched in `Register.php` after tenant + seed
   - `TenantSuspended` — dispatched in `Tenant::suspend()`
   - `PlanChanged` — dispatched in `BillingController::swapPlan()` and `StripeWebhookController::syncTenantPlan()`

### Acceptance Criteria
- [x] `PaymentGatewayInterface` exists with at least `charge()` and `refund()`
- [x] `config('modules.payments')` returns boolean
- [x] `hasModule('payments')` helper works
- [x] New events are dispatchable (wired into real code)
- [x] `composer analyse` — 0 new errors (baseline reduced 83 → 80)

---

## Part II — Post-Conversion Features (Phases 23–30)

### Phase 23 — Plugin Marketplace Core (PR #3)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| `Plugin` model | `name`, `slug`, `category`, `is_free`, `price_monthly`, `stripe_price_id`, `included_in_plans`, `required_modules` |
| `TenantPlugin` pivot | `is_active`, `activated_at`, `billing_type` (free/included/paid), `stripe_subscription_item_id` |
| `PluginSeeder` | 8 plugins across 6 categories (idempotent via `updateOrCreate`) |
| Marketplace Livewire | Category-grouped cards, toggle activation, badges (Included/Free/$X.XX/mo) |
| `hasModule()` upgrade | Now checks both `config/modules.php` AND tenant plugin state |

### Phase 24 — Plugin Stripe Billing (PR #3)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| `PluginBillingService` | `activatePlugin()`, `deactivatePlugin()`, `syncPluginsFromSubscription()`, `deactivateAllPaidPlugins()` |
| Stripe integration | `addPriceAndInvoice()` / `removePrice()` for subscription items |
| Webhook handler | `syncPluginsFromSubscription` on `subscription.updated`, `deactivateAllPaidPlugins` on `subscription.deleted` |
| Billing page | "Active Add-ons" section showing paid plugins with prices |
| Confirmation modal | Paid plugins show price confirmation before activation |

### Phase 25 — Super Admin Plugin Management (PR #3)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| `TenantResource` infolist | Active Plugins section with billing type, activation date, Stripe item ID |
| Plugin columns | `active_plugins_count`, `active_plugins_list` in tenants table |
| `TenantSeedService` | Auto-activates plan-included plugins on tenant registration |

### Phase 26 — Module Flag Wiring (PR #4)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| Route gating | `hasModule()` checks in `routes/web.php` for customer_portal, exports, API |
| Filament gating | `shouldRegisterNavigation()` on LocationResource, CashRegister, ApiSettings, WebhookSettings |
| Sidebar | Navigation items hidden when plugin disabled |
| Tests | 15 PluginMarketplaceTest + 8 PluginBillingTest + 6 ModuleFlagsTest |

### Phase 27 — Loyalty Data Layer & Service (PR #6 — L1+L2)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| `LoyaltyTier` enum | Bronze (0-499, x1), Silver (500-1499, x1.5), Gold (1500-4999, x2), VIP (5000+, x3) |
| `LoyaltyReward` model | `name`, `type`, `points_cost`, `value`, `min_tier`, `is_active` (BelongsToTenant) |
| `LoyaltyTransaction` model | `points`, `type`, `description`, `balance_after`, `order_id` (BelongsToTenant) |
| `LoyaltyCoupon` model | `code`, `type`, `value`, `expires_at`, `used_at`, `order_id` (BelongsToTenant) |
| 4 migrations | Loyalty fields on customers, rewards table, transactions table, coupons table |
| `LoyaltyService` | `creditPoints()`, `reversePoints()`, `redeemReward()`, first-purchase bonus |
| Order observer | Automatic point credit on order completion, reverse on cancellation |
| `LoyaltyRewardSeeder` | 9 default rewards (idempotent via `firstOrCreate`) |
| Loyalty plugin | Added to PluginSeeder, included in Growth/Pro plans |

### Phase 28 — Loyalty UI (PR #6 — L3+L4)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| Customer portal tab | 6 sections: tier card, progress bar, benefits, available rewards, locked rewards, history, coupons |
| Tier card gradients | Bronze (amber), Silver (slate), Gold (yellow), VIP (violet/purple/indigo) with matching shadows |
| `LoyaltyRewardResource` | Filament CRUD for reward catalog (list, create, edit) |
| `CustomerResource` | Loyalty section in customer form (collapsible) |
| Portal routes | `/my-account/loyalty`, `/my-account/loyalty/redeem/{reward}` |

### Phase 29 — Billing Event Tracking (PR #5)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| `BillingEventType` enum | `subscription_started`, `plan_changed`, `plugin_activated`, `plugin_deactivated`, `subscription_cancelled` |
| `BillingEvent` model | `type`, `description`, `amount`, `metadata` (BelongsToTenant) |
| `BillingHistoryService` | `recordSubscriptionStarted()`, `recordPlanChanged()`, `recordPluginActivated()`, `recordPluginDeactivated()`, `recordSubscriptionCancelled()` |
| Integration points | `PluginBillingService`, `TenantResource` subscribe action, `StripeWebhookController` |
| `BillingEventSeeder` | Backfill seeder for existing tenants (idempotent) |
| Tests | 10 BillingHistoryTest (event recording, visibility, isolation, formatting) |

### Phase 30 — Billing UI Enhancements (PR #5)

**Status:** DONE

| Component | Description |
|-----------|-------------|
| Billing page | "Billing History" table with event type, date, formatted amount |
| Next billing date | Displayed on plan card for subscribed tenants |
| Cashier columns migration | `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` on tenants table |

---

## SaaS Foundation (Pre-Conversion — Phases F1–F9)

These 9 phases were implemented before the generic conversion and form the SaaS infrastructure:

| Phase | Feature | Version |
|-------|---------|---------|
| F1 | Multi-tenancy Core | v0.4.0 |
| F2 | Roles & Permissions (RBAC) | v0.5.0 |
| F3 | Registration + Onboarding | v0.6.0 |
| F4 | Stripe + Plans | v0.7.0 |
| F5 | Trial Flow + Landing | v0.8.0 |
| F6 | Retention (alerts, winback) | v0.9.0 |
| F7 | Premium Features (exports, API) | v0.11.0 |
| F8 | Integrations (webhooks, API v1) | v0.13.0 |
| F9 | Optimization (cache, CI/CD, security) | v1.0.0 |

---

## Execution Summary

### Phase completion by PR

| Phase | Priority | Status | PR |
|-------|----------|--------|-----|
| **1–13** | CRITICAL | DONE | #2 |
| **14** | CRITICAL | DONE | #2 |
| **15** | HIGH | DONE | #2 |
| **16** | HIGH | DONE | #2 |
| **17** | HIGH | DONE | #2 |
| **18** | HIGH | DONE | #2 |
| **19** | MEDIUM | DONE | #2 |
| **20** | MEDIUM | DONE | #2 |
| **21** | MEDIUM | DONE | #2 |
| **22** | LOW | DONE | #2 |
| **23–25** | — | DONE | #3 |
| **26** | — | DONE | #4 |
| **27–28** | — | DONE | #6 |
| **29–30** | — | DONE | #5 |

### Current test coverage

| Suite | Tests | Status |
|-------|-------|--------|
| BillingHistoryTest | 10 | PASS |
| PluginMarketplaceTest | 15 | PASS |
| PluginBillingTest | 8 | PASS |
| ModuleFlagsTest | 6 | PASS |
| SuperAdminTest | 9 | PASS |
| TenantIsolationTest | 8 | PASS |
| All other suites | 245 | PASS |
| **Total** | **301** | **ALL PASS (773 assertions)** |

---

### Verification checklist (run after all phases)

```bash
# No Spanish in code
grep -ri 'pedido\|usuario\|sucursal\|producto\|cliente\|direccion\|configuracion' app/ config/ resources/views/ routes/

# No domain references
grep -ri 'conekta\|whatsapp\|oxxo\|spei\|playera\|dtf' app/ config/ resources/ routes/ CLAUDE.md README.md .claude/

# No hardcoded MXN
grep -r 'MXN' app/ resources/views/

# Static analysis
composer analyse

# All tests pass
composer test

# Routes are English
php artisan route:list | grep -E 'pedido|pago|cuenta|precio|termino|politica|direccion|perfil'

# Seeder works
php artisan migrate:fresh --seed
```

---

## Pending / Deferred

| Item | Priority | Notes |
|------|----------|-------|
| PR #6 merge | HIGH | Loyalty program ready for review |
| L5: Loyalty + Referrals integration | MEDIUM | Connect loyalty points to referral program |
| PHPStan baseline cleanup | LOW | Gradually fix larastan false positives |

---

*Last updated: 2026-03-16*
