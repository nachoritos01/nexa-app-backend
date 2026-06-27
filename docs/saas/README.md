# SaaS Template — SaaS Plan

> CRM vertical para businesss y negocios de impresion personalizada.

---

## Documentos

| # | Documento | Descripcion |
|---|----------|-------------|
| 1 | [SAAS_VISION.md](1-SAAS_VISION.md) | Propuesta de valor, problemas de businesss, diferenciacion vs CRMs genericos, ICP, por que pagarian y por que no cancelarian |
| 2 | [PRICING_STRATEGY.md](2-PRICING_STRATEGY.md) | Planes (Starter $299, Growth $699, Pro $1,299), limites, add-ons, descuentos, proyecciones de revenue, analisis competitivo |
| 3 | [MULTITENANCY_STRATEGY.md](3-MULTITENANCY_STRATEGY.md) | Column isolation (tenant_id), 5 roles con permisos granulares, onboarding de tenant, migracion del MVP |
| 4 | [SAAS_ARCHITECTURE.md](4-SAAS_ARCHITECTURE.md) | Stack tecnico (Laravel + Filament + Railway), diagrama, auth SaaS, billing (Stripe), observabilidad, security, escalabilidad |
| 5 | [FEATURE_GAP_ANALYSIS.md](5-FEATURE_GAP_ANALYSIS.md) | MVP vs SaaS vendible: 60+ features analizadas, que existe, que falta, impacto en sales, prioridad |
| 6 | [SAAS_METRICS.md](6-SAAS_METRICS.md) | MRR, ARR, Churn, LTV, CAC, Activation, Retention, Health Score, dashboard super-admin |
| 7 | [SAAS_ROADMAP.md](7-SAAS_ROADMAP.md) | Roadmap en 3 fases (18 semanas): Foundation, Monetizacion, Growth. Milestones, beta testing |
| 8 | [DATABASE_SAAS_SCHEMA.md](8-DATABASE_SAAS_SCHEMA.md) | Diagramas Mermaid ER, normalizacion legacy, tables nuevas SaaS, plan de migrations, volumetria |
| 9 | [DEPLOY_STRATEGY.md](9-DEPLOY_STRATEGY.md) | Ambientes (staging/prod), CI/CD (GitHub Actions), Railway multi-service, R2 storage, backups, monitoring |

---

## Orden de Lectura Recommended

1. **Vision** — Por que existe este SaaS
2. **Feature Gap** — Que hay y que falta
3. **Database Schema** — Como se estructura la data
4. **Multitenancy** — Como aislar datos entre businesss
5. **Architecture** — Stack tecnico completo
6. **Deploy** — Como se despliega y opera
7. **Pricing** — Como cobrar
8. **Metrics** — Como medir exito
9. **Roadmap** — Que hacer y cuando

---

## Estado

- **MVP:** Completado y deployado (SaaS Template en Railway)
- **SaaS:** Fase de planificacion
- **Siguiente paso:** Fase 1 del Roadmap (Multi-tenancy + Roles)

---

## Revision de Consistencia (2026-02-17)

Se realizo una revision completa de los 9 documentos. Se encontraron y corrigieron 14 inconsistencias criticas:

| # | Doc | Issue | Fix |
|---|-----|-------|-----|
| 1 | **Pricing** | ARPU $499 vs weighted $589 sin explicar | Note: early adopter/annual discounts bajan el ARPU blended |
| 2 | **Metrics** | LTV usaba 8% churn ($6,237) vs Pricing 10% ($5,000) | Metrics usa 10% churn consistente ($4,990) |
| 3 | **Multitenancy** | Sistema dual de roles confuso (Spatie + tenant_user.role) | Clarificado: `tenant_user.role` es referencia rapida, Spatie maneja permisos reales, rol se reasigna al cambiar tenant |
| 4 | **Architecture** | Credenciales Payment Gateway por tenant no cubiertas | Seccion "Payment Gateway Multi-Tenant": keys encriptadas en `tenants.settings`, paso optional en onboarding |
| 5 | **DB Schema** | Diagrama ER final was missingn 7 entidades | Agregadas: item_categories, colors, sizes, product_sizes, customer_addresses, dimension_limits, business_configs |
| 6 | **DB Schema** | Migracion paso 7 dice NOT NULL en todas las tables, conflicto con catalogo hibrido | Separado: NOT NULL para tables de negocio, NULLABLE para catalogo (item_categories, sizes, colors, dimension_limits) |
| 7 | **Roadmap** | Tarea 1.3 was missingn tables + listaba tables heredadas incorrectamente | Agregadas item_categories, sizes, colors, dimension_limits. Aclarado que order_lines/payments heredan via FK |
| 8 | **Roadmap** | Typo "cobranzo" | Corregido a "cobrando" |
| 9 | **Architecture** | Name de table inconsistente: "audit_logs" vs "activity_log" | Estandarizado a "activity_log" (default de Spatie) |
| 10 | **Architecture** | Schema de audit trail no coincidia con Spatie Activitylog | Actualizado a columns reales de Spatie |
| 11 | **Multitenancy** | Missing flujo de suspension/reactivacion de tenants | Agregado: triggers, efectos (portal sigue vivo), reactivacion, retencion 90 dias |
| 12 | **DB Schema** | Table `feature_usage` referenciada en Metrics pero no definida | Agregada definicion + entrada en diagrama ER |
| 13 | **Architecture** | Panel super-admin no especificado en ningun lado | Agregada seccion 7: panel Filament separado, `is_super_admin`, impersonacion |
| 14 | **Architecture** | Numeracion de secciones rota despues de insercion | Corregida seccion 8 → 9 |

### Fixes previos (primera pasada)

| # | Doc | Fix |
|---|-----|-----|
| 1 | **Vision** | Rango de precio corregido $299-999 → $299-1,299 |
| 2 | **Pricing** | "Admin unico" → "Owner unico" en plan Starter |
| 3 | **Gap Analysis** | Esfuerzo estimado corregido 10-14 → 16-21 semanas |
| 4 | **Gap Analysis** | Fases corregidas: Reportes (1→2), CFDI (2→3), Corte de caja (2→3) |

---

*Ultima actualizacion: 2026-02-17*
