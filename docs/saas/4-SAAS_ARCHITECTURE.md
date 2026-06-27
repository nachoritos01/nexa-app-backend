# SaaS Architecture: SaaS Template

> Arquitectura tecnica para escalar de MVP a SaaS multi-tenant.

---

## 1. Diagrama Conceptual

```
                    ┌─────────────────────────────────────────┐
                    │              CLOUDFLARE                  │
                    │         CDN + WAF + SSL + DDoS           │
                    └──────────────────┬──────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────┐
                    │           RAILWAY (PaaS)                 │
                    │                                          │
                    │  ┌─────────────────────────────────┐    │
                    │  │     Laravel App (Docker)          │    │
                    │  │                                   │    │
                    │  │  ┌───────────┐  ┌─────────────┐  │    │
                    │  │  │ Filament  │  │  Livewire    │  │    │
                    │  │  │ Admin     │  │  Portal      │  │    │
                    │  │  │ Panel     │  │  Customer     │  │    │
                    │  │  └───────────┘  └─────────────┘  │    │
                    │  │                                   │    │
                    │  │  ┌───────────┐  ┌─────────────┐  │    │
                    │  │  │ REST API  │  │  Webhooks    │  │    │
                    │  │  │ (Sanctum) │  │  (Payment Gateway)   │  │    │
                    │  │  └───────────┘  └─────────────┘  │    │
                    │  │                                   │    │
                    │  │  ┌─────────────────────────────┐  │    │
                    │  │  │  Tenant Middleware Layer     │  │    │
                    │  │  │  Global Scopes + RBAC       │  │    │
                    │  │  └─────────────────────────────┘  │    │
                    │  └─────────────────────────────────┘    │
                    │                                          │
                    │  ┌──────────┐  ┌──────────┐             │
                    │  │PostgreSQL│  │  Redis    │             │
                    │  │   15     │  │  Cache    │             │
                    │  │ (shared) │  │  + Queue  │             │
                    │  └──────────┘  └──────────┘             │
                    │                                          │
                    └──────────────────────────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────┐
                    │         SERVICIOS EXTERNOS               │
                    │                                          │
                    │  Stripe ── Suscripciones SaaS             │
                    │  Payment Gateway ── Payments de customers (MX)       │
                    │  Envia.com ── Cotizacion + Guias envio   │
                    │  Twilio ── SMS / Messaging                │
                    │  S3/R2 ── Storage (fotos, PDFs)          │
                    └──────────────────────────────────────────┘
```

---

## 2. Stack Tecnico

### Backend

| Componente | Tecnologia | Justificacion |
|-----------|------------|---------------|
| Framework | **Laravel 12** | Ya implementado en MVP. Ecosistema maduro, Eloquent, Filament. |
| PHP | **8.5** | Requerido por Laravel 12. Tipado fuerte, performance. |
| Admin Panel | **Filament 3.3** | Ya implementado. Multi-tenant ready con filament-tenancy. |
| Auth | **Laravel Auth + Sanctum** | Session auth para web, token auth para API. |
| Multi-tenancy | **Custom (trait + scope)** | Mas simple que paquetes (stancl/tenancy). Control total. |
| RBAC | **Spatie Permission** | Standard de la industria para Laravel. Integra con Filament Shield. |
| Queues | **Laravel Queue (Redis)** | Emails, PDFs, webhooks en background. |
| Cache | **Redis** | Session, cache, queue — todo en uno. |

### Frontend

| Componente | Tecnologia | Justificacion |
|-----------|------------|---------------|
| Rendering | **Blade + Livewire 4** | Ya implementado. SSR, sin build step, reactivo. |
| Interactividad | **Alpine.js 3** | Incluido con Livewire. Modals, dropdowns, toggles. |
| CSS | **Tailwind CSS 4** | Ya implementado. Utility-first, consistente. |
| Admin UI | **Filament UI** | Componentes pre-construidos. Forms, Tables, Widgets. |
| PDF | **DomPDF** | Ya implementado. Templates Blade → PDF inline. |

### Base de Datos

| Componente | Tecnologia | Justificacion |
|-----------|------------|---------------|
| RDBMS | **PostgreSQL 15** | Ya implementado. Indices, JSON, full-text search. |
| Model | **Single DB + tenant_id** | Simple, barato, suficiente para 1000 tenants. |
| Migrations | **Laravel Migrations** | Versionadas, reversibles, seeders idempotentes. |
| Indices | **B-tree en tenant_id** | Cada table con datos de negocio tiene index en tenant_id. |

### Infraestructura

| Componente | Tecnologia | Justificacion |
|-----------|------------|---------------|
| Hosting | **Railway** | Ya implementado. Docker deploy, PostgreSQL, Redis, auto-scale. |
| CDN / WAF | **Cloudflare** | Free tier suficiente. Cache estaticos, proteccion DDoS, SSL. |
| Storage | **Cloudflare R2** (o S3) | Fotos de items, PDFs, attachments. S3-compatible. |
| CI/CD | **GitHub Actions** | Push a main → deploy a Railway. Tests + PHPStan en PR. |
| Monitoring | **Laravel Telescope** (dev) + **Sentry** (prod) | Debugging local + error tracking operations. |

---

## 3. Auth SaaS

### Flujo de autenticacion

```
Registration:
  1. POST /register → crear User + Tenant + tenant_user(role:owner)
  2. Email de verificacion (optional para MVP)
  3. Redirect a /onboarding

Login:
  1. POST /login → check credenciales
  2. Cargar tenants del user (via tenant_user)
  3. Si 1 tenant → auto-set en session
  4. Si N tenants → mostrar selector
  5. Session: user_id + tenant_id + role

Cada request:
  1. Middleware `auth` → check session
  2. Middleware `tenant` → extraer tenant_id de session
  3. Global Scope → filtrar datos por tenant_id automaticamente
```

### Customer Auth (Portal)

```
El portal del customer es por tenant:
  - Customer se autentica con telefono + password
  - El customer pertenece a un tenant (customer.tenant_id)
  - Solo ve orders de su tenant
  - Guard separado: auth:customer (ya implementado)
```

### API Auth (Futuro)

```
Para integraciones (Shopify, custom):
  - Laravel Sanctum tokens
  - Token tiene tenant_id asociado
  - Rate limiting por tenant + plan
  - Scope de permisos por token
```

---

## 4. Billing (Stripe)

### Por que Stripe y no Payment Gateway para billing SaaS

| Criterio | Stripe | Payment Gateway |
|----------|--------|---------|
| Suscripciones recurrentes | Nativo, robusto | Limitado |
| Customer portal | Si (billing.stripe.com) | No |
| Webhooks de suscripcion | Completos | Basicos |
| Laravel integration | Laravel Cashier (oficial) | Manual |
| Multi-moneda | Si | Solo USD |
| Pricing tables | Si | No |
| Facturacion automatica | Si | No |

**Note:** Payment Gateway se sigue usando para cobros de customers de las businesss (payments de orders). Stripe es para cobrar la suscripcion SaaS.

### Payment Gateway Multi-Tenant

Cada business que use links de payment necesita su propia cuenta Payment Gateway:

```
Implementacion:
  - Almacenar credenciales Payment Gateway por tenant en tenants.settings (JSON):
    { "payment_gateway_public_key": "key_...", "payment_gateway_private_key": "key_..." }
  - Las credenciales se encriptan en BD (Laravel encrypted cast)
  - Al generar link de payment, se usan las keys del tenant activo
  - Onboarding: paso optional "Configura payments en linea" con link a Payment Gateway
  - Imprentas sin Payment Gateway configurado: el boton de link de payment no aparece
  - Feature disponible desde plan Growth
```

### Integracion con Laravel Cashier

```
Flujo:
  1. Tenant Owner selecciona plan en /billing
  2. Redirect a Stripe Checkout
  3. Webhook subscription.created → activar tenant
  4. Webhook invoice.paid → renovar suscripcion
  5. Webhook subscription.deleted → grace period → suspender tenant

Models:
  - Tenant hasOne Subscription (via Cashier)
  - Plan limits se verifican via middleware
```

### Plan Enforcement

```
Middleware `CheckPlanLimits`:
  - Antes de crear order → check orders_count < plan.max_orders
  - Antes de crear user → check users_count < plan.max_users
  - Antes de crear item → check products_count < plan.max_products
  - Si excede → mostrar upgrade modal (no bloquear brutalmente)
```

---

## 5. Observabilidad

### Logging

| Que | Donde | Retencion |
|-----|-------|-----------|
| Application logs | Railway logs + Sentry | 30 dias Railway, 90 dias Sentry |
| Error tracking | Sentry (exceptions) | 90 dias |
| Audit trail | Table `activity_log` (tenant-scoped) | Indefinido |
| Billing events | Stripe Dashboard + webhooks | Indefinido |

### Metricas de aplicacion

| Metrica | Como se mide | Alerta |
|---------|-------------|--------|
| Response time p95 | Laravel middleware timer | > 2s |
| Error rate | Sentry | > 1% en 5 min |
| Queue depth | Redis LLEN | > 100 jobs |
| DB connections | PostgreSQL pg_stat | > 80% max |
| Storage usage | S3/R2 metrics | > 80% plan limit |

### Audit Trail (por tenant)

```
Table activity_log (Spatie Activitylog):
  - tenant_id (agregado via migracion custom)
  - log_name
  - description
  - subject_type (Order, Product, Customer, etc.)
  - subject_id
  - causer_type (User)
  - causer_id
  - properties (JSON — contiene old/new values)
  - event (created, updated, deleted, etc.)
  - created_at

Paquete: spatie/laravel-activitylog (ya probado con Laravel)
```

---

## 6. Security

### Capas de Security

| Capa | Implementacion | Estado |
|------|---------------|--------|
| **Transporte** | HTTPS everywhere (Cloudflare SSL) | Implementado |
| **WAF** | Cloudflare rules (SQL injection, XSS) | Por implementar |
| **Autenticacion** | Laravel Auth + bcrypt + session | Implementado |
| **Autorizacion** | RBAC (Spatie Permission) | Por implementar |
| **Tenant isolation** | Global Scopes + middleware | Por implementar |
| **Input validation** | Form Requests + Laravel validation | Parcial (inline en wizard) |
| **CSRF** | Laravel CSRF tokens | Implementado |
| **XSS** | Blade auto-escaping {{ }} | Implementado |
| **SQL Injection** | Eloquent parameterized queries | Implementado |
| **Rate limiting** | Laravel throttle middleware | Parcial |
| **Secrets** | Environment variables (Railway) | Implementado |
| **Backups** | PostgreSQL automated backups | Railway managed |

### Security SaaS adicional

```
Por implementar:
  - Rate limiting por tenant (no solo por IP)
  - API token rotation
  - Audit log de accesos admin
  - 2FA para owners (futuro)
  - Data export para GDPR/privacy
  - Tenant data deletion (cuando cancela)
```

---

## 7. Super-Admin Panel

```
Panel exclusivo para el equipo SaaS Template (no visible para tenants).
Implementacion: Filament panel separado (/super-admin) con guard propio.

Acceso: Solo users con flag is_super_admin = true en table users.
  - NO usa el mismo sistema de roles/permisos de tenants
  - Middleware dedicado: EnsureSuperAdmin

Features:
  - Dashboard SaaS: MRR, ARR, churn, NRR, trials activos
  - Lista de tenants: filtrar por plan, status, health score
  - Detalle de tenant: metricas, suscripcion, users, logs
  - Suspender/reactivar tenants manualmente
  - Impersonar tenant (ver su panel como si fuera el owner)
  - Logs de actividad cross-tenant
  - Feature usage analytics
```

---

## 8. Escalabilidad

### Actual (MVP) → SaaS Targets

| Dimension | MVP Actual | SaaS Target (Ano 1) | SaaS Target (Ano 3) |
|-----------|-----------|---------------------|---------------------|
| Tenants | 1 | 100 | 1,000 |
| Users concurrentes | 2-3 | 200-300 | 2,000-3,000 |
| Orders totales | ~500 | 50,000 | 500,000 |
| Storage | ~1 GB | ~50 GB | ~500 GB |
| DB size | ~50 MB | ~5 GB | ~50 GB |

### Estrategia de escalado

```
Fase 1 (0-100 tenants):
  - Railway: 1 worker, 1 GB RAM, PostgreSQL shared
  - Redis: cache + sessions + queue
  - No se necesita nada mas

Fase 2 (100-500 tenants):
  - Railway: 2 workers, 2 GB RAM, PostgreSQL dedicated
  - Queue workers separados (PDFs, emails, webhooks)
  - R2 storage para files
  - CDN para assets estaticos

Fase 3 (500-1000 tenants):
  - Railway: auto-scale, PostgreSQL read replica
  - Redis Cluster para cache distribuido
  - Background jobs para reportes pesados
  - Considerar: migrar a AWS/GCP si Railway limita
```

### Bottlenecks esperados y soluciones

| Bottleneck | Cuando | Solucion |
|-----------|--------|----------|
| DB queries lentas | >10K orders por tenant | Indices compuestos (tenant_id, status), pagination |
| PDF generation lenta | >50 PDFs/hora | Queue worker dedicado, generar async |
| File storage | >10 GB | Migrar a R2/S3, lazy loading de imagenes |
| Session storage | >500 concurrent users | Redis sessions (ya configurado) |
| Email sending | >1000 emails/dia | Queue + proveedor dedicado (Postmark/SES) |

---

## 9. Diagrama de Datos Multi-Tenant

```
tenants ──────────────────────────────────────────────────┐
  │                                                        │
  ├── tenant_user ──── users (global)                     │
  │     └── role                                           │
  │                                                        │
  ├── branches                                             │
  ├── products ──── item_categories                        │
  │     └── product_sizes ──── sizes                      │
  ├── colors                                               │
  ├── pricing_rules                                        │
  ├── dimension_limits                                     │
  ├── business_configs                                     │
  │                                                        │
  ├── customers                                            │
  │     └── customer_addresses                             │
  │                                                        │
  ├── orders                                               │
  │     ├── order_lines ──── products                      │
  │     └── payments                                       │
  │                                                        │
  ├── quotes                                               │
  │                                                        │
  └── activity_log                                           │
                                                           │
subscriptions ──── tenants (Stripe Cashier)                │
plans (global) ────────────────────────────────────────────┘
```

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-17*
