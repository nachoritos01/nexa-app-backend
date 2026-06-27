# Test Cases — SaaS Fase 7 PR1: Branding en PDFs + Reportes Avanzados (7.1-7.2)

**Version:** 1.0
**Date:** 2026-02-22
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-PM-{numero} (PM = Premium)
- **Priority:** P1 / P2 / P3 / P4
- **Tipo:** Funcional / Negativo / Regresion / E2E / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 7.1 — Tenant Settings + Branding

### TC-PM-001: Settings page accesible con permiso

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TenantSettingsTest::test_settings_page_accessible_with_permission`) |
| **Precondiciones** | User owner con plan Growth, permiso settings.manage |
| **Steps** | 1. Login como owner<br>2. GET /admin/tenant-settings |
| **Resultado esperado** | Pagina carga con status 200, muestra campos de negocio |
| **Estado** | Pasado |

### TC-PM-002: Logo path se guarda en tenant settings

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-002 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TenantSettingsTest::test_logo_upload_saves_path_in_tenant_settings`) |
| **Precondiciones** | Tenant con plan Growth |
| **Steps** | 1. Guardar logo_path en tenant.settings<br>2. Check en BD |
| **Resultado esperado** | logo_path persiste en JSON settings del tenant |
| **Estado** | Pasado |

### TC-PM-003: Starter no puede subir logo (feature gate)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-003 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TenantSettingsTest::test_starter_cannot_upload_logo`) |
| **Precondiciones** | Tenant con plan Starter |
| **Steps** | 1. Check plan es starter |
| **Resultado esperado** | Plan starter no tiene acceso a logo upload |
| **Estado** | Pasado |

### TC-PM-004: Business info se lee de tenant settings en PDF

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TenantSettingsTest::test_business_info_reads_from_tenant_settings`) |
| **Precondiciones** | Tenant con settings custom (name, slogan, messaging, email, address, logo) |
| **Steps** | 1. Guardar settings custom en tenant<br>2. Llamar PdfGenerator::getBusinessInfo($tenant)<br>3. Check cada campo |
| **Resultado esperado** | getBusinessInfo retorna valores de tenant.settings, no de config |
| **Estado** | Pasado |

### TC-PM-005: Editar slogan y guardar (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-005 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin en /admin/tenant-settings |
| **Steps** | 1. Navegar a /admin/tenant-settings<br>2. Editar campo slogan<br>3. Click "Guardar"<br>4. Check notificacion de exito<br>5. Recargar pagina<br>6. Check valor persiste |
| **Resultado esperado** | Slogan editado se guarda y persiste al recargar |
| **Estado** | Pasado |

### TC-PM-006: Eliminar logo existente (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-006 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Tenant con logo_path existente |
| **Steps** | 1. Navegar a /admin/tenant-settings<br>2. Check preview de logo visible<br>3. Click "Eliminar logo"<br>4. Check notificacion "Logo eliminado"<br>5. Check en BD que logo_path fue removido de settings |
| **Resultado esperado** | File borrado de disco, logo_path removido, UI actualizada |
| **Estado** | Pasado |

---

## 7.2 — Reportes Avanzados

### TC-PM-007: ProductProfitability retorna datos correctos

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-007 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`AdvancedReportsTest::test_product_profitability_returns_correct_data`) |
| **Precondiciones** | Item con order items en mes actual |
| **Steps** | 1. Crear item y order items<br>2. Query rentabilidad agrupada por item<br>3. Check titulo, qty, revenue |
| **Resultado esperado** | Retorna 1 item con title=Product Test, qty=10, revenue=2000 |
| **Estado** | Pasado |

### TC-PM-008: MonthlyComparison retorna 2 datasets

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`AdvancedReportsTest::test_monthly_comparison_returns_two_datasets`) |
| **Precondiciones** | Ordenes en mes actual y mes anterior |
| **Steps** | 1. Crear orden este mes y mes anterior<br>2. Contar por periodo |
| **Resultado esperado** | Ambos periodos tienen >= 1 orden |
| **Estado** | Pasado |

### TC-PM-009: Export profitability CSV funcional

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-009 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`AdvancedReportsTest::test_export_profitability_csv`) |
| **Precondiciones** | User owner autenticado |
| **Steps** | 1. GET /admin/exports/profitability<br>2. Check status 200<br>3. Check content-type text/csv |
| **Resultado esperado** | CSV descargado con headers correctos |
| **Estado** | Pasado |

### TC-PM-010: Reports solo visibles con permiso

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-010 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`AdvancedReportsTest::test_reports_only_visible_with_permission`) |
| **Precondiciones** | User con rol sales (sin permiso reports.export) |
| **Steps** | 1. Check que user sales no tiene permiso reports.export |
| **Resultado esperado** | can('reports.export') retorna false |
| **Estado** | Pasado |

### TC-PM-011: Dashboard muestra widgets de reportes (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-011 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin, datos de ordenes existentes |
| **Steps** | 1. Navegar a /admin<br>2. Scroll hasta ver todos los widgets<br>3. Check ProductProfitability con table y selector de mes<br>4. Check MonthlyComparison con chart de barras azul/gris<br>5. Check MonthlyReport con columns Historico y Ultimo |
| **Resultado esperado** | Todos los widgets renderizan sin error con datos correctos |
| **Estado** | Pasado |

### TC-PM-012: Export CSV con filtro por mes (manual browser)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-012 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP — JS fetch) |
| **Precondiciones** | Sesion autenticada en browser |
| **Steps** | 1. Fetch /admin/exports/profitability?month=2026-02 → 200, CSV con datos<br>2. Fetch sin parametro month → default mes actual<br>3. Fetch ?month=2025-01 (sin datos) → solo headers |
| **Resultado esperado** | CSV correcto en los 3 escenarios |
| **Estado** | Pasado |

### TC-PM-013: MonthlyComparison PostgreSQL compatible (regresion)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-013 |
| **Prioridad** | P1 |
| **Tipo** | Regresion |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | PostgreSQL como BD |
| **Steps** | 1. Navegar a /admin<br>2. Scroll al widget MonthlyComparison<br>3. Check que renderiza sin error SQL |
| **Resultado esperado** | Chart renderiza correctamente (bug DAY() → EXTRACT corregido) |
| **Estado** | Pasado |

### TC-PM-014: product_sizes pivot poblado por seeders (regresion)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-014 |
| **Prioridad** | P1 |
| **Tipo** | Regresion |
| **Automatizado** | No (manual, verificacion en BD via tinker) |
| **Precondiciones** | Seeders ejecutados |
| **Steps** | 1. Check que product_sizes tiene registrations<br>2. Navegar a /admin/orders/create<br>3. Seleccionar item<br>4. Check dropdown de talla tiene opciones |
| **Resultado esperado** | Dropdown muestra: Chica, Mediana, Grande, Extra Grande, Doble Extra Grande |
| **Estado** | Pasado |

### TC-PM-015: MonthlyReport top customers con Historico y Ultimo (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-015 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Dashboard con ordenes existentes |
| **Steps** | 1. Navegar a /admin<br>2. Scroll a seccion "Reporte Mensual"<br>3. Check table top customers<br>4. Check column "Historico" (total gastado historico)<br>5. Check column "Ultimo" (fecha ultimo order) |
| **Resultado esperado** | Ambas columns visibles con datos correctos |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 15 |
| Total | **15** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 8 |
| Manual (Chrome DevTools MCP) | 7 |
