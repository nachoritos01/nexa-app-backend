# Test Plan — SaaS Fase 9 PR2: Security + Monitoring (9.3-9.4)

**Version:** 1.0
**Date:** 2026-02-23
**Feature:** Session security, encrypted settings, OWASP headers, Sentry, activation funnel
**Branch:** feature/saas-phase9-optimization-pr2

## 1. Objetivo

Validar que las medidas de security (session encryption, encrypted tenant settings, OWASP headers, tenant isolation), integraciones de monitoring (Sentry), y widgets de analytics (activation funnel) funcionan correctamente. Esta es la fase final del SaaS evolution — al completar: SaaS Template GROWTH READY (v1.0.0).

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Session security | SESSION_ENCRYPT y SESSION_SECURE_COOKIE documentados para operations |
| HasEncryptedSettings | Trait para encrypt/decrypt de settings sensibles (webhook_secret, API keys) |
| saas:encrypt-settings | Command artisan para migrar valores planos a encriptados (idempotente) |
| Tenant isolation | 8 tests exhaustivos (Eloquent, API v1, WebhookLog, direct DB) |
| SecurityHeaders | Middleware OWASP (X-Content-Type-Options, X-Frame-Options, etc.) |
| Sentry | sentry/sentry-laravel con contexto tenant (id, name, plan) |
| ActivationFunnel | Widget super-admin: Signup → Onboarding → 1er Order → 2do Order → Suscrito |
| Monitoring docs | UptimeRobot + Sentry setup en RAILWAY_DEPLOY_STATUS.md |

### Fuera de alcance

- Configuracion real de Sentry DSN (requiere cuenta sentry.io)
- Deployment de Redis/R2 (preparado en PR1)
- User documentation y FAQ (diferido a post-launch)

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (composer test) | Security headers, tenant isolation, API isolation |
| Unit tests | PHPUnit | Encrypted settings, encrypt command |
| Regresion | PHPUnit suite completa | 251 tests existentes (PR1) |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase9-optimization-pr2 basado en PR1
- [x] HasEncryptedSettings trait implementado
- [x] saas:encrypt-settings command implementado
- [x] WebhookService y WebhookSettings usan accessors encriptados
- [x] SecurityHeaders middleware registrado globalmente
- [x] sentry/sentry-laravel instalado con tenant context
- [x] ActivationFunnel widget y vista creados
- [x] Tests creados (22 tests en 4 files)

## 5. Criterios de salida

- [x] 261/261 tests pasan (658 assertions)
- [x] Sin regresiones en tests existentes
- [x] Security headers presentes en responses
- [x] Encrypted settings: encrypt/decrypt funciona, legacy compatible
- [x] Tenant isolation: 8/8 tests pasan
- [x] saas:encrypt-settings es idempotente

## 6. Datos de test

### Encrypted Settings

| Escenario | Resultado esperado |
|-----------|-------------------|
| setSecureSetting('webhook_secret', 'abc') | Valor encriptado en DB, no legible como texto plano |
| getSecureSetting('webhook_secret') | Valor desencriptado correctamente |
| Legacy plain text en DB | Se lee sin error (backward compatible) |
| saas:encrypt-settings (1ra vez) | Encripta valores planos |
| saas:encrypt-settings (2da vez) | Skip valores ya encriptados |

### Security Headers

| Escenario | Resultado esperado |
|-----------|-------------------|
| GET / | X-Content-Type-Options: nosniff |
| GET / | X-Frame-Options: DENY |
| GET / | Referrer-Policy: strict-origin-when-cross-origin |

### Tenant Isolation

| Escenario | Resultado esperado |
|-----------|-------------------|
| Tenant A query orders | Solo ve orders propias |
| API v1 token tenant A | No retorna datos de tenant B |
| WebhookLog con scope | Filtrado por tenant |
| DB::table() sin scope | Ve todos los registrations (confirma necesidad de scope) |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| APP_KEY change rompe encrypted settings | Baja | Alto | Documentar: no cambiar APP_KEY despues de encriptar |
| Sentry overhead en requests | Baja | Bajo | SENTRY_TRACES_SAMPLE_RATE=0.1 (10%) |
| SecurityHeaders rompe iframes de Filament | Baja | Medio | X-Frame-Options: DENY es correcto, Filament no usa iframes |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase9-optimization-pr2/test-plan.md` |
| Casos de test | `docs/qa/fase9-optimization-pr2/test-cases.md` |
| Resumen QA | `docs/qa/fase9-optimization-pr2/test-summary.md` |
