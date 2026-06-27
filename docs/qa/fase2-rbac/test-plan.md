# Test Plan — SaaS Fase 2: Roles y Permisos (RBAC)

**Version:** 1.0
**Date:** 2026-02-20
**Autor:** QA / Dev Team
**Feature:** RBAC con Spatie Permission v7
**Branch:** feature/saas-phase2-onboarding

---

## 1. Objetivo

Validar que el sistema RBAC restringe correctamente el acceso a recursos, actions y pages de Filament according to el rol asignado al user dentro de su tenant.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Roles | 5 roles: owner, admin, sales, operations, finance |
| Permisos | 15 permisos granulares agrupados en 9 categorias |
| Policies | 9 policies para recursos Filament |
| Sidebar | Visibilidad de menu segun permisos |
| Acciones | Visibilidad de actions en table de orders |
| Wizard | Creacion de orders E2E por rol |
| Tenant Switcher | Cambio de tenant en header |
| Activity Log | Registration de cambios filtrado por tenant |
| Panel Access | canAccessPanel segun membership |

### Fuera de alcance

- Billing (Stripe) — Fase 4
- Registration publico / Onboarding — Fase 3
- Performance / carga — Fase 9
- Security avanzada (CSRF, XSS, SQL injection) — Fase 10

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unitarias / Feature | PHPUnit (`RbacTest.php`) | Permisos por rol, panel access |
| Manuales funcionales | Browser + Chrome DevTools | Sidebar, actions, wizard E2E |
| Regresion | `composer test` (84 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [ ] Spatie Permission v7 instalado y configurado
- [ ] Migrations ejecutadas (tables `roles`, `permissions`, `model_has_roles`, etc.)
- [ ] Seeder ejecutado (`RolesAndPermissionsSeeder`)
- [ ] 9 Policies creadas y auto-descubiertas por Laravel
- [ ] Middleware `EnsureTenant` sincroniza roles desde pivot
- [ ] Users de test creados para cada rol

## 5. Criterios de salida

- [ ] 10/10 tests automatizados pasan (`RbacTest.php`)
- [ ] 84/84 tests de regresion pasan
- [ ] Matriz de tests completada para los 5 roles
- [ ] 0 bugs criticos o bloqueantes abiertos
- [ ] Documentacion actualizada

## 6. Datos de test

### Users

| User | Email | Password | Rol | Tenant |
|---------|-------|----------|-----|--------|
| Admin (owner) | admin@example.com | (env) | owner | SaaS Template |
| Sales Test | sales@test.com | password | sales | SaaS Template |
| Operations Test | (por crear) | password | operations | SaaS Template |
| Finance Test | (por crear) | password | finance | SaaS Template |
| Admin Test | (por crear) | password | admin | SaaS Template |

### Datos de order (wizard)

| Campo | Valor |
|-------|-------|
| Telefono customer | 5551112222 |
| Name | Customer Sales Test |
| Email | customersales@test.com |
| Item | Product Basica Colors |
| Talla | Mediana |
| Color | Blanco |
| Cantidad | 15 |
| Entrega | Recoger en location (Location Principal) |
| Notas | Order de test creado por rol [name_rol] |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Cache de permisos Spatie desactualizado | Media | Alto | `forgetCachedPermissions()` en middleware |
| Policies no auto-descubiertas | Baja | Alto | Convencion `App\Policies\{Model}Policy` |
| Role sync falla si pivot no tiene role | Baja | Alto | Check `if ($pivotRole)` en middleware |
| Sesion activa sin migrations ejecutadas | Media | Medio | `config:clear` + `migrate` en entrypoint |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase2-rbac-test-plan.md` (este file) |
| Test Cases | `docs/qa/fase2-rbac-test-cases.md` |
| Test Matrix | `docs/qa/fase2-rbac-test-matrix.md` |
| Bug Report Template | `docs/qa/bug-report-template.md` |
| Test Summary | `docs/qa/fase2-rbac-test-summary.md` |

## 9. Aprobaciones

| Rol | Name | Fecha | Firma |
|-----|--------|-------|-------|
| QA Lead | | | |
| Dev Lead | | | |
| Product Owner | | | |
