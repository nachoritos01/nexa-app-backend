# Test Cases — SaaS Fase 9 PR1: Performance + Infraestructura (9.1-9.2)

**Version:** 1.0
**Date:** 2026-02-23
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-OPT-{numero} (OPT = Optimization)
- **Priority:** P1 / P2 / P3
- **Tipo:** Funcional / Negativo / Regresion / Performance / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 9.1.1 — Query Optimization

### TC-OPT-001: MonthlyReport no ejecuta N+1 para historical_total

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-001 |
| **Prioridad** | P1 |
| **Tipo** | Performance |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | Widget MonthlyReport con topCustomers |
| **Steps** | 1. Check que historical_total se obtiene con un solo query groupBy<br>2. Check que no hay bucle con queries individuales |
| **Resultado esperado** | Una sola query SELECT...GROUP BY para historical_totals |
| **Estado** | Pasado |

### TC-OPT-002: Tenant::usageCounts() usa subqueries combinados

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-002 |
| **Prioridad** | P1 |
| **Tipo** | Performance |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | Tenant con datos |
| **Steps** | 1. Check que users/branches/products/customers se obtienen en 1 query<br>2. Check que orders se cuenta por separado (filtro mes/anio) |
| **Resultado esperado** | 2 queries en lugar de 5 originales |
| **Estado** | Pasado |

---

## 9.1.2 — Caching Layer

### TC-OPT-003: Crear orden invalida cache de usage counts

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-003 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CacheInvalidationTest::test_creating_order_invalidates_usage_cache`) |
| **Precondiciones** | Cache con datos previos |
| **Steps** | 1. Setear cache usage_counts y dashboard_stats<br>2. Crear orden<br>3. Check que ambas cache keys se eliminaron |
| **Resultado esperado** | Cache::get() retorna null para ambas keys |
| **Estado** | Pasado |

### TC-OPT-004: usageCounts retorna datos cacheados

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CacheInvalidationTest::test_usage_counts_uses_cache_when_available`) |
| **Precondiciones** | Cache pre-poblado con datos conocidos |
| **Steps** | 1. Setear cache con valores especificos<br>2. Llamar usageCounts()<br>3. Check que retorna valores del cache (no DB) |
| **Resultado esperado** | Datos del cache retornados sin queries |
| **Estado** | Pasado |

### TC-OPT-005: usageCounts regenera cache tras miss

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-005 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CacheInvalidationTest::test_usage_counts_regenerates_after_cache_miss`) |
| **Precondiciones** | Cache vacio |
| **Steps** | 1. Forget cache key<br>2. Llamar usageCounts()<br>3. Check estructura de response<br>4. Check que cache key ahora existe |
| **Resultado esperado** | Datos consultados de DB y almacenados en cache |
| **Estado** | Pasado |

### TC-OPT-006: config/saas.php tiene TTLs configurables

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-006 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Check config('saas.cache.usage_counts_ttl') == 300<br>2. Check config('saas.cache.dashboard_stats_ttl') == 60 |
| **Resultado esperado** | TTLs configurables desde config |
| **Estado** | Pasado |

---

## 9.1.3 — Queue Configuration

### TC-OPT-007: docker-entrypoint.sh inicia queue worker

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-007 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | QUEUE_CONNECTION=database |
| **Steps** | 1. Check que entrypoint tiene condicional QUEUE_CONNECTION != sync<br>2. Check flags: --tries=3 --timeout=60 --max-time=3600<br>3. Check que corre en background (&) |
| **Resultado esperado** | Worker inicia si queue no es sync |
| **Estado** | Pasado |

---

## 9.2.1 — CI/CD GitHub Actions

### TC-OPT-008: CI workflow tiene 3 jobs

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | .github/workflows/ci.yml existe |
| **Steps** | 1. Check job lint (PHP CS Fixer dry-run)<br>2. Check job analyse (PHPStan)<br>3. Check job test (PHPUnit con PostgreSQL service) |
| **Resultado esperado** | 3 jobs independientes: lint, analyse, test |
| **Estado** | Pasado |

### TC-OPT-009: Dependabot configurado

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-009 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | .github/dependabot.yml existe |
| **Steps** | 1. Check ecosistemas: composer, npm, github-actions<br>2. Check intervalo: weekly<br>3. Check labels por ecosistema |
| **Resultado esperado** | 3 ecosistemas configurados con updates semanales |
| **Estado** | Pasado |

---

## 9.2.2-9.2.3 — Redis + R2 Preparation

### TC-OPT-010: phpredis en Dockerfile

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-010 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | Dockerfile |
| **Steps** | 1. Check `pecl install redis` en Dockerfile<br>2. Check `docker-php-ext-enable redis` |
| **Resultado esperado** | Extension redis disponible en runtime |
| **Estado** | Pasado |

### TC-OPT-011: Redis config acepta REDIS_URL

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-011 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | config/database.php |
| **Steps** | 1. Check que redis.default.url lee REDIS_URL<br>2. Check que redis.cache.url lee REDIS_URL |
| **Resultado esperado** | Setear REDIS_URL es suficiente para conectar |
| **Estado** | Pasado |

### TC-OPT-012: flysystem-aws-s3-v3 instalado

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-012 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | composer.json |
| **Steps** | 1. Check league/flysystem-aws-s3-v3 en require<br>2. Check config/filesystems.php tiene disco s3<br>3. Check .env.example tiene variables R2 documentadas |
| **Resultado esperado** | Driver S3 listo para Cloudflare R2 |
| **Estado** | Pasado |

---

## 9.2.4 — Health Check Endpoint

### TC-OPT-013: Health check retorna 200 cuando healthy

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-013 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthCheckTest::test_health_check_returns_200_when_healthy`) |
| **Precondiciones** | DB y cache funcionando |
| **Steps** | 1. GET /api/health<br>2. Check HTTP 200<br>3. Check status: healthy |
| **Resultado esperado** | 200 con status healthy |
| **Estado** | Pasado |

### TC-OPT-014: Health check tiene estructura JSON correcta

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-014 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthCheckTest::test_health_check_returns_correct_json_structure`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /api/health<br>2. Check keys: status, services{database, cache, storage}, timestamp |
| **Resultado esperado** | Estructura JSON completa |
| **Estado** | Pasado |

### TC-OPT-015: Health check services OK

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-015 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthCheckTest::test_health_check_services_are_ok`) |
| **Precondiciones** | Todos los services funcionando |
| **Steps** | 1. GET /api/health<br>2. Check database: ok, cache: ok, storage: ok |
| **Resultado esperado** | Todos los services reportan ok |
| **Estado** | Pasado |

### TC-OPT-016: Health check no requiere autenticacion

| Campo | Valor |
|-------|-------|
| **ID** | TC-OPT-016 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`HealthCheckTest::test_health_check_does_not_require_authentication`) |
| **Precondiciones** | Sin session ni token |
| **Steps** | 1. GET /api/health sin Authorization header<br>2. Check HTTP 200 |
| **Resultado esperado** | Accesible sin autenticacion |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 16 |
| Total | **16** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 7 |
| Code review | 9 |
