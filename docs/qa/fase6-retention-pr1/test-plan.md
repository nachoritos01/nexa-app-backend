# Test Plan — SaaS Fase 6 PR1: Core Retention (6.1-6.3)

**Version:** 1.0
**Date:** 2026-02-20
**Feature:** Health Score, Proactive Alerts, Exit Survey, Feature Tracking
**Branch:** feature/saas-phase6-retention-pr1

---

## 1. Objetivo

Validar que el sistema de retencion detecta riesgo de churn y actua proactivamente: health score calculado por tenant, alertas por inactividad y health bajo, exit survey antes de cancelar, feature usage tracking, y login tracking.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Health Score | Command `saas:health-score` con 4 factores, escala 0-100 |
| Feature Usage Tracking | Table feature_usages, FeatureUsage::track() en 6 puntos |
| Login Tracking | last_login_at en users, actualizado via Login event |
| Inactivity Alerts | Email a owners despues de 14d sin orders, dedup |
| Low Health Alerts | Email consolidado a super-admins cuando score < 50 |
| Exit Survey | Modal con 5 razones en billing page antes de cancelar |
| Super-Admin Widgets | HealthScoreDistribution y ChurnReasonsChart doughnuts |
| TenantResource | Column health_score con badge, filtro por health status |

### Fuera de alcance

- Stripe real E2E — modal redirige a billing.portal (mock)
- Email delivery real — solo se verifica con Notification::fake()
- Deploy a Railway — verificacion local unicamente
- Win-back emails (6.4), referral program (6.5), Messaging auto (6.6) — PR2

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (4 files, 15 tests) | Health score, alerts, tracking, surveys |
| Manuales funcionales | Browser + Chrome DevTools MCP | UI billing modal, super-admin widgets, health column |
| Regresion | `composer test` (186 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [x] 4 migrations creadas (feature_usages, health_score, last_login_at, cancellation_surveys)
- [x] FeatureUsage model con track() statico
- [x] CancellationSurvey model con reasonLabels()
- [x] Tenant model actualizado (health_score, relationships, helpers)
- [x] User model actualizado (last_login_at)
- [x] config/saas.php con seccion retention
- [x] Login event listener en AppServiceProvider
- [x] FeatureUsage::track() en 6 puntos de creacion
- [x] CalculateHealthScore command
- [x] SendRetentionAlerts command
- [x] InactivityReminderNotification y LowHealthScoreAlert
- [x] Exit survey modal en Billing page
- [x] HealthScoreDistribution y ChurnReasonsChart widgets
- [x] TenantResource con health column y filter

## 5. Criterios de salida

- [x] 15/15 tests automatizados nuevos pasan
- [x] 186/186 tests totales pasan (0 regresiones)
- [x] Checklist manual: health score column visible con colors
- [x] Checklist manual: health score filter funcional
- [x] Checklist manual: widgets doughnut en super-admin dashboard
- [x] Checklist manual: exit survey modal con 5 razones
- [x] 0 bugs criticos abiertos

## 6. Datos de test

### Health Score Factores

| Factor | Peso | Score 100 | Score 0 |
|--------|------|-----------|---------|
| Login reciente | 30% | Login hoy | Sin login 30+ dias |
| Orders recientes | 30% | Order hoy | Sin orders 30+ dias |
| Features usadas | 20% | 6/6 features | 0/6 features |
| Users activos | 20% | 100% con login 7d | 0% con login |

### Cancellation Reasons

| Clave | Label |
|-------|-------|
| price | Precio muy alto |
| missing_features | Faltan funcionalidades |
| closed_business | Cerro el negocio |
| competitor | Cambio a otra plataforma |
| other | Otro |

### Inactivity Scenarios

| Ultimo order | Resultado esperado |
|---------------|-------------------|
| 20 dias atras | Reminder enviado |
| 3 dias atras | No enviado |
| 20d + reminder ya enviado 5d atras | No enviado (dedup) |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| diffInDays retorna signed con strings | Confirmado | Alto | Corregido con Carbon::parse + absolute: true |
| Exit survey modal no renderea en Filament | Confirmado | Medio | Modal funciona, requeria doble click en MCP (Livewire morphing) |
| health_score null en tenants recien creados | Bajo | Bajo | Handled con ? null check y label "N/A" |
| FeatureUsage::track no-op sin tenant context | Bajo | Bajo | Guard clause: if (!currentTenant()) return |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase6-retention-pr1/test-plan.md` (este file) |
| Test Cases | `docs/qa/fase6-retention-pr1/test-cases.md` |
| Test Summary | `docs/qa/fase6-retention-pr1/test-summary.md` |
