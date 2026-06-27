# SaaS Roadmap: SaaS Template

> De MVP interno a SaaS vendible en 3 fases.

---

## Timeline General

```
Fase 1 — SaaS Foundation     [Semanas 1-6]
Fase 2 — Monetizacion        [Semanas 7-10]
Fase 3 — Growth Engine       [Semanas 11-18]
                              ─────────────
                              Total: ~18 semanas
```

---

## Fase 1 — SaaS Foundation (Semanas 1-6)

> Hacer el MVP multi-tenant con roles. Al final de esta fase: N businesss pueden usar el sistema de forma aislada.

### Semana 1-2: Multi-tenancy Core

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 1.1 | Crear model Tenant | Table `tenants`: id, name, slug, plan, owner_id, settings, is_active, trial_ends_at, subscribed_at | Migration + model + factory |
| 1.2 | Table pivot tenant_user | user_id, tenant_id, role. Un user puede estar en N tenants. | Migration + relationships |
| 1.3 | Agregar tenant_id a tables | orders, customers, products, pricing_rules, quotes, branches, business_configs, item_categories, sizes, colors, dimension_limits. No agregar a: order_lines, payments, customer_addresses, product_sizes (heredan via FK padre). | Migrations con default para datos existentes |
| 1.4 | Crear trait BelongsToTenant | Global Scope que filtra por tenant_id. Auto-assign tenant_id al crear. | Trait aplicado a todos los models |
| 1.5 | Tenant middleware | Resolver tenant desde session. Setear en app container. Validar que user pertenece al tenant. | Middleware registrado en routes |
| 1.6 | Migrar datos existentes | Crear tenant "SaaS Template", asignar tenant_id a todos los registrations existentes. | Datos existentes siguen funcionando |

### Semana 3-4: Roles y Permisos

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 2.1 | Instalar Spatie Permission | `composer require spatie/laravel-permission` + migrations | Package instalado y configurado |
| 2.2 | Definir roles y permisos | 5 roles (Owner, Admin, Sales, Operations, Finance) + permisos granulares | Seeder con roles y permisos |
| 2.3 | Integrar con Filament | Filament Shield o policies manuales. Cada resource respeta permisos del user. | Sales no puede editar items. Operations solo ve tablero. |
| 2.4 | Tenant switcher | Si user tiene >1 tenant, mostrar selector. Cambiar tenant en session. | UI funcional en header de Filament |
| 2.5 | Eliminar admin_emails | Reemplazar `canAccessPanel()` con check de rol via Spatie. | config('app.admin_emails') ya no se usa |
| 2.6 | Actualizar portal customer | Customer portal filtra por tenant_id del customer. | Customers solo ven orders de su business |

### Semana 5-6: Registration y Onboarding

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 3.1 | Pagina de registration | Form: name, email, password, name del negocio, telefono | Crear User + Tenant + tenant_user(owner) |
| 3.2 | Seed data por tenant | Al crear tenant: 5 sizes, 2 item_categories, pricing_rules template, dimension_limits, business_configs default | Tenant funcional en <30s |
| 3.3 | Onboarding wizard | 4 pasos: info negocio, primera location, primer item, primer order guiado | Tenant tiene datos reales al terminar |
| 3.4 | Trial de 14 dias | trial_ends_at = now + 14 days. Banner en dashboard mostrando dias restantes. | Trial funcional sin tarjeta |
| 3.5 | Activity log | Instalar spatie/laravel-activitylog. Log de actions criticas (crear/editar/eliminar) por tenant. | Audit trail visible en admin |
| 3.6 | Tests multi-tenant | Tests que verifican aislamiento: tenant A no ve datos de tenant B. | Suite de tests pasando |

### Entregable Fase 1

- N businesss pueden registrarse y usar el sistema
- Datos completamente aislados entre tenants
- 5 roles funcionando con permisos granulares
- Onboarding guiado para nuevos tenants
- Trial de 14 dias sin tarjeta
- SaaS Template sigue funcionando como tenant #1

---

## Fase 2 — Monetizacion (Semanas 7-10)

> Cobrar suscripciones. Al final de esta fase: customers pagan y el sistema genera MRR.

### Semana 7-8: Stripe + Plans

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 4.1 | Instalar Laravel Cashier | `composer require laravel/cashier` + configuracion Stripe | Cashier funcionando con Stripe test mode |
| 4.2 | Crear planes en Stripe | 3 items: Starter ($299), Growth ($699), Pro ($1,299). Mensual + anual. | Planes creados en Stripe Dashboard |
| 4.3 | Billing page | Pagina `/billing` para Owner: plan actual, upgrade/downgrade, metodo de payment, facturas | UI funcional |
| 4.4 | Stripe Checkout | Click "Upgrade" → redirect a Stripe Checkout → webhook → activar plan | Flujo completo funcionando |
| 4.5 | Webhooks Stripe | subscription.created, invoice.paid, subscription.deleted, invoice.payment_failed | Todos los eventos manejados |
| 4.6 | Plan limits middleware | Middleware que verifica limites del plan (orders/mes, users, locationes, items) | Soft limit con banner + hard limit despues de 7 dias |

### Semana 9-10: Trial Flow + Landing

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 5.1 | Trial expiry flow | Job diario: trials expirados → suspender acceso. Email 3 dias antes, 1 dia antes, dia de expiracion. | Emails enviados, acceso bloqueado |
| 5.2 | Conversion UI | Banner urgente cuando trial expira. Modal de upgrade. Botones de plan en dashboard. | UX clara para convertir |
| 5.3 | Landing page SaaS | Pagina publica: hero, features, testimonials (SaaS Template), planes, CTA. | Landing deployada y funcional |
| 5.4 | Pricing page | Table comparativa de planes. Toggle mensual/anual. CTAs a registration. | Pagina funcional |
| 5.5 | Reportes basicos | Export CSV de orders, payments, customers. Reporte mensual (total orders, ingresos, top items). | Growth y Pro pueden exportar |
| 5.6 | Super-admin dashboard | Panel exclusivo SaaS Template: MRR, tenants activos, trials, churn, health alerts. | Dashboard funcional |

### Entregable Fase 2

- Customers pagan via Stripe (mensual o anual)
- 3 planes con limites enforced
- Trial → Paid flow completo
- Landing page + pricing page publica
- Reportes exportables
- Dashboard de metricas SaaS para el equipo

---

## Fase 3 — Growth Engine (Semanas 11-18)

> Escalar y retener. Al final de esta fase: item listo para growth marketing agresivo.

### Semana 11-12: Retencion

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 6.1 | Health score | Job diario: calcular health score por tenant (login, orders, features). | Score visible en super-admin |
| 6.2 | Alertas proactivas | Email cuando tenant no crea orders en 14 dias. Email cuando usage baja. | Emails automaticos |
| 6.3 | Exit survey | Modal al cancelar: "Por que cancelas?" (precio, features, cerro negocio, otro). Guardar response. | Data de churn recopilada |
| 6.4 | Win-back emails | 7 dias post-cancelacion: email con oferta 1 mes gratis para volver. | Email automatico |
| 6.5 | Referral program | Owner puede invitar a otro negocio. Ambos reciben 1 mes gratis al convertir. | Flujo de referidos funcional |
| 6.6 | Notificaciones automaticas | Messaging automatico al cambiar status de order (no manual). Configurable por tenant. | Notificaciones enviadas automaticamente |

### Semana 13-14: Features Premium

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 7.1 | Branding en PDFs | Cada tenant sube su logo. Logo aparece en PDFs de order y cotizacion. | Logo visible en PDFs |
| 7.2 | Reportes avanzados | Rentabilidad por item, top customers, tendencias de sales, comparativo mensual. | Reportes generados correctamente |
| 7.3 | Facturacion fiscal (CFDI) | Integracion con proveedor PAC (Facturapi o similar). Generar facturas desde order. | Facturas CFDI generadas |
| 7.4 | Corte de caja | Reporte diario: payments por metodo, total del dia, comparativo. | Reporte funcional |
| 7.5 | Cola de operations | Priorizar orders en tablero. Drag & drop o prioridad numerica. | Operations ordenada por prioridad |

### Semana 15-16: Integraciones

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 8.1 | API publica (Sanctum) | REST API autenticada por token. Documentacion con Scramble o similar. | API funcional con docs |
| 8.2 | Webhooks salientes | Notificar sistemas externos cuando cambia status de order. | Webhooks configurables por tenant |
| 8.3 | Shopify sync (exploratorio) | Sync de items y orders desde Shopify. | POC funcional |
| 8.4 | Rate limiting por tenant | Throttle por plan: Starter 60/min, Growth 120/min, Pro 300/min. | Limits enforced |

### Semana 17-18: Optimizacion

| # | Tarea | Detalle | Criterio de aceptacion |
|---|-------|---------|----------------------|
| 9.1 | Performance audit | Query optimization, N+1, caching. Objetivo: p95 < 500ms. | Metricas cumplidas |
| 9.2 | Onboarding optimization | A/B test onboarding. Mejorar activation rate. | Activation >50% |
| 9.3 | Custom domains | tenant.saas-template.app → orders.mi-business.com. SSL via Cloudflare. | Dominios personalizados funcionales |
| 9.4 | Mobile responsive audit | Check que admin y portal funcionan bien en movil. | 100% responsive |
| 9.5 | Security audit | Penetration testing basico. Check tenant isolation. OWASP top 10. | 0 vulnerabilidades criticas |
| 9.6 | Documentacion user | Guias de uso, FAQ, videos tutoriales para onboarding. | Help center basico |

### Entregable Fase 3

- Health scoring y alertas proactivas
- Referral program funcional
- Features premium que justifican upgrade
- API publica documentada
- Facturacion fiscal (CFDI)
- Performance optimizada
- Security audit completado

---

## Resumen de Milestones

```
Semana 2  ✓ Multi-tenancy funcionando
Semana 4  ✓ Roles y permisos completos
Semana 6  ✓ Registration + onboarding + trial → PRIMER BETA TESTER
Semana 8  ✓ Stripe cobrando suscripciones → PRIMER CLIENTE PAGADO
Semana 10 ✓ Landing + pricing + metricas → LANZAMIENTO PUBLICO
Semana 14 ✓ Features premium + CFDI → PRODUCT-MARKET FIT
Semana 18 ✓ API + integraciones + optimizacion → GROWTH READY
```

---

## Dependencias Criticas

| Fase | Depende de | Riesgo | Mitigacion |
|------|-----------|--------|-----------|
| Fase 1 | Nada (build sobre MVP) | Bajo | Datos de SaaS Template como validacion |
| Fase 2 | Cuenta Stripe MX verificada | Medio | Aplicar a Stripe desde Semana 1 |
| Fase 2 | Landing page copy/design | Medio | Usar template, iterar despues |
| Fase 3 | Proveedor PAC para CFDI | Medio | Evaluar Facturapi/Stamping desde Semana 8 |
| Fase 3 | Beta testers (5-10 businesss) | Alto | Empezar a reclutar desde Semana 4 |

---

## Estrategia de Beta Testing

### Reclutamiento (Semana 4-6)

- **Target:** 5-10 businesss en Merida y alrededores
- **Canal:** Contacto directo (redes de SaaS Template), grupos de Facebook de businesss
- **Incentivo:** 3 meses gratis de plan Growth + feedback mensual
- **Criterio:** >20 orders/mes, dolor visible (usa Excel/Messaging), disponible para calls

### Programa Beta (Semana 6-14)

```
Semana 6-7:   Onboarding (videollamada 1:1, configurar negocio)
Semana 8-9:   Uso libre, recopilar feedback semanal
Semana 10-11: Introducir billing, first invoice
Semana 12-13: Feature requests, priorizar
Semana 14:    Conversion a plan pagado o churn
```

### Metricas de exito del beta

- >60% de beta testers crean >10 orders en primer mes
- >50% convierten a plan pagado
- NPS > 40
- Al menos 2 testimonials publicables

---

## Prioridades NO negociables

1. **Tenant isolation es lo primero.** Sin esto no hay SaaS. Cero tolerancia a data leaks.
2. **SaaS Template debe seguir funcionando.** Es el tenant #1 y la validacion del item.
3. **Cobrar lo antes posible.** No esperar a tener todo perfecto. Cobrar desde Fase 2.
4. **No over-engineer.** Column isolation, no schemas separados. Spatie Permission, no custom RBAC. Stripe Checkout, no billing custom.
5. **Metricas desde dia 1.** Si no se mide, no se puede mejorar.

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-17*
