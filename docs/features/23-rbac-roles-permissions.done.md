# RBAC — Roles y Permisos (SaaS Fase 2)

**Status:** Completado
**Date:** 2026-02-20
**Branch:** feature/saas-phase2-onboarding

## Resumen

Sistema de control de acceso basado en roles (RBAC) usando Spatie Permission v7. Cada user tiene un rol por tenant que define sus permisos granulares dentro del panel Filament.

## Arquitectura

- **Spatie Permission v7** — sin teams. El rol se sincroniza desde `tenant_user.role` en cada request via middleware.
- **9 Policies manuales** — sin filament-shield. Laravel auto-descubre por convencion `App\Policies\{Model}Policy`.
- **Sync en middleware** — `EnsureTenant::bindTenant()` lee el pivot role y ejecuta `$user->syncRoles()`.

## Roles y Permisos

### 5 Roles

| Rol | Descripcion | Permisos |
|-----|-------------|----------|
| **owner** | Dueno del negocio | Todos (15/15) |
| **admin** | Admin | Todos menos billing (14/15) |
| **sales** | Equipo de sales | orders.view/create/edit, customers.manage, payments.view/create |
| **operations** | Equipo de operations | orders.view, production.view/mark |
| **finance** | Finance/finanzas | orders.view, payments.view, reports.export |

### 15 Permisos

| Grupo | Permiso | owner | admin | sales | operations | finance |
|-------|---------|:-----:|:-----:|:------:|:----------:|:------------:|
| Orders | orders.view | x | x | x | x | x |
| Orders | orders.create | x | x | x | | |
| Orders | orders.edit | x | x | x | | |
| Orders | orders.delete | x | x | | | |
| Operations | production.view | x | x | | x | |
| Operations | production.mark | x | x | | x | |
| Items | products.manage | x | x | | | |
| Customers | customers.manage | x | x | x | | |
| Payments | payments.view | x | x | x | | x |
| Payments | payments.create | x | x | x | | |
| Precios | pricing.manage | x | x | | | |
| Reportes | reports.export | x | x | | | x |
| Config | settings.manage | x | x | | | |
| Users | users.manage | x | x | | | |
| Billing | billing.manage | x | | | | |

## Policies

| Policy | Model | Permiso usado |
|--------|--------|---------------|
| OrderPolicy | Order | orders.view/create/edit/delete |
| ProductPolicy | Product | products.manage |
| CustomerPolicy | Customer | customers.manage |
| BranchPolicy | Branch | settings.manage |
| PricingRulePolicy | PricingRule | pricing.manage |
| removedPolicy | removed | settings.manage |
| ItemCategoryPolicy | ItemCategory | products.manage |
| ColorPolicy | Color | products.manage |
| SizePolicy | Size | products.manage |

## Componentes

### Tenant Switcher
- Componente Livewire en header de Filament (`panels::user-menu.before`)
- Solo visible si el user tiene >1 tenant activo
- Al cambiar: actualiza session, sincroniza rol Spatie, redirect a dashboard

### Activity Log
- `spatie/laravel-activitylog` con column `tenant_id` en table `activity_log`
- Trait `LogsActivityWithTenant` en Order, Customer, Product, PricingRule
- Registra created/updated/deleted con old/new values
- Pagina Filament filtrada por tenant, solo visible con `settings.manage`

### Acciones protegidas
- `ProductionBoard::canAccess()` — requiere `production.view`
- `toggle_produced` — requiere `production.mark`
- OrderResource actions de table: confirm, start_production, mark_ready, deliver, generate_label — requieren `orders.edit`
- payment_link — requiere `payments.create`
- duplicate — requiere `orders.create`

## Tests

### Automatizados (RbacTest.php — 10 tests)

| Test | Resultado |
|------|-----------|
| Owner tiene todos los permisos (15/15) | PASS |
| Owner puede acceder al panel | PASS |
| Admin tiene todos menos billing | PASS |
| Sales puede manejar orders y payments | PASS |
| Sales no puede delete orders ni manage products | PASS |
| Operations puede ver orders y production | PASS |
| Operations no puede crear/editar orders | PASS |
| Finance puede ver orders y payments | PASS |
| Finance no puede crear/editar | PASS |
| User sin tenant no accede al panel | PASS |

### Manual — Rol Sales (browser)

Test realizada con user `sales@test.com` (rol: sales):

| Verificacion | Esperado | Resultado |
|---|---|---|
| Sidebar: Escritorio, Orders, Customers | Solo estos 3 items | OK |
| No ve: Items | Hidden | OK |
| No ve: Operations | Hidden | OK |
| No ve: Precios | Hidden | OK |
| No ve: Locationes | Hidden | OK |
| No ve: Actividad | Hidden | OK |
| Acciones: Editar order | Visible (tiene orders.edit) | OK |
| Acciones: Duplicar order | Visible (tiene orders.create) | OK |
| Acciones: Link de Payment | Visible (tiene payments.create) | OK |
| Acciones: Messaging | Visible | OK |
| Acciones: Eliminar | Hidden (no tiene orders.delete) | OK |
| Acciones: Restaurar | Hidden (no tiene orders.delete) | OK |

## Files clave

| File | Cambio |
|---------|--------|
| `app/Models/User.php` | HasRoles trait, canAccessPanel via tenant membership |
| `app/Http/Middleware/EnsureTenant.php` | bindTenant() con syncRoles desde pivot |
| `database/seeders/RolesAndPermissionsSeeder.php` | 5 roles, 15 permisos (idempotente) |
| `database/seeders/DefaultTenantSeeder.php` | Asigna Spatie owner al admin |
| `app/Policies/*.php` | 9 policies |
| `app/Livewire/TenantSwitcher.php` | Componente header |
| `app/Models/Concerns/LogsActivityWithTenant.php` | Trait para activity log con tenant_id |
| `app/Filament/Pages/ActivityLog.php` | Pagina de historial filtrada por tenant |
| `app/Filament/Pages/ProductionBoard.php` | canAccess() + toggle visibility |
| `app/Filament/Resources/OrderResource.php` | Acciones protegidas por permiso |
| `tests/Feature/RbacTest.php` | 10 tests RBAC |
