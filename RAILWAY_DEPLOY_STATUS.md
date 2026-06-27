# Railway Deploy - Status Template

> **Project:** saas-template
> **Branch:** main
> **Status:** TEMPLATE — Configure for your deployment

---

## Services on Railway

| Service | Status | Detail |
|---------|--------|--------|
| saas-template | Pending | Laravel app, healthcheck `/up` |
| Postgres | Pending | PostgreSQL with postgres-volume |

### URLs

| URL | Status |
|-----|--------|
| https://your-app.up.railway.app/ | Pending |
| https://your-app.up.railway.app/up | Pending |
| https://your-app.up.railway.app/admin/login | Pending |

### Completed Components

| Component | Status | Detail |
|-----------|--------|--------|
| Dockerfile | Done | Multi-stage: `node:20-alpine` + `php:8.4-cli-alpine` (~301MB) |
| railway.json | Done | Builder DOCKERFILE, healthcheck `/up`, restart on failure |
| docker-entrypoint.sh | Done | config:clear, migrate, db:seed, storage:link, cache, serve |
| .dockerignore | Done | Excludes .env, tests, docs, node_modules, vendor |
| DATABASE_URL | Done | `${{Postgres.DATABASE_URL}}` as service reference |
| Trusted Proxies | Done | `trustProxies(at: '*')` in bootstrap/app.php |
| FilamentUser | Done | `canAccessPanel()` with `config('app.admin_emails')` |
| Admin User | Done | Migration `create_admin_user` with env vars |
| Seeders | Done | Idempotent with `firstOrCreate`, run on every deploy |
| Assets CSS/JS | Done | Vite build in Stage 1, HTTPS via trusted proxies |

---

## Environment Variables on Railway

### Required Variables

| Variable | Value | Notes |
|----------|-------|-------|
| APP_KEY | base64:... | Generate with `php artisan key:generate --show` |
| APP_ENV | production | |
| APP_DEBUG | false | |
| APP_URL | https://your-app.up.railway.app | |
| APP_NAME | SaaS Template | |
| DB_CONNECTION | pgsql | Default is sqlite |
| DATABASE_URL | ${{Postgres.DATABASE_URL}} | Reference to Postgres service |
| SESSION_DRIVER | database | |
| CACHE_STORE | database | |
| QUEUE_CONNECTION | database | |
| ADMIN_EMAIL | admin@example.com | Used by create_admin_user migration |
| ADMIN_PASSWORD | *** | Used by migration (can be removed after) |
| ADMIN_EMAILS | admin@example.com | Used by canAccessPanel() in User model |

### Automatic Railway Variables

| Variable | Value |
|----------|-------|
| PORT | 8080 |
| RAILWAY_ENVIRONMENT | production |
| RAILWAY_PUBLIC_DOMAIN | your-app.up.railway.app |
| RAILWAY_PRIVATE_DOMAIN | your-app.railway.internal |

---

## Entrypoint (docker-entrypoint.sh)

Execution order on each deploy:

```
1. config:clear       → Clear old cache (so env() works)
2. migrate --force    → Pending migrations
3. db:seed --force    → Seed data (idempotent, no duplicates)
4. storage:link       → Symlink (if not exists)
5. config:cache       → Cache config with current env vars
6. route:cache        → Cache routes
7. view:cache         → Cache views
8. serve 0.0.0.0:$PORT → Start server
```

---

## Lessons Learned

1. **`env()` vs `config()`**: With `config:cache` active, `env()` returns null outside config/*.php
2. **Double hash**: The cast `'password' => 'hashed'` hashes automatically. Don't use bcrypt() when creating via model
3. **Trusted Proxies**: Required on Railway for Vite to generate https:// URLs
4. **`config:clear` before migrate**: Without this, the cache from the previous deploy persists and env() fails
5. **ADMIN_EMAILS vs ADMIN_EMAIL**: Different variables. ADMIN_EMAIL for migration, ADMIN_EMAILS for canAccessPanel()

---

## Security Variables (Set on Railway)

| Variable | Value | Notes |
|----------|-------|-------|
| SESSION_ENCRYPT | true | Encrypt session data in DB |
| SESSION_SECURE_COOKIE | true | Cookies only via HTTPS |
| SENTRY_LARAVEL_DSN | (create at sentry.io) | Error tracking with tenant context |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 | 10% of traces for performance monitoring |

## Monitoring

### Health Check
- **Endpoint:** `GET /api/health`
- **Healthy response:** `{"status": "healthy", "services": {"database": "ok", "cache": "ok", "storage": "ok"}}`
- **Degraded response:** HTTP 503, `{"status": "degraded", ...}`

### UptimeRobot (Recommended)
1. Create free account at [UptimeRobot](https://uptimerobot.com/)
2. Add HTTP(S) monitor: `https://your-app.up.railway.app/api/health`
3. Interval: 5 minutes
4. Alerts: email (and Slack if available)

---

## Documentation

See step-by-step guide at: `docs/guides/12-railway-deployment.md`

---

*Last updated: 2026-03-11*
