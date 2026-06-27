# Test Cases — SaaS Fase 5 PR2: Landing + Pricing + Super-Admin + Exports

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-SA-{numero secuencial} (SA = Super-Admin / SaaS)
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E, Security
- **Status:** Pendiente, Pasado, Fallido, Bloqueado

---

## Super-Admin Panel — Access Control

### TC-SA-001: Super-admin accede al panel /super-admin

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_super_admin_can_access_super_admin_panel`) |
| **Precondiciones** | User con is_super_admin=true |
| **Steps** | 1. GET /super-admin |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-SA-002: User regular NO accede al panel /super-admin

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-002 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`SuperAdminTest::test_regular_user_cannot_access_super_admin_panel`) |
| **Precondiciones** | User con is_super_admin=false, con tenant |
| **Steps** | 1. GET /super-admin |
| **Resultado esperado** | HTTP 403 |
| **Estado** | Pasado |

---

### TC-SA-003: Guest redirigido a login del super-admin

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-003 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`SuperAdminTest::test_guest_is_redirected_to_super_admin_login`) |
| **Precondiciones** | Sin sesion |
| **Steps** | 1. GET /super-admin |
| **Resultado esperado** | Redirect a /super-admin/login |
| **Estado** | Pasado |

---

## Super-Admin Panel — TenantResource

### TC-SA-004: Lista de tenants visible para super-admin

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_super_admin_can_see_tenants_list`) |
| **Precondiciones** | Super-admin autenticado, tenants existentes |
| **Steps** | 1. GET /super-admin/tenants |
| **Resultado esperado** | HTTP 200, names de tenants visibles |
| **Estado** | Pasado |

---

### TC-SA-005: Accion suspend cambia is_active a false

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-005 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_super_admin_can_suspend_tenant`) |
| **Precondiciones** | Tenant con is_active=true |
| **Steps** | 1. Ejecutar accion "Suspender" en TenantResource |
| **Resultado esperado** | is_active=false en DB |
| **Estado** | Pasado |

---

### TC-SA-006: Accion reactivate cambia is_active a true

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_super_admin_can_reactivate_tenant`) |
| **Precondiciones** | Tenant con is_active=false |
| **Steps** | 1. Ejecutar accion "Reactivar" en TenantResource |
| **Resultado esperado** | is_active=true en DB |
| **Estado** | Pasado |

---

### TC-SA-007: Accion reset onboarding limpia datos de onboarding

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-007 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_super_admin_can_reset_onboarding`) |
| **Precondiciones** | Tenant con onboarding_completed_at != null |
| **Steps** | 1. Ejecutar accion "Reset Onboarding" en TenantResource |
| **Resultado esperado** | onboarding_steps=null, onboarding_completed_at=null |
| **Estado** | Pasado |

---

## Super-Admin Panel — Impersonation

### TC-SA-008: Impersonate guarda session y redirige

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_impersonation_sets_session_and_redirects`) |
| **Precondiciones** | Super-admin autenticado, tenant con owner |
| **Steps** | 1. Ejecutar accion "Ver como Owner" en TenantResource |
| **Resultado esperado** | Session impersonating_from set, session tenant_id set, redirect a /admin |
| **Estado** | Pasado |

---

### TC-SA-009: Stop impersonation regresa al super-admin

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-009 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SuperAdminTest::test_stop_impersonation_returns_to_super_admin`) |
| **Precondiciones** | Session con impersonating_from = super-admin ID |
| **Steps** | 1. GET /impersonation/stop |
| **Resultado esperado** | Auth = super-admin, session keys cleared, redirect a /super-admin |
| **Estado** | Pasado |

---

## SaaS Template Pages — Landing

### TC-SA-010: Landing page accesible sin autenticacion

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-010 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_landing_page_is_accessible`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /saas-template |
| **Resultado esperado** | HTTP 200, contiene "El sistema operativo", "Test gratis 14 dias" |
| **Estado** | Pasado |

---

### TC-SA-011: Landing page con hero, features, CTA (manual)

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-011 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template |
| **Steps** | 1. Check hero con titulo y CTAs<br>2. Check 6 feature cards<br>3. Check testimonial<br>4. Check CTA final con boton "Crear cuenta gratis"<br>5. Check nav con logo SaaS Template y links |
| **Resultado esperado** | Todo visible y bien formateado |
| **Estado** | Pasado |

---

## SaaS Template Pages — Pricing

### TC-SA-012: Pricing page accesible con 3 planes

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-012 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_pricing_page_is_accessible`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /saas-template/pricing |
| **Resultado esperado** | HTTP 200, contiene "Planes simples y transparentes", "Starter", "Growth", "Pro" |
| **Estado** | Pasado |

---

### TC-SA-013: Pricing page muestra features de planes

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-013 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_pricing_page_shows_plan_features`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /saas-template/pricing<br>2. Check textos "orders/mes", "users", "Exportar reportes CSV" |
| **Resultado esperado** | Features visibles en cards de planes |
| **Estado** | Pasado |

---

### TC-SA-014: Pricing page muestra FAQ

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-014 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_pricing_page_shows_faq`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /saas-template/pricing<br>2. Check "Preguntas frecuentes", "Necesito tarjeta para empezar?" |
| **Resultado esperado** | FAQ section visible |
| **Estado** | Pasado |

---

### TC-SA-015: Pricing page con toggle y cards (manual)

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-015 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template/pricing |
| **Steps** | 1. Check toggle Mensual/Anual<br>2. Check 3 cards con precios<br>3. Check Growth destacado como "Popular"<br>4. Check CTAs "Empezar test gratis"<br>5. Check FAQ accordion con 6 preguntas |
| **Resultado esperado** | Toggle funcional, precios correctos, FAQ expandible |
| **Estado** | Pasado |

---

## Plan Pre-Selection

### TC-SA-016: Registration acepta parametro ?plan=

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-016 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_registration_page_accepts_plan_query_param`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /admin/register?plan=growth |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-SA-017: Registration con plan pre-seleccionado crea tenant con plan correcto

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-017 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | Si (`SaasTemplatePagesTest::test_registration_with_plan_preselection`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Livewire::test(Register) con plan=growth<br>2. Llenar formulario<br>3. call('register') |
| **Resultado esperado** | Tenant creado con plan='growth', redirect a /admin/onboarding |
| **Estado** | Pasado |

---

## CSV Export — Plan Gating

### TC-SA-018: Growth puede exportar CSV

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-018 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ExportTest::test_growth_plan_can_export_orders`) |
| **Precondiciones** | Tenant plan=growth, user con permiso reports.export |
| **Steps** | 1. GET /admin/exports/orders |
| **Resultado esperado** | HTTP 200, Content-Type text/csv |
| **Estado** | Pasado |

---

### TC-SA-019: Starter NO puede exportar CSV

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-019 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ExportTest::test_starter_plan_cannot_export`) |
| **Precondiciones** | Tenant plan=starter, user con permiso reports.export |
| **Steps** | 1. GET /admin/exports/orders |
| **Resultado esperado** | HTTP 403 |
| **Estado** | Pasado |

---

### TC-SA-020: Pro puede exportar CSV

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-020 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ExportTest::test_pro_plan_can_export`) |
| **Precondiciones** | Tenant plan=pro, user con permiso reports.export |
| **Steps** | 1. GET /admin/exports/orders |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-SA-021: User sin permiso reports.export NO puede exportar

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-021 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ExportTest::test_user_without_permission_cannot_export`) |
| **Precondiciones** | Tenant plan=growth, user SIN permiso reports.export |
| **Steps** | 1. GET /admin/exports/orders |
| **Resultado esperado** | HTTP 403 |
| **Estado** | Pasado |

---

### TC-SA-022: Export con tipo desconocido retorna 404

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-022 |
| **Prioridad** | P3 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ExportTest::test_unknown_export_type_returns_404`) |
| **Precondiciones** | Tenant plan=growth, user con permiso |
| **Steps** | 1. GET /admin/exports/invalid |
| **Resultado esperado** | HTTP 404 |
| **Estado** | Pasado |

---

## Super-Admin Dashboard (Manual)

### TC-SA-023: Dashboard muestra metricas SaaS

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-023 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como super-admin en /super-admin/login |
| **Steps** | 1. Ir a /super-admin<br>2. Check widget MRR con valor ($1,299)<br>3. Check Active Tenants count<br>4. Check Active Trials count<br>5. Check Churned This Month count<br>6. Check chart de crecimiento |
| **Resultado esperado** | Todas las metricas visibles con valores correctos |
| **Estado** | Pasado |

---

### TC-SA-024: Tenants list muestra todos los tenants con actions

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-024 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como super-admin |
| **Steps** | 1. Ir a /super-admin/tenants<br>2. Check columns: name, plan, owner, status, trial_ends_at<br>3. Check actions available en dropdown |
| **Resultado esperado** | Lista completa con datos y actions visibles |
| **Estado** | Pasado |

---

### TC-SA-025: Super-admin login muestra branding "SaaS Template Admin"

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-025 |
| **Prioridad** | P3 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /super-admin/login |
| **Steps** | 1. Check titulo "SaaS Template Admin"<br>2. Check formulario de login |
| **Resultado esperado** | Branding correcto, formulario funcional |
| **Estado** | Pasado |

---

## SaaS Template Pricing — Toggle Mensual/Anual con Period Param

### TC-SA-026: Toggle cambia precios de mensual a anual

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-026 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template/pricing |
| **Steps** | 1. Check precios mensuales: Starter $299, Growth $699, Pro $1,299<br>2. Click boton "Anual -20%"<br>3. Check precios anuales: Starter $239/mes ($2,868/year), Growth $559/mes ($6,708/year), Pro $1,039/mes ($12,468/year) |
| **Resultado esperado** | Precios cambian segun toggle, yearly muestra precio/mes + total/year |
| **Estado** | Pasado |

---

### TC-SA-027: Links CTA incluyen period=monthly en modo mensual

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-027 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template/pricing, toggle en "Mensual" |
| **Steps** | 1. Inspeccionar href de CTA "Empezar test gratis" de cada plan |
| **Resultado esperado** | URLs: /admin/register?plan=starter&period=monthly, ?plan=growth&period=monthly, ?plan=pro&period=monthly |
| **Estado** | Pasado |

---

### TC-SA-028: Links CTA incluyen period=yearly en modo anual

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-028 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template/pricing, click toggle "Anual -20%" |
| **Steps** | 1. Inspeccionar href de CTA "Empezar test gratis" de cada plan |
| **Resultado esperado** | URLs: /admin/register?plan=starter&period=yearly, ?plan=growth&period=yearly, ?plan=pro&period=yearly |
| **Estado** | Pasado |

---

### TC-SA-029: Alpine.js carga correctamente en layout saas-template

| Campo | Valor |
|-------|-------|
| **ID** | TC-SA-029 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /saas-template/pricing |
| **Steps** | 1. Check que solo precios mensuales son visibles al cargar (x-cloak oculta yearly)<br>2. Check que toggle Mensual tiene estilo activo (bg-white, shadow)<br>3. Click "Anual" → precios cambian inmediatamente |
| **Resultado esperado** | Alpine inicializa, x-cloak funciona, toggle reactivo |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 29 |
| Pending | 0 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **29** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 20 |
| Manual (UI/browser) | 9 |
