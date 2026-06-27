# Test Plan — Plugin Marketplace + Stripe Billing

**Version:** 1.0
**Date:** 2026-03-11
**Feature:** Plugin marketplace, paid plugin billing, sidebar gating, hasModule memoization
**Branch:** feature/plugin-marketplace

---

## 1. Objetivo

Validar que el marketplace permite activar/desactivar plugins por tenant, que los plugins de pago se integran con Stripe subscription items, que el sidebar se oculta cuando un plugin está desactivado, y que `hasModule()` memoiza correctamente.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Marketplace page | Listado por categoría, toggles, badges (Included/Free/Paid) |
| Paid plugins | Modal de confirmación, "Subscribe first" badge, Stripe subscription items |
| Sidebar gating | LocationResource, CashRegister, ApiSettings, WebhookSettings ocultos sin plugin |
| hasModule() | Memoización per-request, cache clear tras toggle |
| Billing page | Sección "Active Add-ons" con plugins de pago activos |
| Webhook sync | syncPluginsFromSubscription en subscription.updated, deactivate en subscription.deleted |
| SuperAdmin form | stripe_price_id requerido para paid, required_modules como CheckboxList |
| Seeder | updateOrCreate, plugins de pago (Advanced Analytics, White Label) |
| Accessibility | aria-label en toggles, wire:loading spinner |
| Tenant isolation | Toggle de tenant A no afecta tenant B |

### Fuera de alcance

- Stripe Dashboard (creación de precios) — manual, externo
- Payment 3D Secure / SCA flows — gestionado por Stripe
- Plugin feature code (solo toggle on/off, no la funcionalidad del plugin)

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unit/Feature | PHPUnit (285 tests) | hasModule, toggle, isolation, billing service, sidebar gating |
| Static analysis | PHPStan Level 5 | 0 errors |
| Manual/E2E | Chrome DevTools MCP | Marketplace UI, modal, sidebar, billing page |
| Accessibility | a11y snapshot | aria-label, role=switch, aria-checked |

## 4. Entorno

| Componente | Detalle |
|------------|---------|
| PHP | 8.4+ |
| Laravel | 12 |
| Filament | 3.3 |
| PostgreSQL | 15 (Docker, port 5433) |
| Stripe | Laravel Cashier (mocked en tests) |

## 5. Riesgos

| Riesgo | Mitigación |
|--------|------------|
| Stripe API failure al activar paid plugin | Try/catch con fallback local + log |
| hasModule cache stale | Clear on every toggle + null sentinel para reset |
| Sidebar no refleja cambio hasta reload | Filament cachea nav per-request — documentado |
| Race condition en webhook sync | syncPluginsFromSubscription es idempotente |
