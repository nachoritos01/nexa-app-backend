# Test Plan — SaaS Fase 8 PR2: Rate Limiting + Shopify Research (8.3-8.4)

**Version:** 1.0
**Date:** 2026-02-22
**Feature:** Rate limiting por plan de tenant, investigacion integracion Shopify, fix dark mode UI
**Branch:** feature/saas-phase8-integrations-pr2

## 1. Objetivo

Validar que el rate limiting de la API funciona por plan de tenant (starter:60, growth:120, pro:300 req/min), que los headers de rate limit se incluyen en las responses, y que las paginas de configuracion son legibles en dark mode.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Rate Limiting | Throttle por plan via RateLimiter::for('api-tenant') |
| Rate Limit Config | config/saas.php → api.rate_limits por plan |
| Rate Limit Headers | X-RateLimit-Limit, X-RateLimit-Remaining en responses |
| 429 Response | Too Many Requests cuando se excede el limite |
| Shopify Research | Documento de viabilidad + stub service |
| Dark Mode UI | Texto blanco en dark theme para 5 paginas settings |

### Fuera de alcance

- Integracion Shopify real (solo investigacion + stub)
- Rate limiting persistente (Redis) — usa in-memory

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (composer test) | Rate limiting, headers, 429 |
| Manual UI | Chrome DevTools MCP | Dark mode en 5 paginas settings |
| Regresion | PHPUnit suite completa | 244 tests existentes |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase8-integrations-pr2 creado desde PR1
- [x] RateLimiter::for('api-tenant') configurado en AppServiceProvider
- [x] config/saas.php con rate_limits por plan
- [x] throttle:api-tenant en middleware chain
- [x] ShopifyService stub creado
- [x] Tests creados (4 tests en 1 file)

## 5. Criterios de salida

- [x] 244/244 tests pasan (613 assertions)
- [x] Checklist manual completo (5/5 verificaciones dark mode)
- [x] Sin regresiones en tests existentes

## 6. Datos de test

### Rate Limiting

| Escenario | Resultado esperado |
|-----------|-------------------|
| Pro plan (300 req/min) | Header X-RateLimit-Limit: 300 |
| Request con token valido | Headers rate limit presentes |
| Sin token | 401 (no 429) |
| Exceder limite | 429 Too Many Requests |

### Dark Mode UI

| Pagina | Verificacion |
|--------|-------------|
| /admin/billing | Texto blanco en plan info, uso, planes, referidos |
| /admin/whats-app-settings | Texto blanco en labels, toggle corregido |
| /admin/tenant-settings | Texto blanco en labels de formulario |
| /admin/api-settings | Texto blanco en tokens, referencia rapida |
| /admin/webhook-settings | Texto blanco, toggle switch corregido |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Rate limit in-memory no persiste entre procesos | Media | Bajo | Aceptable para MVP, Redis en operations |
| Dark mode text-white en todos lados puede reducir jerarquia visual | Baja | Bajo | Aceptable, user lo pidio explicitamente |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase8-integrations-pr2/test-plan.md` |
| Casos de test | `docs/qa/fase8-integrations-pr2/test-cases.md` |
| Resumen QA | `docs/qa/fase8-integrations-pr2/test-summary.md` |
