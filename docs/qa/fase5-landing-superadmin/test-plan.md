# Test Plan — SaaS Fase 5 PR2: Landing + Pricing + Super-Admin + Exports

**Version:** 1.0
**Date:** 2026-02-20
**Feature:** SaaS Template landing/pricing, super-admin panel, CSV exports, monthly report
**Branch:** feature/saas-phase5-landing-superadmin

---

## 1. Objetivo

Validar que el super-admin panel permite gestionar tenants con metricas SaaS, que las paginas publicas de SaaS Template (landing y pricing) muestran contenido correcto con pre-seleccion de plan, que los exports CSV respetan plan gating y tenant isolation, y que el widget de reporte mensual muestra datos correctos.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Super-Admin Panel | Panel separado en /super-admin con autenticacion independiente |
| TenantResource | CRUD de tenants con actions: suspend, reactivate, reset onboarding, impersonate |
| SaaS Metrics | Widgets: MRR, Active Tenants, Active Trials, Churned This Month |
| TenantGrowthChart | Chart de nuevos tenants por semana (8 semanas) |
| Impersonation | Session-based: login como owner, banner de retorno, stop controller |
| SaaS Template Landing | Pagina publica /saas-template con hero, features, testimonial, CTA |
| SaaS Template Pricing | Pagina /saas-template/pricing con toggle mensual/anual, 3 planes, FAQ |
| Plan Pre-selection | ?plan=growth en URL de registration → tenant creado con plan correcto |
| CSV Export | Controller con streamDownload, plan gating (starter bloqueado) |
| Export Buttons | Header actions en OrderResource y CustomerResource |
| MonthlyReport | Widget con top 5 items y customers del mes |

### Fuera de alcance

- Stripe Checkout E2E — requiere Stripe keys reales
- Email delivery real — solo se verifica logica
- Deploy a Railway — verificacion local unicamente
- Mobile responsive — solo desktop verificado manualmente

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (`SuperAdminTest`, `ExportTest`, `SaasTemplatePagesTest`) | Access control, actions, plan gating, pages |
| Manuales funcionales | Browser + Chrome DevTools MCP | UI landing, pricing, super-admin dashboard |
| Regresion | `composer test` (171 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [x] Migration is_super_admin creada
- [x] User model con canAccessPanel multi-panel
- [x] SuperAdminPanelProvider registrado en bootstrap/providers.php
- [x] TenantResource con actions (suspend, reactivate, reset, impersonate)
- [x] SaasMetrics y TenantGrowthChart widgets creados
- [x] ImpersonationController creado
- [x] SaaS Template layout standalone creado
- [x] Landing y pricing pages creadas
- [x] Register page con #[Url] plan property
- [x] ExportController con plan gating
- [x] Export header actions en OrderResource y CustomerResource
- [x] MonthlyReport widget creado

## 5. Criterios de salida

- [x] 20/20 tests automatizados nuevos pasan
- [x] 171/171 tests totales pasan (0 regresiones)
- [x] Checklist manual: landing page con hero, features, CTA
- [x] Checklist manual: pricing page con 3 planes, toggle, FAQ
- [x] Checklist manual: super-admin login con branding "SaaS Template Admin"
- [x] Checklist manual: super-admin dashboard con metricas y chart
- [x] Checklist manual: super-admin tenants list con actions
- [x] 0 bugs criticos abiertos

## 6. Datos de test

### Super-Admin

| User | is_super_admin | Resultado esperado |
|---------|----------------|--------------------|
| Admin (seeder) | true | Accede /super-admin |
| Owner regular | false | 403 en /super-admin |
| Guest | N/A | Redirect a /super-admin/login |

### Planes para pricing

| Plan | Precio mensual | Precio anual | Features |
|------|---------------|--------------|----------|
| Starter | $299 | $2,868 | 50 orders, 2 users, 1 location |
| Growth | $699 | $6,708 | 200 orders, 5 users, 3 locationes |
| Pro | $1,299 | $12,468 | Ilimitado todo |

### Export scenarios

| Tenant plan | Permiso reports.export | Resultado |
|-------------|----------------------|-----------|
| Growth | Si | 200 + CSV stream |
| Starter | Si | 403 |
| Pro | Si | 200 + CSV stream |
| Growth | No | 403 |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| SuperAdminPanelProvider no registrado | Confirmado | Alto | Agregado a bootstrap/providers.php |
| request('plan') no funciona en Livewire | Confirmado | Alto | Cambiado a #[Url] property |
| Pricing toggle duplicado (2 x-data scopes) | Confirmado | Medio | Consolidado en un solo wrapper |
| Alpine.js no cargado en layout saas-template | Confirmado | Alto | Alpine importado en bootstrap.js via Vite, x-cloak CSS agregado |
| Paneles Filament con auth sessions separadas | Confirmado | Bajo | Documentado, login via /super-admin/login |
| TenantGrowthChart DATE_TRUNC incompatible SQLite | Bajo | Medio | Tests usan RefreshDatabase con SQLite, chart solo en prod |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase5-landing-superadmin/test-plan.md` (este file) |
| Test Cases | `docs/qa/fase5-landing-superadmin/test-cases.md` |
| Test Summary | `docs/qa/fase5-landing-superadmin/test-summary.md` |
