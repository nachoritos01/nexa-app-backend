# Test Cases — SaaS Fase 8 PR2: Rate Limiting + Shopify Research (8.3-8.4)

**Version:** 1.0
**Date:** 2026-02-22
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-INT-{numero} (INT = Integrations, continua desde PR1)
- **Priority:** P1 / P2 / P3 / P4
- **Tipo:** Funcional / Negativo / Regresion / E2E / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 8.3 — Rate Limiting por Plan

### TC-INT-028: Pro plan tiene limite 300

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-028 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RateLimitTest::test_pro_plan_has_300_limit`) |
| **Precondiciones** | Token de tenant Pro |
| **Steps** | 1. GET /api/v1/orders<br>2. Check header X-RateLimit-Limit: 300 |
| **Resultado esperado** | Limite 300 req/min para plan Pro |
| **Estado** | Pasado |

### TC-INT-029: Headers de rate limit presentes

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-029 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RateLimitTest::test_rate_limit_header_present`) |
| **Precondiciones** | Token valido |
| **Steps** | 1. GET /api/v1/orders<br>2. Check X-RateLimit-Limit presente<br>3. Check X-RateLimit-Remaining presente |
| **Resultado esperado** | Ambos headers presentes en response |
| **Estado** | Pasado |

### TC-INT-030: Sin autenticacion retorna 401

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-030 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`RateLimitTest::test_unauthenticated_returns_401`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /api/v1/orders sin token |
| **Resultado esperado** | 401 (auth falla antes de rate limit) |
| **Estado** | Pasado |

### TC-INT-031: Exceder limite retorna 429

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-031 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RateLimitTest::test_exceeding_limit_returns_429`) |
| **Precondiciones** | Token con plan que tiene limite bajo |
| **Steps** | 1. Configurar rate limit bajo (2 req/min)<br>2. Hacer 3 requests<br>3. Check tercer request = 429 |
| **Resultado esperado** | 429 Too Many Requests |
| **Estado** | Pasado |

---

## Dark Mode UI — Verificacion Manual

### TC-INT-032: Billing dark mode texto blanco (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-032 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dark theme activo |
| **Steps** | 1. Navegar a /admin/billing<br>2. Check textos legibles: plan info, uso actual, planes, referidos |
| **Resultado esperado** | Todos los textos en blanco, legibles sobre fondo oscuro |
| **Estado** | Pasado |

### TC-INT-033: Messaging Settings dark mode (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-033 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dark theme activo |
| **Steps** | 1. Navegar a /admin/whats-app-settings<br>2. Check toggle switch funcional<br>3. Check labels y checkboxes legibles |
| **Resultado esperado** | Toggle con animacion, textos blancos |
| **Estado** | Pasado |

### TC-INT-034: Tenant Settings dark mode (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-034 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dark theme activo |
| **Steps** | 1. Navegar a /admin/tenant-settings<br>2. Check labels de formulario legibles<br>3. Check seccion logo legible |
| **Resultado esperado** | Labels en blanco, inputs con texto blanco |
| **Estado** | Pasado |

### TC-INT-035: API Settings dark mode (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-035 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dark theme activo |
| **Steps** | 1. Navegar a /admin/api-settings<br>2. Check texto descripcion, labels, table tokens, referencia rapida |
| **Resultado esperado** | Todo el texto legible en blanco |
| **Estado** | Pasado |

### TC-INT-036: Webhook Settings dark mode (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-036 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dark theme activo |
| **Steps** | 1. Navegar a /admin/webhook-settings<br>2. Check toggle switch funcional (on/off)<br>3. Check labels, secret, eventos legibles |
| **Resultado esperado** | Toggle con animacion, textos blancos, secret legible |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 9 |
| Total | **9** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 4 |
| Manual (Chrome DevTools MCP) | 5 |
