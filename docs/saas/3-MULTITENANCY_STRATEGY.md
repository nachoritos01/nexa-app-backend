# Multitenancy Strategy: SaaS Template

> De single-tenant a multi-tenant sin reescribir el MVP.

---

## 1. Estado Actual (Single-Tenant)

El MVP actual opera como una sola instancia para un solo negocio (SaaS Template). No existe concepto de "empresa" o "tenant" — todos los datos (orders, customers, items) pertenecen a la unica empresa.

### Que existe hoy

- 1 base de datos PostgreSQL
- 1 panel admin Filament (acceso por email)
- 1 portal de customer (auth:customer)
- 0 concepto de organizacion/empresa/tenant
- 0 roles (solo "es admin" o "no es admin")
- 0 aislamiento de datos

---

## 2. Estrategia de Tenants

### Model elegido: Single Database + Column Isolation (tenant_id)

**Por que este model y no otros:**

| Model | Descripcion | Ventaja | Desventaja | Para SaaS Template |
|--------|-------------|---------|------------|----------------|
| DB por tenant | Una base de datos por empresa | Aislamiento total | Costo alto, migrations complicadas | No — overkill para nuestro tamano |
| Schema por tenant | Un schema PostgreSQL por empresa | Buen aislamiento | Complejo de manejar, migrations lentas | No — innecesaria complejidad |
| **Column isolation** | **Una BD, column `tenant_id` en cada table** | **Simple, escalable, bajo costo** | **Requiere disciplina en queries** | **Si — correcto para 0-1000 tenants** |

### Justificacion

- **Costo:** Una sola BD cuesta lo mismo con 1 o 100 tenants
- **Simplicidad:** No hay que crear/destruir infraestructura por cada customer nuevo
- **Migrations:** Un solo `php artisan migrate` aplica para todos
- **Consultas cross-tenant:** Posible para metricas SaaS del super-admin
- **Escalabilidad:** PostgreSQL con indices en `tenant_id` maneja millones de filas sin problema
- **Riesgo:** El unico riesgo es un query sin filtro de tenant. Se mitiga con Global Scopes de Laravel.

---

## 3. Implementacion Tecnica

### 3.1 Model Tenant

```
tenants
├── id (PK)
├── name                    -- "SaaS Template", "Impresiones JR"
├── slug                    -- "saas-template", "impresiones-jr" (para subdomain)
├── plan                    -- "starter", "growth", "pro"
├── owner_id (FK → users)   -- user que creo la cuenta
├── settings (JSON)         -- branding, moneda, configuraciones custom
├── is_active               -- para suspender cuentas morosas
├── trial_ends_at           -- fin del trial (nullable)
├── subscribed_at           -- inicio de suscripcion pagada
├── created_at
├── updated_at
```

### 3.2 Tables que necesitan tenant_id

Todas las tables de datos de negocio reciben `tenant_id`:

| Table | Prioridad | Notas |
|-------|-----------|-------|
| orders | Critica | Cada order pertenece a un tenant |
| order_lines | Critica | Via order.tenant_id (heredado) |
| customers | Critica | Un customer puede existir en multiples tenants (mismo telefono, diferentes cuentas) |
| customer_addresses | Critica | Via customer.tenant_id |
| products | Critica | Cada tenant tiene su propio catalogo |
| pricing_rules | Critica | Cada tenant tiene sus propios precios |
| payments | Critica | Via order.tenant_id (heredado) |
| quotes | Critica | Cada cotizacion pertenece a un tenant |
| branches | Alta | Locationes por tenant |
| item_categories | Alta | Tipos de item por tenant (pero pueden ser globales/template) |
| sizes | Media | Pueden ser globales (compartidos) con override por tenant |
| colors | Media | Pueden ser globales con override por tenant |
| dimension_limits | Media | Pueden ser globales |
| business_configs | Alta | Configuraciones por tenant |
| users | Critica | Via table pivot tenant_user |

### 3.3 Tables globales (SIN tenant_id)

| Table | Razon |
|-------|-------|
| tenants | Es la table de tenants misma |
| plans | Definicion de planes SaaS |
| subscriptions | Relacion tenant ↔ plan (Stripe) |
| users | Un user puede pertenecer a multiples tenants |
| tenant_user | Pivot: user ↔ tenant + role |

### 3.4 Global Scope para Aislamiento

```
Concepto (no codigo):

- Trait `BelongsToTenant` se agrega a todo model con tenant_id
- El trait registra un Global Scope que filtra por tenant_id del user autenticado
- Al crear registrations, se asigna tenant_id automaticamente
- Ningun query puede "olvidar" el filtro — el scope lo inyecta siempre
```

**Proteccion en capas:**
1. **Global Scope** — filtra automaticamente en Eloquent
2. **Middleware** — valida que el tenant_id del request sea valido
3. **Policy** — verifica pertenencia del recurso al tenant
4. **DB Index** — `tenant_id` indexado en todas las tables para performance

---

## 4. Roles y Permisos

### Estado actual

- Solo existe `canAccessPanel()` que chequea si el email esta en `config('app.admin_emails')`
- No hay roles, no hay permisos granulares
- Todos los admins pueden hacer todo

### Roles propuestos

| Rol | Descripcion | Puede | No puede |
|-----|-------------|-------|----------|
| **Owner** | Dueno de la cuenta. Creo el tenant. | Todo. Administrar suscripcion, agregar/eliminar users, ver metricas, configurar. | Nada le esta restringido. |
| **Admin** | Admin del negocio. Puede gestionar todo excepto billing. | Gestionar orders, items, customers, precios, locationes, users (excepto owner). | Administrar suscripcion/billing. |
| **Sales** | Vendedor. Crea orders y atiende customers. | Crear/editar orders, ver customers, generar quotes, compartir PDFs/Messaging, registrar payments. | Editar items/pricing, gestionar users, ver metricas financieras completas. |
| **Operations** | Operador de operations. Ejecuta orders. | Ver tablero de operations, marcar items como producidos, ver detalles de order (solo lectura). | Crear/editar orders, ver payments, gestionar customers. |
| **Finance** | Finanzas. Ve payments y reportes. | Ver payments, generar reportes, ver metricas financieras, exportar datos. | Crear/editar orders, gestionar items, gestionar users. |

### Implementacion de permisos

```
Model: Role-Based Access Control (RBAC) con Spatie Permission

Paquete: spatie/laravel-permission
  - Tables: roles, permissions, role_has_permissions, model_has_roles, model_has_permissions
  - Los roles son globales (Owner, Admin, Sales, Operations, Finance)
  - Los permisos se asignan a roles, los roles se asignan a users

Asignacion de rol por tenant:
  - Spatie Permission no es multi-tenant por defecto
  - Solucion: usar la table pivot `tenant_user` para registrar que rol tiene
    un user en cada tenant (column `role` como referencia rapida)
  - Al hacer login, se asigna el rol Spatie correspondiente al tenant activo
  - Si el user cambia de tenant, se reasigna el rol Spatie
  - Esto permite: Juan es Owner en tenant A (tiene rol Spatie "owner")
    y Sales en tenant B (se le reasigna rol Spatie "sales" al cambiar)

Filament Shield: genera policies automaticamente basadas en roles Spatie
```

### Permisos granulares

| Grupo | Permiso | Owner | Admin | Sales | Operations | Finance |
|-------|---------|:-----:|:-----:|:------:|:----------:|:------------:|
| **Orders** | orders.view | Si | Si | Si | Solo asignados | Si |
| | orders.create | Si | Si | Si | No | No |
| | orders.edit | Si | Si | Si | No | No |
| | orders.delete | Si | Si | No | No | No |
| | orders.export | Si | Si | No | No | Si |
| **Operations** | production.view | Si | Si | No | Si | No |
| | production.mark | Si | Si | No | Si | No |
| **Items** | products.view | Si | Si | Si | No | No |
| | products.manage | Si | Si | No | No | No |
| **Customers** | customers.view | Si | Si | Si | No | No |
| | customers.manage | Si | Si | No | No | No |
| **Payments** | payments.view | Si | Si | Si | No | Si |
| | payments.create | Si | Si | Si | No | No |
| | payments.delete | Si | Si | No | No | No |
| **Precios** | pricing.view | Si | Si | Si | No | No |
| | pricing.manage | Si | Si | No | No | No |
| **Reportes** | reports.view | Si | Si | No | No | Si |
| | reports.export | Si | Si | No | No | Si |
| **Configuracion** | settings.view | Si | Si | No | No | No |
| | settings.manage | Si | Si | No | No | No |
| **Users** | users.view | Si | Si | No | No | No |
| | users.manage | Si | Si | No | No | No |
| **Billing** | billing.manage | Si | No | No | No | No |

---

## 5. Onboarding de Nuevo Tenant

### Flujo de registration

```
1. Landing page → "Test gratis 14 dias"
2. Formulario: name, email, password, name del negocio, telefono
3. Sistema crea:
   - User (owner)
   - Tenant (slug auto-generado del name)
   - tenant_user (role: owner)
   - Datos default: sizes (5), item_categories (2), pricing_rules template
4. Redirect a onboarding wizard:
   - Step 1: Info del negocio (name, direccion, Messaging, logo)
   - Step 2: Primera location
   - Step 3: Primer item
   - Step 4: Primer order (guiado)
5. Dashboard listo para usar
```

### Seed data por tenant

Al crear un tenant nuevo, se pre-cargan:

| Dato | Cantidad | Notas |
|------|----------|-------|
| Sizes | 5 | CH, MD, GD, EG, XX (standard) |
| Product Models | 2 | Basicas, Premium (template) |
| Pricing Rules | ~10 | Template de precios por defecto (editables) |
| Dimension Limits | 10 | 5 tallas x 2 lados |
| Business Config | ~10 | Defaults (moneda USD, anticipio 50%, etc.) |

### Tiempo de provision

- **Objetivo:** <30 segundos desde registration hasta dashboard funcional
- **No se requiere** crear subdomain, ni base de datos, ni servidor
- **Todo es software:** crear registrations en la BD y asignar permisos

---

## 6. Acceso Multi-Tenant

### URL Strategy

```
Opcion A (Recomendada para MVP SaaS):
app.saas-template.mx/admin          -- Todos los tenants usan la misma URL
                                 -- El tenant se determina por el user autenticado

Opcion B (Futuro — vanity subdomains):
saas-template.saas-template.mx       -- Cada tenant tiene su propio subdomain
impresiones-jr.saas-template.mx      -- Mas profesional, mas complejo de implementar
```

**Recomendacion:** Empezar con Opcion A. Los subdomains son un nice-to-have que agrega complejidad DNS/SSL innecesaria al inicio.

### Portal del Customer Multi-Tenant

```
Opcion A (MVP SaaS):
app.saas-template.mx/my-account       -- El customer se autentica y ve orders de su tenant

Opcion B (Futuro):
saas-template.saas-template.mx/my-account  -- Portal con branding del tenant
```

### Resolucion de Tenant

```
Flujo de autenticacion:

1. User hace login
2. Sistema verifica credenciales
3. Si el user pertenece a 1 solo tenant → auto-seleccion
4. Si pertenece a multiples tenants → "Selecciona tu negocio" (tenant switcher)
5. Se establece el tenant activo en sesion
6. Todas las queries usan el tenant_id de la sesion
```

---

## 7. Migracion del MVP Actual

### Steps para agregar multitenancy al MVP

```
1. Crear migracion: table `tenants`
2. Crear migracion: table `tenant_user` (pivot con role)
3. Crear migracion: agregar `tenant_id` a todas las tables de negocio
4. Crear migracion: poblar tenant_id con tenant default (SaaS Template)
5. Crear trait `BelongsToTenant` con Global Scope
6. Agregar trait a todos los models con tenant_id
7. Crear middleware `EnsureTenant` para validar tenant en sesion
8. Integrar Spatie Permission (o similar) para roles
9. Actualizar Filament para respetar tenant y roles
10. Actualizar portal del customer para filtrar por tenant
```

### Suspension y Reactivacion de Tenants

```
Suspension (tenant.is_active = false):
  Triggers:
    - Trial expirado sin conversion a pagado
    - Suscripcion cancelada (grace period terminado)
    - Payment fallido despues de 3 reintentos (Stripe dunning)
    - Suspension manual por admin SaaS Template

  Efecto:
    - Middleware bloquea acceso al panel admin (redirect a /suspended)
    - Portal del customer sigue activo (customers pueden ver orders existentes)
    - No se pueden crear orders, items ni customers nuevos
    - Links de payment existentes siguen funcionando
    - Datos NO se eliminan

Reactivacion:
    - Owner paga → webhook Stripe → is_active = true
    - Admin SaaS Template reactiva manualmente
    - Acceso se restaura inmediatamente

Eliminacion de datos (post-cancelacion):
    - 90 dias despues de cancelacion definitiva
    - Email de aviso 30 dias y 7 dias antes
    - Export CSV disponible durante el periodo de gracia
    - Datos se borran de forma irreversible (GDPR/privacy compliance)
```

### Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|:------------:|:-------:|-----------|
| Query sin filtro de tenant (data leak) | Media | Critico | Global Scope required + tests automatizados |
| Performance con table grande | Baja | Alto | Indices en tenant_id, query optimization |
| User en multiples tenants (confuso) | Media | Medio | Tenant switcher claro en UI |
| Migracion de datos existentes | Baja | Medio | Script que asigna tenant_id default |

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-17*
