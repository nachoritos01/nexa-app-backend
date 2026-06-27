# Test Plan — SaaS Fase 7 PR1: Branding en PDFs + Reportes Avanzados (7.1-7.2)

**Version:** 1.0
**Date:** 2026-02-22
**Feature:** Tenant Settings con logo upload, branding en PDFs, rentabilidad por item, comparativo mensual, top customers mejorado, export CSV
**Branch:** feature/saas-phase7-premium-pr1

## 1. Objetivo

Validar que las features premium de branding (7.1) y reportes avanzados (7.2) funcionan correctamente: settings del negocio con upload de logo, branding condicional en PDFs, widget de rentabilidad por item, comparativo mensual, y export CSV de rentabilidad.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Tenant Settings | Pagina /admin/tenant-settings con campos de negocio + logo upload |
| Logo feature gate | Solo Growth+ puede subir logo (Starter ve mensaje upgrade) |
| PDF branding | Logo condicional en header de quotes PDF |
| ProductProfitability | Widget table con rentabilidad por item + selector de mes |
| MonthlyComparison | Chart de barras comparando mes actual vs anterior |
| MonthlyReport mejorado | Top customers con columns Historico y Ultimo order |
| Export CSV | Endpoint /admin/exports/profitability con filtro por mes |
| Seeders | product_sizes pivot poblado correctamente |

### Fuera de alcance

- Facturacion CFDI (PR2)
- Corte de caja (PR2)
- Prioridad de ordenes (PR2)

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unitarias/Feature | PHPUnit (composer test) | Models, services, endpoints |
| Manual UI | Chrome DevTools MCP | Paginas Filament, widgets, formularios |
| Regresion | PHPUnit suite completa | 215 tests existentes |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase7-premium-pr1 creado desde develop
- [x] TenantSettings page implementada
- [x] PdfGenerator modificado para leer tenant settings
- [x] ProductProfitability widget creado
- [x] MonthlyComparison widget creado
- [x] MonthlyReport mejorado con historico y ultimo order
- [x] ExportController con tipo profitability
- [x] Tests creados (8 tests en 2 files)

## 5. Criterios de salida

- [x] 215/215 tests pasan (536 assertions)
- [x] Checklist manual completo (15/15 verificaciones)
- [x] 2 bugs encontrados y corregidos
- [x] Sin regresiones en tests existentes

## 6. Datos de test

### Tenant Settings

| Escenario | Resultado esperado |
|-----------|-------------------|
| Editar slogan y guardar | Notificacion exito, valor persiste al recargar |
| Eliminar logo existente | File borrado de disco, logo_path removido de settings |
| Plan Starter | Logo upload deshabilitado con mensaje upgrade |
| Plan Growth+ | Logo upload habilitado |

### Reportes

| Escenario | Resultado esperado |
|-----------|-------------------|
| Dashboard con datos | ProductProfitability y MonthlyComparison visibles |
| Export CSV con mes | CSV con item, qty, ingresos, % |
| Export CSV sin mes | Default a mes actual |
| Export CSV mes sin datos | Solo headers |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| DAY() incompatible con PostgreSQL | Alta | Alto | Detectado y corregido: EXTRACT(DAY FROM) |
| product_sizes pivot vacio | Alta | Alto | Detectado y corregido: seeders actualizados |
| Logo path incorrecto en DomPDF | Media | Medio | Usar storage_path() para path absoluto |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase7-premium-pr1/test-plan.md` |
| Casos de test | `docs/qa/fase7-premium-pr1/test-cases.md` |
| Resumen QA | `docs/qa/fase7-premium-pr1/test-summary.md` |
