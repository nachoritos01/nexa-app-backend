# Deploy Strategy: SaaS Template SaaS

> Como evolucionar el deploy actual de Railway a una infraestructura SaaS.

---

## 1. Estado Actual del Deploy

```
Environment unico (operations):
  Railway
  ├── Laravel App (Docker, 1 worker)
  ├── PostgreSQL 15 (managed)
  └── Sin Redis, sin queue workers, sin staging

CI/CD: Manual (push → Railway auto-deploy desde branch)
Dominio: your-app.up.railway.app
SSL: Railway managed
Storage: Local (storage/app/public)
```

### Limitaciones actuales para SaaS

| Problema | Impacto | Solucion |
|----------|---------|----------|
| Sin ambiente de staging | No se puede probar sin afectar operations | Crear staging en Railway |
| Storage local | Se pierde en cada deploy (ephemeral) | Migrar a S3/R2 |
| Sin Redis | No hay cache, queues, ni sessions rapidas | Agregar Redis en Railway |
| Sin queue worker | PDFs, emails, webhooks bloquean el request | Worker dedicado |
| Deploy manual | Sin tests automaticos pre-deploy | GitHub Actions CI/CD |
| Sin monitoring | Errores se descubren cuando el user reporta | Sentry + health checks |

---

## 2. Arquitectura Target

```
                    ┌─────────────────────────────────┐
                    │           CLOUDFLARE              │
                    │    DNS + SSL + CDN + WAF          │
                    │                                   │
                    │    app.saas-template.mx  ──────┐     │
                    │    saas-template.mx (landing)──┤     │
                    └───────────────────────────┼─────┘
                                                │
    ┌───────────────────────────────────────────▼──────────┐
    │                    RAILWAY                            │
    │                                                       │
    │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │
    │  │  Web App     │  │  Worker      │  │  Scheduler   │  │
    │  │  (Laravel)   │  │  (Queue)     │  │  (Cron)      │  │
    │  │  Port 8080   │  │  horizon     │  │  schedule:run │  │
    │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  │
    │         │                 │                  │          │
    │  ┌──────▼─────────────────▼──────────────────▼───────┐ │
    │  │              Shared Services                       │ │
    │  │  ┌──────────────┐  ┌──────────────┐               │ │
    │  │  │ PostgreSQL   │  │    Redis      │               │ │
    │  │  │ (Dedicated)  │  │  (Cache +     │               │ │
    │  │  │              │  │   Queue +     │               │ │
    │  │  │              │  │   Sessions)   │               │ │
    │  │  └──────────────┘  └──────────────┘               │ │
    │  └────────────────────────────────────────────────────┘ │
    │                                                         │
    │  STAGING (mismo layout, escala menor)                   │
    │  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  │
    │  │ Web Staging  │  │ PG Staging   │  │ Redis Staging│  │
    │  └─────────────┘  └──────────────┘  └──────────────┘  │
    └─────────────────────────────────────────────────────────┘
                         │
    ┌────────────────────▼────────────────────┐
    │          EXTERNAL STORAGE                │
    │   Cloudflare R2 (S3-compatible)         │
    │   Fotos, PDFs, attachments, logos       │
    └─────────────────────────────────────────┘
```

---

## 3. Ambientes

### Production

| Componente | Config | Costo estimado |
|-----------|--------|:-------------:|
| Web App | 1 vCPU, 1 GB RAM, auto-sleep off | ~$10 USD/mes |
| Queue Worker | 0.5 vCPU, 512 MB RAM | ~$5 USD/mes |
| Scheduler | 0.5 vCPU, 256 MB RAM (cron) | ~$3 USD/mes |
| PostgreSQL | Dedicated, 1 GB RAM, 10 GB storage | ~$10 USD/mes |
| Redis | 256 MB | ~$5 USD/mes |
| Cloudflare R2 | 10 GB included free | $0 |
| Cloudflare (DNS/CDN) | Free tier | $0 |
| Sentry | Free tier (5K events/mes) | $0 |
| **Total** | | **~$33 USD/mes** |

### Staging

| Componente | Config | Costo estimado |
|-----------|--------|:-------------:|
| Web App | 0.5 vCPU, 512 MB, auto-sleep on | ~$3 USD/mes |
| PostgreSQL | Shared, 512 MB | ~$5 USD/mes |
| Redis | 128 MB | ~$3 USD/mes |
| **Total** | | **~$11 USD/mes** |

### Costo total infraestructura

```
Fase 1 (0-100 tenants):     ~$44 USD/mes ($880 USD)
Fase 2 (100-500 tenants):   ~$80 USD/mes (scale up)
Fase 3 (500-1000 tenants):  ~$150 USD/mes (dedicated DB, replicas)
```

**Note:** Con 10 customers pagando $499 USD/mes promedio ($4,990 MRR), la infra se paga sola con <20% del MRR.

---

## 4. CI/CD Pipeline

### GitHub Actions Workflow

```
Trigger: Push a `develop` → deploy staging
         Push a `main` → deploy operations
         Pull Request → solo tests (no deploy)

Pipeline:
  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
  │  Lint    │───▶│  Test    │───▶│  Analyse │───▶│  Deploy  │
  │  (Pint)  │    │ (PHPUnit)│    │ (PHPStan)│    │(Railway) │
  └──────────┘    └──────────┘    └──────────┘    └──────────┘
       │               │               │               │
    composer         composer         composer       railway
     format            test           analyse         deploy
```

### File: `.github/workflows/ci.yml`

```yaml
Concepto (no codigo final):

jobs:
  lint:
    - composer format -- --dry-run (check formatting)

  test:
    - services: postgres, redis
    - composer test (PHPUnit)
    - Requiere: .env.testing con DB test

  analyse:
    - composer analyse (PHPStan level 5)

  deploy-staging:
    - needs: [lint, test, analyse]
    - if: github.ref == 'refs/heads/develop'
    - railway deploy --service staging

  deploy-production:
    - needs: [lint, test, analyse]
    - if: github.ref == 'refs/heads/main'
    - railway deploy --service production
```

### Reglas de Branch Protection

```
main (operations):
  - Require PR with 1 approval
  - Require CI passing (lint + test + analyse)
  - No force push
  - No direct commits

develop (staging):
  - Require CI passing
  - Allow direct commits (for hotfixes)
  - Auto-deploy on push
```

---

## 5. Deploy Process

### Deploy a Staging

```
1. Developer pushes to `develop`
2. GitHub Actions: lint → test → analyse → deploy
3. Railway builds Docker image
4. Railway runs entrypoint:
   config:clear → migrate → db:seed → storage:link → config:cache → route:cache → view:cache → serve
5. Staging disponible en staging.saas-template.mx
6. QA manual + smoke tests
```

### Deploy a Operations

```
1. PR de develop → main (con CI passing)
2. Merge to main
3. GitHub Actions: lint → test → analyse → deploy
4. Railway builds Docker image
5. Zero-downtime deploy (Railway rolling update)
6. Migrations corren automaticamente
7. Check health check: GET /api → 200
8. Monitor Sentry por 30 min
```

### Rollback

```
Si algo falla post-deploy:

Opcion A — Rollback en Railway:
  - Railway Dashboard → Deployments → click "Redeploy" en version anterior
  - Toma ~1 minuto

Opcion B — Git revert:
  - git revert HEAD
  - Push a main
  - Pipeline normal

NUNCA:
  - git push --force a main
  - Modificar DB directamente en operations
  - Correr migrations destructivas sin backup previo
```

---

## 6. Migrations de Base de Datos

### Reglas para Migrations SaaS

```
1. NUNCA hacer DROP COLUMN/TABLE en operations sin backup
2. SIEMPRE hacer migrations aditivas primero (ADD COLUMN nullable)
3. SIEMPRE separar en pasos:
   a. Agregar column (nullable)
   b. Backfill datos
   c. Agregar constraint (NOT NULL)
   d. Eliminar column vieja (en siguiente deploy)
4. SIEMPRE probar migracion en staging primero
5. Backups automaticos antes de cada migrate
```

### Proceso de Migracion Segura

```
Deploy N:
  - ADD COLUMN tenant_id bigint nullable
  - Codigo sigue funcionando sin tenant_id

Deploy N+1:
  - Backfill: UPDATE table SET tenant_id = 1 WHERE tenant_id IS NULL
  - Codigo empieza a leer tenant_id

Deploy N+2:
  - ALTER COLUMN tenant_id SET NOT NULL
  - ADD CONSTRAINT FK
  - Codigo requiere tenant_id
```

---

## 7. Storage Strategy

### Migracion de Storage Local a R2

```
Actual:
  - Fotos y PDFs en storage/app/public/
  - Ephemeral en Railway (se pierde en redeploy)
  - Acceso: Storage::url() → /storage/filename

Target:
  - Cloudflare R2 (S3-compatible)
  - Persistente, CDN global
  - Acceso: Storage::url() → https://cdn.saas-template.mx/filename

Steps:
  1. Instalar league/flysystem-aws-s3-v3
  2. Configurar .env: FILESYSTEM_DISK=r2
  3. Configurar config/filesystems.php con driver 's3' apuntando a R2
  4. Migrar files existentes con script
  5. Check que Storage::url() genera URLs de R2
```

### Estructura de Storage por Tenant

```
r2-bucket/
├── tenants/
│   ├── 1/                     (tenant_id)
│   │   ├── products/          fotos de items
│   │   ├── orders/            PDFs de orders
│   │   ├── quotes/            PDFs de quotes
│   │   ├── designs/           imagenes de disenos
│   │   ├── attachments/       files adjuntos
│   │   └── branding/          logo, favicon
│   ├── 2/
│   │   └── ...
│   └── N/
└── system/                    assets globales de SaaS Template
```

---

## 8. Monitoring y Alertas

### Health Checks

```
Endpoint: GET /api/health

Response:
{
  "status": "ok",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "storage": "ok",
    "queue": "ok"
  },
  "version": "1.2.3",
  "timestamp": "2026-02-16T12:00:00Z"
}

Railway: configurar health check en este endpoint
Uptime: UptimeRobot (free) pinging /api/health cada 5 min
```

### Error Tracking (Sentry)

```
Configuracion:
  - composer require sentry/sentry-laravel
  - SENTRY_LARAVEL_DSN en .env
  - Context: tenant_id, user_id, plan en cada error
  - Alertas: Slack/email si >5 errors en 5 min

Lo que se trackea:
  - Exceptions no manejadas
  - 500 errors
  - Queue job failures
  - Slow queries (>2s)
  - Memory warnings
```

### Metricas de Infra

```
Railway Dashboard (built-in):
  - CPU usage
  - Memory usage
  - Network traffic
  - Deploy history

PostgreSQL:
  - Active connections
  - Query performance (pg_stat_statements)
  - Storage usage
  - Backup status

Redis:
  - Memory usage
  - Connected clients
  - Queue depth (LLEN)
```

---

## 9. Domains y DNS

### Estructura de Dominios

```
saas-template.mx                    Landing page / marketing
app.saas-template.mx                Aplicacion SaaS (Filament + portal)
staging.saas-template.mx            Ambiente de staging
api.saas-template.mx                REST API (futuro, Fase 3)
cdn.saas-template.mx                Storage R2 (Cloudflare)

Futuro (Fase 3):
{tenant}.saas-template.mx           Vanity subdomains por tenant
```

### Configuracion DNS (Cloudflare)

```
Tipo    Name              Destino                         Proxy
A       saas-template.mx        Railway IP                      Si
CNAME   app                 production.up.railway.app       Si
CNAME   staging             staging.up.railway.app          Si
CNAME   cdn                 R2 bucket URL                   Si
```

### SSL

- Cloudflare gestiona SSL automaticamente (Universal SSL)
- Railway tambien genera SSL, pero Cloudflare lo intercepta (Full Strict)
- No se necesita gestionar certificados manualmente

---

## 10. Backups

### Base de Datos

```
Railway PostgreSQL (automatico):
  - Backups diarios (retencion 7 dias en plan Hobby, 30 en Pro)
  - Point-in-time recovery

Script manual (semanal):
  - pg_dump via railway exec
  - Almacenar en R2 bucket separado
  - Retener 4 backups semanales + 3 mensuales

Pre-deploy:
  - Backup automatico antes de cada migracion
  - Script en entrypoint: pg_dump → R2 → migrate
```

### Storage (R2)

```
R2 es durable por defecto (11 nines).
No se necesitan backups adicionales del storage.
```

### Disaster Recovery

```
RPO (Recovery Point Objective): <24 horas (backup diario)
RTO (Recovery Time Objective): <1 hora

Proceso:
  1. Railway: redeploy ultimo build exitoso (~2 min)
  2. DB: restaurar backup desde Railway Dashboard (~5 min)
  3. Check: health check + smoke test (~5 min)
```

---

## 11. Checklist de Migracion MVP → SaaS Deploy

```
Pre-requisitos:
  [ ] Comprar dominio saas-template.mx
  [ ] Crear cuenta Cloudflare
  [ ] Crear cuenta Sentry
  [ ] Crear cuenta Stripe (modo test)
  [ ] Crear bucket R2 en Cloudflare

Railway Setup:
  [ ] Crear proyecto "SaaS Template" en Railway
  [ ] Crear service Web (production)
  [ ] Crear service Web (staging)
  [ ] Crear PostgreSQL (production, dedicated)
  [ ] Crear PostgreSQL (staging, shared)
  [ ] Crear Redis (production)
  [ ] Crear Redis (staging)
  [ ] Crear service Worker (queue)
  [ ] Crear service Scheduler (cron)

DNS/Domains:
  [ ] Configurar DNS en Cloudflare
  [ ] Apuntar app.saas-template.mx → Railway production
  [ ] Apuntar staging.saas-template.mx → Railway staging
  [ ] Configurar SSL (Full Strict en Cloudflare)
  [ ] Check HTTPS funciona

CI/CD:
  [ ] Crear .github/workflows/ci.yml
  [ ] Configurar secrets: RAILWAY_TOKEN, SENTRY_DSN
  [ ] Configurar branch protection en main
  [ ] Check pipeline: PR → tests → merge → deploy

Storage:
  [ ] Configurar R2 como filesystem default
  [ ] Migrar files existentes a R2
  [ ] Check Storage::url() genera URLs de CDN

Monitoring:
  [ ] Configurar Sentry en Laravel
  [ ] Configurar health check endpoint
  [ ] Configurar UptimeRobot
  [ ] Check alertas funcionan

Verificacion:
  [ ] Deploy completo en staging funciona
  [ ] Migrations corren sin error
  [ ] SaaS Template (tenant #1) funciona en nuevo setup
  [ ] Health check responde OK
  [ ] Errores llegan a Sentry
  [ ] PDFs se generan correctamente
  [ ] Storage R2 funciona (fotos, uploads)
```

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-16*
