# SaaS Evolution Roadmap — SaaS Template

> Roadmap para evolucionar SaaS Template (MVP single-tenant) a SaaS Template (SaaS multi-tenant vendible).
> Sincronizado con los documentos `docs/saas/1-9` y la base del `laravel-migration-roadmap.md`.

**Autor:** SaaS Template Team
**Date:** 2026-02-19
**Ultima Actualizacion:** 2026-02-22
**Status:** En progreso — Fase 6 completada, Fase 7 pendiente

---

## Progreso Actual

| Fase | Estado | Fecha |
|------|--------|-------|
| PRE-0: MVP Completado | ✅ Completada | 2026-02-14 |
| FASE 1: SaaS Foundation — Multi-tenancy | ✅ Completada | 2026-02-19 |
| FASE 2: SaaS Foundation — Roles y Permisos | ✅ Completada | 2026-02-20 |
| FASE 3: SaaS Foundation — Registration y Onboarding | ✅ Completada | 2026-02-20 |
| FASE 4: Monetizacion — Stripe + Plans | ✅ Completada | 2026-02-20 |
| FASE 5: Monetizacion — Trial Flow + Landing | ✅ Completada | 2026-02-20 |
| FASE 6: Growth — Retencion | ✅ Completada | 2026-02-22 |
| FASE 7: Growth — Features Premium | ✅ Completada | 2026-02-22 |
| FASE 8: Growth — Integraciones | ✅ Completada | 2026-02-22 |
| FASE 9: Growth — Optimizacion | ✅ Completada | 2026-02-23 |

### Estado del MVP (Base para SaaS)

```
📁 /Users/joseignacio/projects/saas-template-laravel/
```

| Componente | Version | Estado |
|------------|---------|--------|
| Laravel | v12.49.0 | ✅ Instalado |
| Livewire | v3.5.x | ✅ Instalado |
| FilamentPHP | v3.3.47 | ✅ Instalado |
| DomPDF | v3.1.1 | ✅ Instalado |
| PostgreSQL | Railway Postgres 15 | ✅ Conectado |
| Operations | your-app.up.railway.app | ✅ Online |
| Admin Panel | /admin (Filament) | ✅ 9 Resources, 7 Widgets |
| Portal Customer | /my-account | ✅ Auth, orders, payments, direcciones |
| Models | 15 | ✅ Product, Order, Customer, Branch, etc. |
| Migrations | 30+ tables | ✅ Ejecutadas |
| Seeders | 9 (idempotentes) | ✅ Ejecutados |

### Paquetes por Instalar (SaaS)

| Paquete | Version | Para que | Fase |
|---------|---------|----------|------|
| spatie/laravel-permission | ^6 | RBAC: roles y permisos | ✅ Instalado (Fase 2) |
| spatie/laravel-activitylog | ^4 | Audit trail por tenant | ✅ Instalado (Fase 2) |
| laravel/cashier | ^16 | Suscripciones Stripe | ✅ Instalado (Fase 4) |
| filament-shield (bezhansalleh) | ^3 | Auto-generate Filament policies | ❌ Descartado (policies manuales) |
| sentry/sentry-laravel | ^4 | Error tracking operations | Fase 9 |
| league/flysystem-aws-s3-v3 | ^3 | Storage R2/S3 | Fase 9 |

### Timeline General

```
PRE-0: MVP Completado           [Completado: 2026-01-28 → 2026-02-14]
                                 ─────────────────────────────────────
Fase 1: Multi-tenancy Core      [Semanas 1-2]
Fase 2: Roles y Permisos        [Semanas 3-4]
Fase 3: Registration + Onboarding   [Semanas 5-6]
                                 ───────────── → PRIMER BETA TESTER
Fase 4: Stripe + Plans          [Semanas 7-8]
Fase 5: Trial Flow + Landing    [Semanas 9-10]
                                 ───────────── → PRIMER CLIENTE PAGADO
Fase 6: Retencion               [Semanas 11-12]
Fase 7: Features Premium        [Semanas 13-14]
                                 ───────────── → PRODUCT-MARKET FIT
Fase 8: Integraciones           [Semanas 15-16]
Fase 9: Optimizacion            [Semanas 17-18]
                                 ───────────── → GROWTH READY
                                 Total: ~18 semanas
```

---

## PRE-0: MVP Completado (Base Reutilizable)

> Todo lo que el MVP ya tiene y que se reutiliza directo para SaaS (solo agregar `tenant_id`).

### Features 100% Reutilizables

| Feature | Componentes | Estado |
|---------|-------------|--------|
| Order Wizard (5 pasos) | OrderResource + CreateOrder | ✅ Listo |
| Status lifecycle | Recibido → Confirmado → Operations → Listo → Entregado | ✅ Listo |
| Auto-transiciones | Payment → confirma. Ultima pieza → listo. | ✅ Listo |
| Tablero de operations | ProductionPanel Filament page | ✅ Listo |
| Motor de precios | PricingCalculator service | ✅ Listo |
| Calculadora publica | QuoteCalculator Livewire | ✅ Listo |
| PDFs (order + cotizacion) | PdfGenerator service + DomPDF | ✅ Listo |
| Portal del customer | CustomerPortalController + auth:customer | ✅ Listo |
| Payments Payment Gateway | Payment model + webhooks | ✅ Listo |
| Envios Envia.com | EnviaShippingService (6 carriers) | ✅ Listo |
| Dashboard widgets | 7 widgets Filament | ✅ Listo |
| Messaging compartir | Links + notificaciones | ✅ Listo |

### Features que Necesitan Modificacion

| Feature | Modificacion Requerida | Fase |
|---------|----------------------|------|
| User auth (`canAccessPanel`) | Reemplazar admin_emails por RBAC | Fase 2 |
| Filament panel | Integrar tenant scope + Shield policies | Fase 2 |
| Customer auth | Agregar tenant_id a customers | Fase 1 |
| Business configs | Scope por tenant_id | Fase 1 |
| Seeders | Hacer parametricos por tenant | Fase 3 |
| API endpoints | Agregar Sanctum tokens + tenant scope | Fase 8 |
| Paginas publicas (home, catalogo) | Son de SaaS Template, SaaS usa landing propia | Fase 5 |

### Problemas de BD a Resolver (Pre-Fase 1)

| Problema | Solucion | Referencia |
|----------|----------|-----------|
| Campos legacy JSON (orders.items, products.sizes/colors, pricing_rules.model) | DROP columns — ya tienen reemplazo normalizado | 8-DATABASE_SAAS_SCHEMA.md §2.1 |
| quotes sin FK a customers/item_categories | Agregar customer_id y model_id FK | 8-DATABASE_SAAS_SCHEMA.md §2.3 |
| dimension_limits.size es string, no FK | Agregar size_id FK | 8-DATABASE_SAAS_SCHEMA.md §2.3 |
| orders tiene 40+ columns | Mantener (pragmatico para Fase 1-2) | 8-DATABASE_SAAS_SCHEMA.md §2.4 |

---

## FASE 1: Multi-tenancy Core (Semanas 1-2)

**Objetivo:** Hacer el sistema multi-tenant. Al final de esta fase: N businesss pueden existir en la misma BD con datos completamente aislados.

### 1.1 Limpieza Legacy (Pre-requisito)

Antes de agregar `tenant_id`, eliminar campos legacy que ya no se usan.

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.1.1 | Grep campos legacy | Buscar referencias a `orders.items` (JSON), `products.sizes` (JSON), `products.colors` (JSON), `pricing_rules.model` (string) en todo el codebase | Lista de files que los referencian |
| 1.1.2 | Limpiar models | Quitar de `$fillable`, `$casts`, accessors, y cualquier referencia en Resources/Services | 0 referencias en codebase |
| 1.1.3 | Migracion: drop legacy | `DROP COLUMN items` en orders, `DROP COLUMN sizes, colors` en products, `DROP COLUMN model` en pricing_rules | Migracion ejecuta sin error |
| 1.1.4 | Normalizar quotes | `ADD customer_id bigint FK→customers NULLABLE`, `ADD model_id bigint FK→item_categories NULLABLE` | Relationships funcionales |
| 1.1.5 | Normalizar dimension_limits | `ADD size_id bigint FK→sizes NULLABLE`, backfill via lookup de size string | FK funcional |

### 1.2 Model Tenant

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.2.1 | Crear migracion `tenants` | Table: id, name, slug (unique), plan, owner_id (FK→users), settings (JSON), is_active, trial_ends_at, subscribed_at, timestamps | Migracion ejecuta |
| 1.2.2 | Crear model `Tenant` | Fillable, casts (settings→array, is_active→boolean), relationships: owner (belongsTo User), users (belongsToMany via tenant_user), orders/customers/products (hasMany) | Model funcional |
| 1.2.3 | Crear factory `TenantFactory` | Genera tenant con datos faker, plan random entre starter/growth/pro | Factory funcional |

```
Estructura de table tenants:

tenants
├── id              bigint PK auto
├── name            string(100)         "SaaS Template"
├── slug            string(50) unique   "saas-template"
├── plan            string(20)          "starter" | "growth" | "pro"
├── owner_id        bigint FK→users     quien creo la cuenta
├── settings        json                { logo, colors, currency, timezone, messaging, payment_gateway_keys }
├── is_active       boolean default true
├── trial_ends_at   timestamp nullable
├── subscribed_at   timestamp nullable
├── created_at
└── updated_at
```

### 1.3 Table Pivot tenant_user

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.3.1 | Crear migracion `tenant_user` | Table: id, tenant_id (FK), user_id (FK), role (string), timestamps. Unique constraint en (tenant_id, user_id). | Migracion ejecuta |
| 1.3.2 | Actualizar model `User` | Agregar relacion `tenants()` belongsToMany con pivot role. Metodo `currentTenant()` lee de session. | Relationships funcionales |
| 1.3.3 | Actualizar model `Tenant` | Agregar relacion `users()` belongsToMany con pivot role. Scope `forUser($user)`. | Relationships funcionales |

### 1.4 Agregar tenant_id a Tables de Negocio

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.4.1 | Migracion: add tenant_id | Agregar `tenant_id bigint FK→tenants NULLABLE` + INDEX a: orders, customers, products, pricing_rules, quotes, branches, business_configs | Migracion ejecuta |
| 1.4.2 | Migracion: add tenant_id catalogo | Agregar `tenant_id bigint FK→tenants NULLABLE` a: item_categories, sizes, colors, dimension_limits. NULL = template global. | Migracion ejecuta |
| 1.4.3 | Seed tenant default | INSERT tenant "SaaS Template" (slug: saas-template, plan: pro, owner: admin actual). UPDATE todas las tables SET tenant_id = 1. | Todos los registrations tienen tenant_id |
| 1.4.4 | Migracion: NOT NULL constraints | ALTER tenant_id SET NOT NULL en: orders, customers, products, pricing_rules, quotes, branches, business_configs. MANTENER nullable en catalogo (global templates). | Constraints activos |

**Tables que NO reciben tenant_id:**

| Table | Razon |
|-------|-------|
| users | Global — un user puede estar en N tenants via tenant_user |
| order_lines | Hereda tenant via order.tenant_id |
| payments | Hereda tenant via order.tenant_id |
| customer_addresses | Hereda tenant via customer.tenant_id |
| product_sizes | Hereda tenant via product.tenant_id |
| sessions, cache, jobs | Tables del framework |

### 1.5 Trait BelongsToTenant + Global Scope

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.5.1 | Crear trait `BelongsToTenant` | Registra Global Scope que filtra `WHERE tenant_id = {current_tenant_id}`. Auto-asigna tenant_id al crear registrations (creating event). Define relacion `tenant()` belongsTo. | Trait funcional |
| 1.5.2 | Aplicar trait a models | Agregar `use BelongsToTenant` a: Order, Customer, Product, PricingRule, Quote, Branch, BusinessConfig | 7 models con trait |
| 1.5.3 | Scope para catalogo | Para ItemCategory, Size, Color, removed: scope que retorna `WHERE tenant_id = {current} OR tenant_id IS NULL` (herencia de templates globales) | Catalogo muestra propios + globales |

```
Proteccion en capas:

1. Global Scope      — Eloquent filtra automaticamente
2. Middleware         — Valida que tenant_id del request es valido
3. Policy            — Verifica pertenencia del recurso al tenant
4. DB Index          — tenant_id indexado en todas las tables
```

### 1.6 Middleware EnsureTenant

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.6.1 | Crear middleware `EnsureTenant` | Lee tenant_id de session. Si no existe, redirect a selector. Si existe, valida que user pertenece al tenant. Setea tenant en `app()->instance('tenant', $tenant)`. | Middleware registrado |
| 1.6.2 | Registrar en rutas | Aplicar middleware a todas las rutas de admin (Filament) y portal del customer. Excluir: login, registration, landing. | Rutas protegidas |
| 1.6.3 | Helper global | `currentTenant()` helper que retorna el tenant activo desde el container. Usar en Global Scope y en codigo. | Helper funcional |

### 1.7 Migrar Datos Existentes

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.7.1 | Seeder: DefaultTenantSeeder | Crear tenant "SaaS Template", asignar tenant_id = 1 a todos los registrations, crear tenant_user con role owner para el admin actual. Idempotente. | Datos existentes siguen funcionando |
| 1.7.2 | Check aislamiento | Test: crear tenant B, crear order en tenant B, check que tenant A no ve la order. | Test pasa |
| 1.7.3 | Check portal customer | Customer login filtra por tenant_id del customer. Customer de tenant A no ve orders de tenant B. | Portal funcional |

### Criterios de Aceptacion Fase 1

- [x] Table `tenants` creada con datos de SaaS Template como tenant #1
- [x] Table `tenant_user` creada con admin actual como owner de tenant #1
- [x] `tenant_id` agregado a 7 tables de negocio (NOT NULL) + 4 de catalogo (NULLABLE)
- [x] Trait `BelongsToTenant` aplicado a 7 models
- [x] Global Scope filtra automaticamente por tenant_id
- [x] Middleware `EnsureTenant` protege rutas admin y portal
- [x] Campos legacy eliminados (orders.items, products.sizes/colors, pricing_rules.model)
- [x] SaaS Template sigue funcionando como tenant #1 sin cambios de UX
- [x] Test de aislamiento: tenant A no ve datos de tenant B
- [x] `composer test` pasa al 100%
- [x] `composer analyse` sin errores nuevos

### Indices Criticos (Fase 1)

```sql
-- Cada table con tenant_id:
CREATE INDEX idx_{table}_tenant ON {table} (tenant_id);

-- Indices compuestos para queries frecuentes:
CREATE INDEX idx_orders_tenant_status ON orders (tenant_id, status);
CREATE INDEX idx_orders_tenant_created ON orders (tenant_id, created_at DESC);
CREATE INDEX idx_customers_tenant_phone ON customers (tenant_id, phone);
```

---

## FASE 2: Roles y Permisos (Semanas 3-4)

**Objetivo:** Implementar RBAC con 5 roles. Al final de esta fase: cada user tiene permisos granulares dentro de su tenant.

### 2.1 Instalar Spatie Permission

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.1.1 | Instalar paquete | `composer require spatie/laravel-permission` + publicar migrations + migrate | Package instalado |
| 2.1.2 | Configurar model User | Agregar trait `HasRoles` a User. Configurar guard en config/permission.php. | User puede tener roles |
| 2.1.3 | Instalar Activitylog | `composer require spatie/laravel-activitylog` + publicar migrations + agregar tenant_id a la migracion + migrate | Activity log funcional |

### 2.2 Definir Roles y Permisos

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.2.1 | Crear seeder RolesAndPermissionsSeeder | 5 roles: Owner, Admin, Sales, Operations, Finance. Permisos granulares por grupo (orders.*, production.*, products.*, customers.*, payments.*, pricing.*, reports.*, settings.*, users.*, billing.*). Idempotente. | Seeder ejecuta sin duplicar |
| 2.2.2 | Asignar roles por tenant | Al establecer tenant activo en session, asignar el rol Spatie correspondiente al user segun `tenant_user.role`. Si cambia de tenant, reasignar. | Rol correcto por tenant |
| 2.2.3 | Asignar Owner a admin actual | Migrar admin actual → role Owner del tenant SaaS Template | Admin actual tiene permisos completos |

**Table de permisos por rol:**

| Grupo | Permiso | Owner | Admin | Sales | Operations | Finance |
|-------|---------|:-----:|:-----:|:------:|:----------:|:------------:|
| Orders | orders.view | ✅ | ✅ | ✅ | Solo asignados | ✅ |
| Orders | orders.create | ✅ | ✅ | ✅ | ❌ | ❌ |
| Orders | orders.edit | ✅ | ✅ | ✅ | ❌ | ❌ |
| Orders | orders.delete | ✅ | ✅ | ❌ | ❌ | ❌ |
| Operations | production.view | ✅ | ✅ | ❌ | ✅ | ❌ |
| Operations | production.mark | ✅ | ✅ | ❌ | ✅ | ❌ |
| Items | products.manage | ✅ | ✅ | ❌ | ❌ | ❌ |
| Customers | customers.manage | ✅ | ✅ | ❌ | ❌ | ❌ |
| Payments | payments.view | ✅ | ✅ | ✅ | ❌ | ✅ |
| Payments | payments.create | ✅ | ✅ | ✅ | ❌ | ❌ |
| Precios | pricing.manage | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reportes | reports.export | ✅ | ✅ | ❌ | ❌ | ✅ |
| Config | settings.manage | ✅ | ✅ | ❌ | ❌ | ❌ |
| Users | users.manage | ✅ | ✅ | ❌ | ❌ | ❌ |
| Billing | billing.manage | ✅ | ❌ | ❌ | ❌ | ❌ |

### 2.3 Integrar con Filament

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.3.1 | Instalar Filament Shield | `composer require bezhansalleh/filament-shield` o crear Policies manualmente | Policies para cada Resource |
| 2.3.2 | Crear Policies | OrderPolicy, ProductPolicy, CustomerPolicy, BranchPolicy, etc. Cada policy usa `$user->can('permiso')`. | Policies registradas |
| 2.3.3 | Actualizar canAccessPanel | Reemplazar `config('app.admin_emails')` con check de rol via Spatie. Cualquier user con rol en tenant activo puede acceder. | `admin_emails` ya no se usa |
| 2.3.4 | Navigation por rol | Sales no ve "Items" ni "Precios". Operations solo ve "Tablero de Operations". Finance solo ve "Payments" y "Reportes". | Menu filtrado por permisos |

### 2.4 Tenant Switcher

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.4.1 | Crear componente | Si user tiene >1 tenant, mostrar selector en header de Filament. Al cambiar tenant: actualizar session, reasignar rol Spatie, redirect a dashboard. | UI funcional |
| 2.4.2 | Single tenant auto-select | Si user pertenece a 1 solo tenant, auto-seleccion sin mostrar switcher. | UX fluida |

### 2.5 Activity Log

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.5.1 | Configurar logging | Agregar trait `LogsActivity` a models criticos: Order, Customer, Product, PricingRule. Registra created/updated/deleted con old/new values. | Logs generados automaticamente |
| 2.5.2 | Filtrar por tenant | Activity log incluye tenant_id. Solo se muestran logs del tenant activo. | Aislamiento de audit trail |
| 2.5.3 | UI en Filament | Pagina "Actividad" que muestra log con filtros por user, model, fecha. | Pagina funcional |

### 2.6 Actualizar Portal del Customer

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.6.1 | Filtrar por tenant | CustomerPortalController filtra orders por tenant_id del customer. Un customer de tenant A no ve orders de tenant B. | Portal aislado por tenant |
| 2.6.2 | Login por tenant | Si un customer tiene cuentas en multiples tenants (mismo telefono), mostrar selector o determinar por URL/subdomain (futuro). | Login funcional |

### Criterios de Aceptacion Fase 2

- [x] 5 roles creados con permisos granulares (15 permisos)
- [x] Filament Resources respetan permisos (9 Policies)
- [x] `canAccessPanel()` usa tenant membership en vez de `admin_emails`
- [x] Tenant switcher funcional para users multi-tenant
- [x] Activity log registra actions criticas por tenant
- [ ] Portal del customer filtrado por tenant (ya filtrado por EnsureCustomerTenant de Fase 1)
- [x] `composer test` pasa al 100% (84 tests)
- [x] `composer analyse` sin errores nuevos (solo preexistentes)

---

## FASE 3: Registration y Onboarding (Semanas 5-6)

**Objetivo:** Nuevas businesss pueden registrarse y empezar a usar el sistema en <30 segundos. Al final de esta fase: SaaS Template puede tener su primer beta tester externo.

### 3.1 Pagina de Registration

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.1.1 | Form de registration | Campos: name del dueno, email, password, name del negocio, telefono, ciudad. Validacion server-side con Form Request. | Form funcional |
| 3.1.2 | Crear User + Tenant | Al submit: crear User, crear Tenant (slug auto-generado), crear tenant_user (role: owner), setear session. | Tenant creado en <5s |
| 3.1.3 | Email de verificacion | Enviar email de verificacion (middleware `verified` optional para MVP). | Email enviado |
| 3.1.4 | Trial automatico | `trial_ends_at = now() + 14 days`. Plan default: starter. | Trial activo |

### 3.2 Seed Data por Tenant

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.2.1 | TenantSeeder parametrico | Al crear tenant: insertar 5 sizes (CH/MD/GD/EG/XX), 2 item_categories (Basicas/Premium), ~10 pricing_rules template, 10 dimension_limits, business_configs default. | Datos creados automaticamente |
| 3.2.2 | Seed idempotente | Si ya existen registrations (re-seed), no duplicar. Usar `firstOrCreate`. | No duplica datos |
| 3.2.3 | Tiempo < 30s | Desde submit de registration hasta dashboard funcional: menos de 30 segundos. | Performance verificada |

### 3.3 Onboarding Wizard

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.3.1 | Step 1: Info del negocio | Name, direccion, Messaging del negocio, logo (upload). Guardar en tenant.settings. | Settings guardados |
| 3.3.2 | Step 2: Primera location | Name, direccion, ciudad, estado, telefono, horario. Crear Branch. | Location creada |
| 3.3.3 | Step 3: Primer item | Seleccionar model, color, titulo. Crear Product con ItemCategory. | Item creado |
| 3.3.4 | Step 4: Primer order guiado | Tutorial interactivo que guia al user por el Order Wizard. "Crea tu primer order de test". | Order de test creado |
| 3.3.5 | Skip option | Cada paso puede saltarse. Banner "Completa tu configuracion" en dashboard hasta completar todos. | Skip funcional |

### 3.4 Trial de 14 Dias

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.4.1 | Banner de trial | Widget en dashboard: "Te quedan X dias de test gratis". Color cambia: verde (>7d), amarillo (3-7d), rojo (<3d). | Banner visible |
| 3.4.2 | Trial sin tarjeta | No se pide tarjeta al registrar. Plan Starter completo durante 14 dias. | Sin bloqueo por payment |
| 3.4.3 | Trial expiry tracking | Flag `trial_expired` para UI. No bloquear acceso inmediatamente (grace period 3 dias). | Grace period funcional |

### 3.5 Tests Multi-Tenant

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.5.1 | Test de aislamiento | Feature test: tenant A crea order, tenant B no la ve en query ni en API. | Test pasa |
| 3.5.2 | Test de registration | Feature test: submit registration → user + tenant + tenant_user + seed data creados. | Test pasa |
| 3.5.3 | Test de roles | Feature test: Sales no puede acceder a ProductResource. Operations solo ve tablero. | Test pasa |
| 3.5.4 | Test de onboarding | Feature test: completar 4 pasos → location, item, order creados en el tenant correcto. | Test pasa |

### Criterios de Aceptacion Fase 3

- [x] Formulario de registration publico funcional
- [x] Nuevo tenant se crea con seed data en <30 segundos
- [x] Onboarding wizard de 2 pasos (location + primer order)
- [x] Formulario de location identico a BranchResource (state, zip, schedule)
- [x] Trial de 14 dias sin tarjeta
- [x] Banner de trial visible en dashboard
- [x] FirstProductBanner post-onboarding (desaparece al crear item)
- [x] Tests de aislamiento, registration, roles, onboarding pasando (104 tests)
- [x] SaaS Template (tenant #1) sigue funcionando sin cambios
- [x] **MILESTONE: Primer beta tester externo puede registrarse**

---

## FASE 4: Stripe + Plans (Semanas 7-8)

**Objetivo:** Cobrar suscripciones. Al final de esta fase: un customer puede pagar y su plan se activa automaticamente.

### 4.1 Instalar Laravel Cashier

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.1.1 | Instalar Cashier | `composer require laravel/cashier` + `php artisan cashier:install` + migrate | Package instalado |
| 4.1.2 | Configurar Billable | Agregar trait `Billable` al model Tenant (no a User). Tenant es el suscriptor. | Tenant es billable |
| 4.1.3 | Configurar Stripe | Crear cuenta Stripe MX. Agregar `STRIPE_KEY` y `STRIPE_SECRET` a .env y config/services.php. | Stripe en modo test |

### 4.2 Crear Planes en Stripe

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.2.1 | Items en Stripe | 3 items: SaaS Template Starter, SaaS Template Growth, SaaS Template Pro. | Items creados |
| 4.2.2 | Precios mensuales | Starter $299 USD/mes, Growth $699 USD/mes, Pro $1,299 USD/mes. | Precios creados |
| 4.2.3 | Precios anuales | Starter $239 USD/mes ($2,868/ano), Growth $559 USD/mes ($6,708/ano), Pro $1,039 USD/mes ($12,468/ano). Descuento 20%. | Precios anuales creados |
| 4.2.4 | Config local | Guardar price IDs de Stripe en config/saas.php: plans.starter.monthly, plans.starter.yearly, etc. | Config centralizada |

### 4.3 Billing Page

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.3.1 | Ruta /billing | Pagina accesible solo para Owner. Muestra: plan actual, fecha de renovacion, metodo de payment, historial de facturas. | Pagina funcional |
| 4.3.2 | Upgrade/Downgrade | Botones para cambiar plan. Prorratea automaticamente via Stripe. | Cambio de plan funcional |
| 4.3.3 | Cancelar suscripcion | Boton "Cancelar". Acceso hasta fin de periodo pagado. Exit survey modal. | Cancelacion funcional |
| 4.3.4 | Portal de Stripe | Link "Gestionar metodo de payment" → Stripe Customer Portal. | Portal accesible |

### 4.4 Stripe Checkout

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.4.1 | Checkout flow | Click "Suscribirse" → redirect a Stripe Checkout → payment → redirect a /billing con success. | Flujo completo |
| 4.4.2 | Trial en checkout | Si trial activo, Checkout incluye trial period remaining. No cobra hasta que expire. | Trial respetado |

### 4.5 Webhooks de Stripe

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.5.1 | Endpoint webhook | `POST /stripe/webhook` — Cashier maneja automaticamente. | Endpoint registrado |
| 4.5.2 | subscription.created | Activar tenant: `subscribed_at = now()`, `plan = selected_plan`. | Tenant activado |
| 4.5.3 | invoice.paid | Renovar suscripcion. Log en activity. | Renovacion exitosa |
| 4.5.4 | subscription.deleted | Grace period. Suspender acceso al final del periodo pagado. | Suspension funcional |
| 4.5.5 | invoice.payment_failed | Email al Owner. Reintento automatico (Stripe dunning). Despues de 3 fallos: suspender. | Dunning funcional |

### 4.6 Plan Limits Middleware

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.6.1 | Middleware `CheckPlanLimits` | Antes de crear order: orders_count < plan.max_orders. Antes de crear user: users_count < plan.max_users. Antes de crear item: products_count < plan.max_products. | Limites verificados |
| 4.6.2 | Soft limit + grace | Al 100%: banner "Alcanzaste el limite de tu plan". Grace period de 7 dias. Hard block despues. | UX clara |
| 4.6.3 | Banner al 80% | Al 80% del limite: "Has usado X de Y orders este mes. Considera upgrade." | Banner visible |

**Limites por plan:**

| Limite | Starter | Growth | Pro |
|--------|:-------:|:------:|:---:|
| Orders/mes | 50 | 200 | Ilimitados |
| Users | 2 | 5 | 15 |
| Locationes | 1 | 2 | 5 |
| Items | 20 | 50 | Ilimitados |
| Customers | 200 | 1,000 | Ilimitados |

### Criterios de Aceptacion Fase 4

- [x] Laravel Cashier v16 instalado y configurado con Tenant como Billable (USD)
- [x] 3 planes con precios mensuales y anuales en config/saas.php (price IDs via env)
- [x] Billing page funcional (solo Owner, /admin/billing)
- [x] Stripe Checkout → payment → webhook → plan activo
- [x] Webhooks manejan: created, updated, deleted, payment_failed
- [x] Plan limits enforcement via Policies (no middleware) + PlanLimitsBanner
- [x] SaaS Template = plan Pro con subscribed_at (sin Stripe)
- [x] 21 tests nuevos (125 total, 348 assertions)

---

## FASE 5: Trial Flow + Landing (Semanas 9-10)

**Objetivo:** Conversion de trials a pagados + presencia publica. Al final de esta fase: SaaS Template tiene landing page y flujo completo trial → paid.

### 5.1 Trial Expiry Flow

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.1.1 | Scheduled job: trial_check | Job diario: encontrar trials que expiran en 3 dias, 1 dia, hoy. Enviar email en cada caso. | Emails enviados automaticamente |
| 5.1.2 | Suspension post-trial | Trial expirado + no suscrito → `is_active = false`. Middleware bloquea admin. Portal del customer sigue activo (customers ven orders existentes). | Suspension funcional |
| 5.1.3 | Pagina /suspended | Pagina para tenants suspendidos: "Tu periodo de test termino. Suscribete para continuar." + botones de plan. | Pagina funcional |

### 5.2 Conversion UI

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.2.1 | Banner urgente | Ultimos 3 dias de trial: banner rojo fijo en dashboard "Tu test termina en X dias. Suscribete ahora." | Banner visible |
| 5.2.2 | Modal de upgrade | Modal con table comparativa de planes + CTAs a Stripe Checkout. Aparece al intentar crear recurso cuando en soft limit. | Modal funcional |
| 5.2.3 | Botones en dashboard | Widget "Tu Plan" con plan actual + boton "Upgrade" siempre visible para Starter/Growth. | Widget funcional |

### 5.3 Landing Page SaaS

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.3.1 | Hero section | Titulo: "El sistema operativo de tu business". Subtitulo + CTA "Test gratis 14 dias". Screenshot de la app. | Hero funcional |
| 5.3.2 | Features section | 6 features principales con iconos: Orders, Operations, Quotes, Payments, Portal Customer, Dashboard. | Seccion funcional |
| 5.3.3 | Testimonials | Testimonial de SaaS Template (primer user). "Construido por una business real." | Seccion funcional |
| 5.3.4 | CTA final | "Empieza tu test gratis de 14 dias" → link a registration. | CTA funcional |
| 5.3.5 | Deploy en saas-template.mx | Puede ser la misma app Laravel con layout diferente, o una landing estatica separada. | Landing online |

### 5.4 Pricing Page

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.4.1 | Table comparativa | 3 columns: Starter, Growth, Pro. Features, limites, precios. Toggle mensual/anual. | Pagina funcional |
| 5.4.2 | CTAs por plan | Cada plan con boton "Empezar test gratis" → registration con plan pre-seleccionado. | CTAs funcionales |
| 5.4.3 | FAQ de pricing | Preguntas frecuentes: "Puedo cancelar?", "Que pasa si supero el limite?", etc. | FAQ funcional |

### 5.5 Reportes Basicos

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.5.1 | Export CSV | Exportar orders, payments, customers a CSV. Disponible desde Growth. | Export funcional |
| 5.5.2 | Reporte mensual | Total orders, ingresos, top items, top customers del mes. Widget en dashboard. | Reporte funcional |

### 5.6 Super-Admin Dashboard

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.6.1 | Panel /super-admin | Filament panel separado con guard propio. Solo users con `is_super_admin = true`. | Panel accesible |
| 5.6.2 | Metricas SaaS | Widgets: MRR, ARR, tenants activos, trials activos, churn este mes, NRR. | Widgets funcionales |
| 5.6.3 | Lista de tenants | Table: name, plan, status, orders/mes, suscripcion, health. Filtros. | Table funcional |
| 5.6.4 | Impersonar tenant | Boton "Ver como Owner" → session temporal como el owner del tenant. | Impersonacion funcional |
| 5.6.5 | Suspender/reactivar | Acciones manuales para suspender y reactivar tenants. | Acciones funcionales |
| 5.6.6 | Reset onboarding | Boton en lista de tenants para resetear onboarding (`onboarding_steps = null`, `onboarding_completed_at = null`). El tenant vera el wizard de nuevo al entrar. Util para soporte. | Boton funcional, tenant ve wizard |

### Criterios de Aceptacion Fase 5

- [x] Job diario de trial expiry funcional (emails 3d, 1d, 0d)
- [x] Tenants suspendidos ven pagina /suspended con CTA
- [x] Landing page de SaaS Template en /saas-template
- [x] Pricing page con toggle mensual/anual y FAQ
- [x] Export CSV funcional (Growth+) con plan gating
- [x] Super-admin dashboard con metricas SaaS
- [x] **MILESTONE: Primer customer puede pagar y usar el sistema**

---

## FASE 6: Retencion (Semanas 11-12)

**Objetivo:** Retener customers y reducir churn. Al final de esta fase: sistema detecta riesgo de cancelacion y actua proactivamente.

### 6.1 Health Score

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.1.1 | Artisan command | `php artisan saas:health-score` — job diario que calcula score por tenant. Factores: login reciente (30%), orders recientes (30%), features usadas (20%), users activos (20%). | Score calculado diariamente |
| 6.1.2 | Escala 0-100 | 80-100 = Saludable (verde). 50-79 = En riesgo (amarillo). 0-49 = Critico (rojo). | Score visible en super-admin |
| 6.1.3 | Feature usage tracking | Table `feature_usage`: tenant_id, feature, user_id, used_at. Registrar uso de features core. | Tracking funcional |

### 6.2 Alertas Proactivas

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.2.1 | Email: sin orders 14d | Si tenant no crea orders en 14 dias → email "Te echamos de menos" con tips. | Email automatico |
| 6.2.2 | Email: usage baja | Si health score baja de 50 → email al equipo SaaS Template para intervenir. | Alerta interna |

### 6.3 Exit Survey

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.3.1 | Modal al cancelar | "Por que cancelas?" — opciones: Precio, Falta features, Cerro negocio, Competencia, Otro. | Response guardada |
| 6.3.2 | Analytics de churn | Dashboard super-admin: razones de cancelacion agrupadas. | Datos visibles |

### 6.4 Win-back Emails

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.4.1 | Email 7d post-cancelacion | "Vuelve a SaaS Template — 1 mes gratis". Link con cupon de Stripe. | Email automatico |

### 6.5 Referral Program

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.5.1 | Link de referido | Owner puede generar link unico. Al registrarse un referido: ambos reciben 1 mes gratis al convertir a pagado. | Links funcionales |
| 6.5.2 | Tracking de referidos | Table referrals: referrer_tenant_id, referred_tenant_id, converted_at, rewarded_at. | Tracking funcional |

### 6.6 Notificaciones Automaticas por Messaging

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.6.1 | Messaging automatico | Al cambiar status de order: enviar Messaging al customer automaticamente (no manual como ahora). Configurable por tenant en settings. | Notificaciones automaticas |
| 6.6.2 | Toggle en settings | Tenant puede activar/desactivar notificaciones automaticas por cada tipo de transicion. | Settings funcional |

### Criterios de Aceptacion Fase 6

- [x] Health score calculado diariamente por tenant (0-100)
- [x] Alertas automaticas por email cuando tenant esta inactivo
- [x] Exit survey al cancelar con analytics de razones
- [x] Win-back email 7 dias post-cancelacion
- [x] Programa de referidos funcional
- [x] Messaging automatico configurable por tenant

---

## FASE 7: Features Premium (Semanas 13-14)

**Objetivo:** Features que justifican el upgrade a Growth y Pro. Al final de esta fase: product-market fit.

### 7.1 Branding en PDFs

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 7.1.1 | Upload de logo | Tenant sube logo en settings. Guardar en R2/storage por tenant. | Upload funcional |
| 7.1.2 | Logo en PDFs | PdfGenerator lee logo del tenant. Muestra en header de PDF de order y cotizacion. | Logo visible en PDFs |
| 7.1.3 | Feature gate | Solo Growth+ puede subir logo. Starter muestra "Upgrade para branding personalizado". | Feature gated |

### 7.2 Reportes Avanzados

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 7.2.1 | Rentabilidad por item | Reporte: item, unidades vendidas, ingresos, porcentaje del total. | Reporte funcional |
| 7.2.2 | Top customers | Reporte: customer, orders, total gastado, ultimo order. | Reporte funcional |
| 7.2.3 | Comparativo mensual | Grafica: este mes vs mes anterior (orders, ingresos). | Grafica funcional |
| 7.2.4 | Export PDF/CSV | Exportar cualquier reporte a PDF o CSV. | Export funcional |

### 7.3 Facturacion Fiscal (CFDI)

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 7.3.1 | Evaluar proveedor PAC | Investigar Facturapi, Stamping, otro. Evaluar costo, API, soporte Laravel. | Proveedor seleccionado |
| 7.3.2 | Integracion basica | Desde order: boton "Generar factura". Pide datos fiscales del customer (RFC, razon social, uso CFDI). | Factura generada |
| 7.3.3 | Almacenar XML/PDF | Guardar factura (XML + PDF) vinculada al order. Visible en portal del customer. | Files almacenados |
| 7.3.4 | Feature gate | Solo Pro puede facturar. | Feature gated |

### 7.4 Corte de Caja

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 7.4.1 | Reporte diario | Payments del dia agrupados por metodo (cash, transferencia, tarjeta, Bank Transfer, Wire Transfer). Total del dia. Comparativo vs dia anterior. | Reporte funcional |
| 7.4.2 | Rango de fechas | Filtrar por rango: hoy, esta semana, este mes, personalizado. | Filtros funcionales |

### 7.5 Cola de Operations

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 7.5.1 | Prioridad de orders | Campo priority en orders (1-5). Default 3 (normal). | Campo funcional |
| 7.5.2 | Tablero ordenado | Tablero de operations ordena por prioridad (alta primero) + fecha. | Ordenamiento funcional |
| 7.5.3 | Drag & drop (optional) | Reordenar orders en tablero con drag & drop (Alpine.js + Livewire). | UI interactiva |

### Criterios de Aceptacion Fase 7

- [x] Logo del tenant visible en PDFs (Growth+)
- [x] Reportes de rentabilidad, top customers, comparativo mensual
- [x] Integracion basica con proveedor PAC para CFDI (Pro)
- [x] Corte de caja diario con filtros
- [x] Cola de operations con prioridades
- [x] **MILESTONE: Product-market fit — features justifican cada tier de precio**

---

## FASE 8: Integraciones (Semanas 15-16)

**Objetivo:** Conectar SaaS Template con el mundo exterior. Al final de esta fase: API publica documentada + webhooks salientes.

### 8.1 API Publica (Sanctum)

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 8.1.1 | Sanctum tokens | Token de API por tenant. Generacion desde Settings. Token tiene tenant_id asociado. | Tokens funcionales |
| 8.1.2 | API endpoints | REST API: GET/POST orders, GET customers, GET products, GET payments. JSON responses consistentes. | Endpoints funcionales |
| 8.1.3 | Documentacion API | Scramble o manual. Docs publicas en api.saas-template.mx o /docs. | Docs publicadas |
| 8.1.4 | Feature gate | Solo Pro tiene acceso a API. | Feature gated |

### 8.2 Webhooks Salientes

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 8.2.1 | Configuracion por tenant | Settings: URL de webhook + eventos a enviar (order.created, order.status_changed, payment.received). | Config funcional |
| 8.2.2 | Envio async | Job en queue: POST al webhook URL con payload JSON + signature HMAC. Retry 3 veces. | Envio funcional |
| 8.2.3 | Logs de webhooks | Table webhook_logs: tenant_id, event, url, status_code, response, created_at. | Logs visibles en settings |

### 8.3 Rate Limiting por Tenant

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 8.3.1 | Throttle por plan | Starter: 60 req/min, Growth: 120 req/min, Pro: 300 req/min. Basado en API token. | Rate limiting funcional |
| 8.3.2 | Headers de rate limit | `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`. | Headers presentes |

### 8.4 Integracion Shopify (Exploratorio)

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 8.4.1 | Investigar | Evaluar Shopify API para sync de items y orders. POC basico. | Documento de viabilidad |
| 8.4.2 | POC (si viable) | Sync basico: item Shopify → item SaaS Template. Order Shopify → order SaaS Template. | POC funcional |

### Criterios de Aceptacion Fase 8

- [x] API REST documentada con Sanctum tokens (Pro)
- [x] Webhooks salientes configurables por tenant
- [x] Rate limiting por plan enforced
- [x] POC de Shopify evaluado

---

## FASE 9: Optimizacion (Semanas 17-18)

**Objetivo:** Performance, security, y polish final. Al final de esta fase: SaaS Template listo para growth marketing agresivo.

### 9.1 Performance Audit

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.1.1 | ✅ Query optimization | Fix N+1 en MonthlyReport, optimizar Tenant::usageCounts() con subqueries combinados. | p95 < 500ms |
| 9.1.2 | ✅ Caching | Cache para usage counts (5min) y dashboard stats (60s). CacheInvalidationObserver para invalidar en CUD. | Response times mejorados |
| 9.1.3 | ✅ Queue workers | Queue worker en docker-entrypoint.sh (background). Database driver; preparado para Redis. | 0 jobs sincronos |

### 9.2 Infraestructura SaaS

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.2.1 | ✅ CI/CD GitHub Actions | Workflow: lint → test → analyse. Dependabot: composer, npm, actions. | Pipeline funcional |
| 9.2.2 | ✅ Redis preparation | phpredis en Dockerfile, config ready para REDIS_URL. | Config preparada |
| 9.2.3 | ✅ Storage R2 preparation | flysystem-aws-s3-v3 instalado, .env.example con variables R2. | Driver instalado |
| 9.2.4 | ✅ Health check endpoint | GET /api/health verifica DB, cache, storage. 200/503. | Endpoint funcional |

### 9.3 Security Audit

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.3.1 | ✅ Session security | SESSION_ENCRYPT=true, SESSION_SECURE_COOKIE=true documentados para prod. | Sessions encriptadas |
| 9.3.2 | ✅ Encrypted tenant settings | HasEncryptedSettings trait, encrypt/decrypt individual keys, migration command. | Keys no visibles en BD |
| 9.3.3 | ✅ Tenant isolation tests | 8 tests: Eloquent, API v1, WebhookLog, direct DB. | 0 data leaks |
| 9.3.4 | ✅ OWASP hardening | SecurityHeaders middleware, verified Blade templates, mass assignment. | 0 vulnerabilidades criticas |
| 9.3.5 | ✅ Dependabot | Configurado en PR1 (.github/dependabot.yml). | Alertas activas |

### 9.4 Monitoring

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.4.1 | ✅ Sentry | sentry/sentry-laravel v4 con tenant context (id, name, plan). | Errores en Sentry |
| 9.4.2 | ✅ Health check + Activation Funnel | GET /api/health (PR1), ActivationFunnel widget super-admin. | Endpoint + Funnel funcional |
| 9.4.3 | ✅ Uptime monitoring docs | Documentado en RAILWAY_DEPLOY_STATUS.md (UptimeRobot + Sentry). | Documentacion lista |

### 9.5 Onboarding Optimization

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.5.1 | Metricas de activacion | Trackear: signup → onboarding → primer order → segundo order → PDF compartido. | Funnel visible en super-admin |
| 9.5.2 | Optimizar TTV | Time to Value < 24h. Simplificar pasos si data muestra drop-off. | TTV medido |

### 9.6 Documentacion User

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.6.1 | Guias de uso | Guias paso a paso: crear order, cotizar, operations, payments, portal. | 5+ guias |
| 9.6.2 | FAQ publico | Preguntas frecuentes en landing. | FAQ online |
| 9.6.3 | Video tutoriales | 3-5 videos cortos (Loom) mostrando features principales. | Videos publicados |

### 9.7 Custom Domains (Futuro)

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 9.7.1 | Vanity subdomains | tenant.saas-template.app via Cloudflare wildcard SSL. | Subdomains funcionales |
| 9.7.2 | Custom domain | orders.mi-business.com → CNAME a app.saas-template.mx. SSL via Cloudflare for SaaS. | Custom domains funcionales |

### Criterios de Aceptacion Fase 9

- [x] p95 response time < 500ms (caching layer + query optimization)
- [x] Redis preparado (config ready, phpredis en Dockerfile, REDIS_URL)
- [x] Storage R2 preparado (flysystem-aws-s3-v3, config ready)
- [x] CI/CD GitHub Actions funcional (lint + test + analyse)
- [x] Security audit: session encryption, encrypted settings, OWASP headers, tenant isolation tests
- [x] Sentry + health checks + uptime monitoring docs
- [ ] Documentacion de user basica (guias + FAQ) — diferido a post-launch
- [x] **MILESTONE: SaaS Template GROWTH READY (v1.0.0)**

---

## Resumen de Milestones

```
Semana 2  ✅ Multi-tenancy funcionando — datos aislados (v0.4.0, 2026-02-19)
Semana 4  ✅ Roles y permisos completos — 5 roles, permisos granulares (v0.5.0, 2026-02-20)
Semana 6  ✅ Registration + onboarding + trial → PRIMER BETA TESTER (v0.6.0, 2026-02-20)
Semana 8  ⬜ Stripe cobrando suscripciones → PRIMER CLIENTE PAGADO
Semana 10 ⬜ Landing + pricing + metricas → LANZAMIENTO PUBLICO
Semana 14 ⬜ Features premium + CFDI → PRODUCT-MARKET FIT
Semana 18 ⬜ API + integraciones + optimizacion → GROWTH READY
```

---

## Dependencias Criticas

| Fase | Depende de | Riesgo | Mitigacion |
|------|-----------|--------|-----------|
| Fase 1 | Nada (build sobre MVP) | Bajo | Datos de SaaS Template como validacion |
| Fase 4 | Cuenta Stripe MX verificada | Medio | Aplicar a Stripe desde Semana 1 |
| Fase 5 | Dominio saas-template.mx | Bajo | Comprar dominio desde Semana 1 |
| Fase 5 | Landing page copy/design | Medio | Usar template, iterar despues |
| Fase 7 | Proveedor PAC para CFDI | Medio | Evaluar Facturapi/Stamping desde Semana 8 |
| Fase 7 | Beta testers (5-10 businesss) | Alto | Empezar a reclutar desde Semana 4 |
| Fase 9 | Redis en Railway | Bajo | Railway lo provee nativo |

---

## Estrategia de Beta Testing

### Reclutamiento (Semana 4-6)

- **Target:** 5-10 businesss en Merida y alrededores
- **Canal:** Contacto directo (redes de SaaS Template), grupos de Facebook de businesss
- **Incentivo:** 3 meses gratis de plan Growth + feedback mensual
- **Criterio:** >20 orders/mes, dolor visible (usa Excel/Messaging), disponible para calls

### Programa Beta (Semana 6-14)

```
Semana 6-7:   Onboarding (videollamada 1:1, configurar negocio)
Semana 8-9:   Uso libre, recopilar feedback semanal
Semana 10-11: Introducir billing, primer invoice
Semana 12-13: Feature requests, priorizar
Semana 14:    Conversion a plan pagado o churn
```

### Metricas de Exito del Beta

| Metrica | Target |
|---------|:------:|
| Beta testers que crean >10 orders en primer mes | >60% |
| Conversion a plan pagado | >50% |
| NPS | >40 |
| Testimonials publicables | Al menos 2 |

---

## Costos Proyectados

### Infraestructura

```
Fase 1-3 (0 tenants, desarrollo):
  Railway (actual) .................. ~$10 USD/mes
  Total .............................. ~$10 USD/mes

Fase 4-5 (0-10 tenants, beta):
  Railway (web + worker + scheduler) . ~$18 USD/mes
  PostgreSQL (dedicated) ............. ~$10 USD/mes
  Redis ............................... ~$5 USD/mes
  Cloudflare (free) ................... ~$0
  Total .............................. ~$33 USD/mes

Fase 6-9 (10-100 tenants, crecimiento):
  Railway (scale up) ................. ~$40 USD/mes
  PostgreSQL (dedicated) ............. ~$15 USD/mes
  Redis ............................... ~$5 USD/mes
  R2 storage ......................... ~$5 USD/mes
  Sentry (free tier) ................. ~$0
  Total .............................. ~$65 USD/mes
```

### Services Externos

| Service | Costo | Cuando |
|---------|-------|--------|
| Stripe | 3.6% + $3 USD por transaccion | Fase 4+ |
| Dominio saas-template.mx | ~$15 USD/ano | Fase 5 |
| Cloudflare (free) | $0 | Fase 5+ |
| Sentry (free tier) | $0 | Fase 9 |
| UptimeRobot (free) | $0 | Fase 9 |
| Proveedor PAC (CFDI) | ~$500-1,000 USD/mes | Fase 7 |

### Break-even

```
Costo mensual total (Fase 4-5): ~$33 USD/mes (~$660 USD)
ARPU estimado: $499 USD/mes
Break-even: 2 customers pagados

Costo mensual total (Fase 6-9): ~$65 USD/mes (~$1,300 USD)
Break-even: 3 customers pagados
```

---

## Prioridades NO Negociables

1. **Tenant isolation es lo primero.** Sin esto no hay SaaS. Cero tolerancia a data leaks entre tenants.
2. **SaaS Template debe seguir funcionando.** Es el tenant #1 y la validacion del item.
3. **Cobrar lo antes posible.** No esperar a tener todo perfecto. Cobrar desde Fase 4.
4. **No over-engineer.** Column isolation, no schemas separados. Spatie Permission, no custom RBAC. Stripe Checkout, no billing custom.
5. **Metricas desde dia 1.** Si no se mide, no se puede mejorar. Health score, feature adoption, churn.
6. **Mobile-first data.** Todo lo que se implemente debe funcionar con la API REST para la app movil futura.

---

## Documentos de Referencia

| Documento | Contenido |
|-----------|----------|
| [1-SAAS_VISION.md](1-SAAS_VISION.md) | Propuesta de valor, ICP, diferenciacion, posicionamiento |
| [2-PRICING_STRATEGY.md](2-PRICING_STRATEGY.md) | 3 planes, limites, proyecciones de revenue |
| [3-MULTITENANCY_STRATEGY.md](3-MULTITENANCY_STRATEGY.md) | Column isolation, BelongsToTenant, roles, onboarding |
| [4-SAAS_ARCHITECTURE.md](4-SAAS_ARCHITECTURE.md) | Stack, auth, billing Stripe, observabilidad |
| [5-FEATURE_GAP_ANALYSIS.md](5-FEATURE_GAP_ANALYSIS.md) | MVP vs SaaS — que existe, que falta |
| [6-SAAS_METRICS.md](6-SAAS_METRICS.md) | MRR, churn, CAC, LTV, health score |
| [7-SAAS_ROADMAP.md](7-SAAS_ROADMAP.md) | Roadmap original (formato table simple) |
| [8-DATABASE_SAAS_SCHEMA.md](8-DATABASE_SAAS_SCHEMA.md) | Schema actual, tables nuevas, plan de migrations |
| [9-DEPLOY_STRATEGY.md](9-DEPLOY_STRATEGY.md) | Railway, CI/CD, storage R2, monitoring |
| [10-SECURITY_HARDENING.md](10-SECURITY_HARDENING.md) | Security SaaS, estado actual, checklist |

---

*Documento creado: 2026-02-19*
*Ultima actualizacion: 2026-02-20*
