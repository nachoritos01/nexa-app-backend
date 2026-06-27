# Feature Gap Analysis: MVP vs SaaS

> Que tiene el MVP, que falta, y que impacto tiene en sales.

---

## 1. Resumen Ejecutivo

El MVP actual de SaaS Template es un sistema completo de gestion de orders para UNA business. Tiene ~80% de las features que una business necesita para operar. Pero tiene 0% de las features que se necesitan para VENDER como SaaS.

**Lo que existe** es el "item core" — y es fuerte.
**Lo que falta** es la "capa SaaS" — multitenancy, billing, roles, onboarding.

---

## 2. Table de Gap Analysis

### Gestion de Orders

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Wizard de order (5 pasos) | Si | Completo. Customer, items, entrega, extras, resumen. | Alto — diferenciador principal | Ya listo |
| Status lifecycle | Si | Recibido → Confirmado → Operations → Listo → Entregado/Cancelado | Alto — operacion diaria | Ya listo |
| Transiciones automaticas | Si | Payment → confirma. Operations primera pieza → en operations. Ultima pieza → listo. | Alto — ahorra tiempo | Ya listo |
| Items desglosados | Si | OrderLines con item, talla, color, cantidad, precio. | Alto — precision | Ya listo |
| Duplicar order | Si | Copia items sin payments, status recibido. | Medio — convenience | Ya listo |
| Soft delete | Si | Orders no se borran, se archivan. | Medio — auditoria | Ya listo |
| Orders recurrentes | No | GAP. No hay forma de crear orders periodicos automaticamente. | Bajo — pocos customers lo necesitan | Fase 3 |
| Bulk operations | No | GAP. No se pueden actualizar multiples orders a la vez. | Medio — businesss grandes | Fase 2 |

### Operations

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Tablero de operations | Si | Items agrupados por order, toggle producido. | Alto — diferenciador clave | Ya listo |
| Progreso por order | Si | X/Y items producidos, porcentaje. | Alto — visibilidad | Ya listo |
| Auto-transicion de status | Si | Primera pieza → en operations. Ultima → listo. | Alto — automatizacion | Ya listo |
| Asignacion de operador | Parcial | produced_by registra quien marco, pero no hay asignacion previa. | Medio — businesss medianas | Fase 2 |
| Cola de operations (prioridad) | No | GAP. No hay forma de priorizar que order producir primero. | Medio — cuando hay >10 orders en cola | Fase 2 |
| Tiempo de operations | No | GAP. No se mide cuanto tarda cada item/order. | Medio — metricas de eficiencia | Fase 3 |

### Pricing y Quotes

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Motor de precios por reglas | Si | Model + talla + rango de cantidad → precio. | Alto — core del negocio | Ya listo |
| Calculadora publica | Si | Livewire: seleccionar model/talla/cantidad → precio instantaneo. | Alto — conversion de sales | Ya listo |
| Quotes PDF | Si | Generar PDF con datos del negocio, compartir por Messaging. | Alto — cierre de sales | Ya listo |
| Sugerencia de tier | Si | "Agrega 2 mas y ahorra $X por pieza". | Medio — upsell | Ya listo |
| Precios custom por customer | No | GAP. Todos los customers ven el mismo precio. | Medio — businesss B2B con mayoristas | Fase 3 |
| Cotizacion multi-item | No | GAP. Calculadora solo cotiza 1 item a la vez. | Medio — orders complejos | Fase 2 |

### Payments

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Registration de payments | Si | Monto, metodo, referencia, fecha. | Alto — control financiero | Ya listo |
| Payments parciales | Si | Anticipo + liquidacion. Balance calculado automaticamente. | Alto — flujo tipico de businesss | Ya listo |
| Payment Gateway (tarjeta/Bank Transfer/Wire Transfer) | Si | Links de payment con webhook automatico. | Alto — cobra sin perseguir | Ya listo |
| Notificaciones de payment | Si | SMS (Twilio) + Messaging links. | Medio — comunicacion | Ya listo |
| Facturacion fiscal (CFDI) | No | GAP CRITICO. Imprentas necesitan emitir facturas. | Alto — requerimiento legal MX | Fase 3 |
| Corte de caja diario | No | GAP. No hay reporte de payments del dia por metodo. | Medio — control diario | Fase 3 |
| Conciliacion bancaria | No | GAP. No se vinculan payments con estados de cuenta. | Bajo — finance avanzada | Fase 3 |

### Customers

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Registration de customers | Si | Name, telefono, email. | Alto — base de customers | Ya listo |
| Busqueda por telefono | Si | Autocompletado en wizard. | Alto — velocidad | Ya listo |
| Portal del customer | Si | Login, ver orders, payments, direcciones, perfil, PDF. | Alto — reduce Messaging | Ya listo |
| Historial de orders | Si | Lista completa con filtros. | Alto — recompra | Ya listo |
| Direcciones multiples | Si | Hasta 5, con default. | Medio — convenience | Ya listo |
| Notificaciones por status | Parcial | Messaging manual + SMS. No hay push automatico. | Alto — experiencia | Fase 2 |
| Programa de lealtad | No | GAP. Enum LoyaltyTier existe pero no hay logica. | Medio — retencion | Fase 3 |
| CRM (seguimiento de leads) | No | GAP. No hay pipeline de prospectos. | Medio — crecimiento | Fase 3 |

### Envios

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Cotizacion multi-carrier | Si | Envia.com: 6 paqueterias en paralelo. | Alto — ahorra tiempo | Ya listo |
| Generacion de guias | Si | Envia.com: generar guia con tracking. | Alto — operacion | Ya listo |
| Tracking | Si | Envia.com: consultar status de envio. | Alto — seguimiento | Ya listo |
| Pickup en location | Si | Seleccion de location con datos. | Alto — opcion local | Ya listo |
| Etiqueta de envio | Parcial | URL de etiqueta pero sin impresion directa. | Bajo — nice to have | Fase 3 |

### Admin / Dashboard

| Feature | Existe | Estado | Impacto en Sales | Prioridad SaaS |
|---------|:------:|--------|:-----------------:|:--------------:|
| Stats overview | Si | Orders hoy, pendientes, en operations, payments hoy, saldo pendiente. | Alto — vision rapida | Ya listo |
| Grafica de orders (30d) | Si | Line chart diario. | Medio — tendencias | Ya listo |
| Grafica de ingresos (30d) | Si | Bar chart diario. | Medio — tendencias | Ya listo |
| Ultimos orders | Si | Widget con 5 mas recientes. | Medio — acceso rapido | Ya listo |
| Acciones pendientes | Si | Orders que necesitan accion, con next step. | Alto — no olvidar orders | Ya listo |
| Reportes exportables | No | GAP. No hay export CSV/Excel. | Alto — finance | Fase 2 |
| Reporte de rentabilidad | No | GAP. No se sabe que item/customer es mas rentable. | Alto — decisiones de negocio | Fase 2 |

---

## 3. Features SaaS (Gap Total)

Estas features NO existen en absoluto y son requeridas para vender como SaaS:

| Area | Feature | Descripcion | Impacto | Prioridad |
|------|---------|-------------|:-------:|:---------:|
| **Multi-empresa** | Tenant model | Table tenants + tenant_id en tables de negocio | Critico | Fase 1 |
| **Multi-empresa** | Global Scopes | Filtro automatico por tenant_id | Critico | Fase 1 |
| **Multi-empresa** | Tenant middleware | Validar tenant en cada request | Critico | Fase 1 |
| **Multi-empresa** | Seed data por tenant | Datos default al crear tenant (tallas, models, precios) | Alto | Fase 1 |
| **Roles** | RBAC | Owner, Admin, Sales, Operations, Finance | Critico | Fase 1 |
| **Roles** | Filament policies | Permisos en panel admin por rol | Critico | Fase 1 |
| **Roles** | Tenant switcher | UI para cambiar de tenant si user tiene multiples | Medio | Fase 1 |
| **Auth SaaS** | Registration de cuenta | Formulario publico de registration | Critico | Fase 1 |
| **Auth SaaS** | Onboarding wizard | Steps guiados para configurar negocio nuevo | Alto | Fase 1 |
| **Auth SaaS** | Email verification | Check email al registrar | Medio | Fase 1 |
| **Billing** | Stripe integration | Laravel Cashier + Stripe Checkout | Critico | Fase 2 |
| **Billing** | Plan management | Seleccionar/cambiar plan desde UI | Critico | Fase 2 |
| **Billing** | Plan limits enforcement | Middleware que chequea limites del plan | Alto | Fase 2 |
| **Billing** | Trial management | 14 dias gratis, conversion a pagado | Alto | Fase 2 |
| **Billing** | Invoicing | Facturas automaticas de Stripe | Medio | Fase 2 |
| **Metricas** | MRR tracking | Calcular MRR desde subscriptions | Alto | Fase 2 |
| **Metricas** | Churn tracking | Detectar cancelaciones y razones | Alto | Fase 2 |
| **Metricas** | Usage analytics | Orders/mes por tenant, features usadas | Medio | Fase 2 |
| **Onboarding** | Landing page SaaS | Pagina publica de venta del item | Alto | Fase 2 |
| **Onboarding** | Pricing page | Pagina de planes con CTA | Alto | Fase 2 |
| **Onboarding** | Trial flow | Registration → trial → onboarding → uso → conversion | Alto | Fase 2 |
| **Auditoria** | Activity log | Registration de actions por user por tenant | Medio | Fase 1 |
| **Auditoria** | Data export | Exportar datos del tenant (CSV) | Medio | Fase 2 |
| **Security** | API auth (Sanctum) | Tokens de API por tenant | Medio | Fase 3 |
| **Security** | Rate limiting por tenant | Throttle basado en plan | Medio | Fase 2 |
| **Branding** | Logo en PDFs | Cada tenant con su logo | Medio | Fase 2 |
| **Branding** | Colors custom | Palette del tenant en portal | Bajo | Fase 3 |
| **Branding** | Custom domain | tenant.saas-template.app → orders.mi-business.com | Bajo | Fase 3 |

---

## 4. Resumen de Reutilizabilidad del MVP

### Features 100% reutilizables (solo agregar tenant_id)

- Order wizard (5 pasos)
- Order lifecycle + transitions
- Production board
- Pricing engine
- PDF generation (quotes + orders)
- Customer portal
- Payment tracking (Payment Gateway)
- Shipping integration (Envia.com)
- Dashboard widgets
- Product catalog
- Quote calculator
- Messaging integration

### Features que necesitan modificacion

| Feature | Modificacion |
|---------|-------------|
| User auth | Agregar tenant_user pivot + roles |
| Filament panel | Integrar tenant scope + Shield para permisos |
| Customer auth | Agregar tenant_id a customers |
| Business configs | Scope por tenant_id |
| Seeders | Hacer parametricos por tenant |
| API endpoints | Agregar Sanctum + tenant scope |

### Features que NO sirven para SaaS

| Feature | Por que | Reemplazo |
|---------|---------|-----------|
| `config('app.admin_emails')` | Hardcodeado para 1 empresa | RBAC con roles |
| Single DB sin tenant_id | No aisla datos | Column isolation |
| Public pages (home, catalogo) | Son de SaaS Template, no de SaaS Template | Landing page SaaS separada |

---

## 5. Esfuerzo Estimado

| Fase | Features | Esfuerzo | Dependencias |
|------|----------|----------|-------------|
| Fase 1 — Foundation | Multi-tenant + roles + onboarding | 6-8 semanas | Ninguna |
| Fase 2 — Monetizacion | Stripe + plans + trials + landing | 4-5 semanas | Fase 1 |
| Fase 3 — Growth | Reportes + API + branding + integraciones | 6-8 semanas | Fase 2 |

**Total estimado para SaaS vendible:** 16-21 semanas de desarrollo enfocado.

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-16*
