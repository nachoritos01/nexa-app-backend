# Test Plan — SaaS Fase 9 PR1: Performance + Infraestructura (9.1-9.2)

**Version:** 1.0
**Date:** 2026-02-23
**Feature:** Query optimization, caching, queue worker, CI/CD, Redis/R2 prep, health check
**Branch:** feature/saas-phase9-optimization-pr1

## 1. Objetivo

Validar que las optimizaciones de performance (fix N+1, caching layer), la configuracion de infraestructura (queue worker, CI/CD, Redis/R2 prep), y el health check endpoint funcionan correctamente sin regresiones.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| N+1 Fix | MonthlyReport historical_total en query unico |
| Tenant::usageCounts() | Query optimizado con subqueries + cache (5 min) |
| CacheInvalidationObserver | Invalida cache en CUD de Order, Customer, Product, Branch, Payment |
| StatsOverview cache | Dashboard stats cacheados por 60s por tenant |
| config/saas.php | Seccion `cache` con TTLs configurables |
| Queue worker | docker-entrypoint.sh con queue:work background |
| CI/CD | GitHub Actions workflow (lint + test + analyse) |
| Dependabot | Composer, npm, github-actions weekly updates |
| Redis prep | phpredis en Dockerfile, config acepta REDIS_URL |
| R2 storage | flysystem-aws-s3-v3 instalado, .env.example documentado |
| Health check | GET /api/health con verificacion DB, cache, storage |

### Fuera de alcance

- Security headers y encrypted settings (PR2)
- Sentry integration (PR2)
- Redis/R2 deployment (requiere services en Railway/Cloudflare)

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (composer test) | Health check endpoint |
| Unit tests | PHPUnit | Cache invalidation, usage counts |
| Regresion | PHPUnit suite completa | 244 tests existentes |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase9-optimization-pr1 creado desde develop
- [x] MonthlyReport N+1 corregido con query agrupado
- [x] Tenant::usageCounts() optimizado y cacheado
- [x] CacheInvalidationObserver creado y registrado
- [x] StatsOverview usa Cache::remember()
- [x] docker-entrypoint.sh incluye queue worker
- [x] CI workflow y dependabot configurados
- [x] phpredis en Dockerfile
- [x] flysystem-aws-s3-v3 instalado
- [x] HealthController implementado
- [x] Tests creados (7 tests en 2 files)

## 5. Criterios de salida

- [x] 251/251 tests pasan (634 assertions)
- [x] Sin regresiones en tests existentes
- [x] Health check retorna JSON correcto
- [x] Cache se invalida al crear/actualizar/eliminar models

## 6. Datos de test

### Health Check

| Escenario | Resultado esperado |
|-----------|-------------------|
| GET /api/health con services OK | 200 + status: healthy |
| Sin autenticacion | 200 (endpoint publico) |
| Estructura JSON | services: {database, cache, storage}, timestamp |

### Cache Invalidation

| Escenario | Resultado esperado |
|-----------|-------------------|
| Crear orden | Cache usage_counts y dashboard_stats invalidado |
| Consultar usageCounts con cache | Retorna datos cacheados |
| Cache miss | Regenera datos y los cachea |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Cache stale en alta concurrencia | Baja | Bajo | TTL cortos (60s stats, 300s usage) |
| Queue worker crash en Railway | Baja | Medio | --max-time=3600 fuerza reinicio, entrypoint lo relanza |
| Redis no disponible en prod | N/A | Ninguno | Database driver como default, Redis solo cuando se configure |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase9-optimization-pr1/test-plan.md` |
| Casos de test | `docs/qa/fase9-optimization-pr1/test-cases.md` |
| Resumen QA | `docs/qa/fase9-optimization-pr1/test-summary.md` |
