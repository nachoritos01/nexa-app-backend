# Test Plan — SaaS Fase 7 PR2: CFDI + Corte de Caja + Cola de Operations (7.3-7.5)

**Version:** 1.0
**Date:** 2026-02-22
**Feature:** Facturacion CFDI stub, corte de caja con filtros de fecha, prioridad de ordenes con sort en production board
**Branch:** feature/saas-phase7-premium-pr2

## 1. Objetivo

Validar que las features premium de facturacion (7.3), corte de caja (7.4), y prioridad de ordenes (7.5) funcionan correctamente: model Invoice con CfdiService, pagina CashRegister con presets y comparativos, campo priority en ordenes con sort en ProductionBoard.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| CFDI Invoice | Model Invoice, CfdiService, migracion, relationships |
| CFDI action | Boton "Facturar" en OrderResource (solo plan Pro) |
| Corte de Caja | Pagina /admin/cash-register con presets Hoy/Semana/Mes |
| Cash Register | Desglose por metodo de payment, comparativo vs periodo anterior |
| Order Priority | Campo priority con enum OrderPriority (5 niveles) |
| ProductionBoard | Sort por prioridad, badge coloreado, group title con prioridad |
| Order Wizard | Select de prioridad en Step 4 (Extras) |

### Fuera de alcance

- Integracion real con PAC (Facturapi) — solo stub
- Timbrado CFDI real
- XML/PDF de factura

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unitarias/Feature | PHPUnit (composer test) | Models, services, endpoints |
| Manual UI | Chrome DevTools MCP | Paginas Filament, formularios, tables |
| Regresion | PHPUnit suite completa | 215 tests existentes |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase7-premium-pr2 creado desde PR1
- [x] Migrations: invoices table + priority column
- [x] Invoice model + CfdiService implementados
- [x] CashRegister page + vista implementadas
- [x] OrderPriority enum creado
- [x] Order.php con priority fillable + cast
- [x] ProductionBoard con sort por prioridad
- [x] OrderResource con campo priority en wizard
- [x] Tests creados (9 tests en 3 files)

## 5. Criterios de salida

- [x] 215/215 tests pasan (536 assertions)
- [x] Checklist manual completo (16/16 verificaciones)
- [x] 1 bug encontrado y corregido
- [x] Sin regresiones en tests existentes

## 6. Datos de test

### CFDI

| Escenario | Resultado esperado |
|-----------|-------------------|
| Crear factura para orden | Invoice draft con RFC, impuestos 16%, relacion order+tenant |
| Plan Starter intenta facturar | No tiene acceso (feature gate) |

### Corte de Caja

| Escenario | Resultado esperado |
|-----------|-------------------|
| Preset "Hoy" | Payments de hoy agrupados por metodo |
| Preset "Esta semana" | Payments de lunes a hoy |
| Preset "Este mes" | Payments del 1 al dia actual |
| Comparativo periodo anterior | Porcentaje arriba/abajo vs periodo equivalente |

### Prioridad

| Escenario | Resultado esperado |
|-----------|-------------------|
| Crear orden sin prioridad | Default: Normal (3) |
| ProductionBoard con multiples prioridades | Urgente aparece primero |
| Enum OrderPriority | 5 valores: 1-Urgente a 5-Diferida |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| defaultSort con relationship name genera SQL invalido | Alta | Alto | Detectado y corregido: join + table real |
| CFDI sin proveedor PAC | Baja | Bajo | Draft mode aceptable, PAC se integra despues |
| IVA hardcoded 16% | Baja | Bajo | Correcto para Mexico, configurable en futuro |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase7-premium-pr2/test-plan.md` |
| Casos de test | `docs/qa/fase7-premium-pr2/test-cases.md` |
| Resumen QA | `docs/qa/fase7-premium-pr2/test-summary.md` |
