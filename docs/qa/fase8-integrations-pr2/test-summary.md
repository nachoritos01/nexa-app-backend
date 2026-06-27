# QA Summary: SaaS Fase 8 PR2 — Rate Limiting + Shopify Research (8.3-8.4)

**Date:** 2026-02-22
**Branch:** feature/saas-phase8-integrations-pr2
**PR:** #34

## Test Results

```
composer test
Tests: 244 passed (613 assertions)
Duration: ~22s
```

### New Tests Added (4)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/Api/V1/RateLimitTest.php | 4 | ~13 | PASS |

### Existing Tests (240) — No Regressions

| Test File | Tests | Status |
|-----------|-------|--------|
| tests/Unit/Models/*.php | 27 | PASS |
| tests/Unit/Services/*.php | 12 | PASS |
| tests/Feature/*.php (preexistentes) | 201 | PASS |

## Manual Verification Checklist

### Dark Mode — Texto blanco en paginas settings
- [x] /admin/billing — Plan info, uso, planes available, referidos legibles
- [x] /admin/whats-app-settings — Toggle corregido (button switch), labels y checkboxes blancos
- [x] /admin/tenant-settings — Labels formulario (Name, Slogan, Messaging, Email, Direccion) blancos
- [x] /admin/api-settings — Descripcion, label token, table tokens, referencia rapida blancos
- [x] /admin/webhook-settings — Toggle funcional, URL label, secret, eventos, table logs blancos

### Rate Limiting
- [x] Header X-RateLimit-Limit presente en API responses
- [x] Header X-RateLimit-Remaining presente
- [x] Pro plan: 300 req/min
- [x] 429 Too Many Requests al exceder limite

## Issues Found and Fixed

1. **Toggle switch CSS con peer classes** — El toggle basado en `peer-checked:after:translate-x-full` no renderizaba correctamente en Tailwind. Fix: reemplazar con `<button role="switch">` usando clases explicitas y condicionales Blade.

2. **Texto invisible en dark mode** — Multiples paginas settings usaban `dark:text-gray-300/400` que era poco legible sobre `bg-gray-800`. Fix: cambiar a `dark:text-white` en las 5 paginas: billing, messaging-settings, tenant-settings, api-settings, webhook-settings.

3. **opacity-50 demasiado agresivo** — La seccion deshabilitada en webhooks/messaging usaba `opacity-50` que hacia el texto casi invisible en dark mode. Fix: cambiar a `opacity-60`.

## Files Changed

### Created (4)
- `tests/Feature/Api/V1/RateLimitTest.php` — 4 tests rate limiting
- `app/Services/ShopifyService.php` — Stub service (isConfigured, syncProduct, syncOrder)
- `docs/features/21-shopify-integration-research.md` — Analisis viabilidad Shopify

### Modified (7)
- `app/Providers/AppServiceProvider.php` — RateLimiter::for('api-tenant')
- `config/saas.php` — api.rate_limits (starter:60, growth:120, pro:300) + shopify config
- `resources/views/filament/pages/webhook-settings.blade.php` — Toggle switch + dark:text-white
- `resources/views/filament/pages/billing.blade.php` — dark:text-white
- `resources/views/filament/pages/messaging-settings.blade.php` — Toggle switch + dark:text-white
- `resources/views/filament/pages/tenant-settings.blade.php` — dark:text-white
- `resources/views/filament/pages/api-settings.blade.php` — dark:text-white

### Documentation
- `docs/qa/fase8-integrations-pr2/test-plan.md`
- `docs/qa/fase8-integrations-pr2/test-cases.md`
- `docs/qa/fase8-integrations-pr2/test-summary.md`
- `docs/saas/11-SAAS_EVOLUTION_ROADMAP.md` — Checkboxes 8.3, 8.4
- `.claude/context/current.md` — Estado actualizado
