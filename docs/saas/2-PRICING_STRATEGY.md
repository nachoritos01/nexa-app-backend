# Pricing Strategy: SaaS Template

> Model de monetizacion para SaaS de businesss.

---

## 1. Estrategia de Monetizacion

### Model: Suscripcion Mensual con Tiers

- **Cobro:** Mensual (con descuento anual del 20%)
- **Moneda:** USD (pesos mexicanos)
- **Pasarela de cobro SaaS:** Stripe (soporta USD, recurrente, facturacion, trials)
- **Trial:** 14 dias gratis, sin tarjeta de credito
- **Cancelacion:** En cualquier momento, sin penalizacion

### Principios de Pricing

1. **Facil de entender** — 3 planes claros, sin precios ocultos
2. **Valor antes que features** — el customer paga por resultados, no por checkboxes
3. **Crece con el customer** — el plan sube cuando el negocio crece
4. **No penalizar por uso** — limites generosos, no por transaccion

---

## 2. Planes

### Table Comparativa

| Caracteristica | **Starter** | **Growth** | **Pro** |
|----------------|:-----------:|:----------:|:-------:|
| **Precio mensual** | $299 USD | $699 USD | $1,299 USD |
| **Precio anual** (por mes) | $239 USD | $559 USD | $1,039 USD |
| **Para quien** | Imprenta 1-3 personas | Imprenta 5-15 personas | Multi-service / multi-location |
| | | | |
| **Users** | 2 | 5 | 15 |
| **Orders por mes** | 50 | 200 | Ilimitados |
| **Locationes** | 1 | 2 | 5 |
| **Items en catalogo** | 20 | 50 | Ilimitados |
| **Customers registrados** | 200 | 1,000 | Ilimitados |
| | | | |
| **Wizard de order** | Si | Si | Si |
| **Tablero de operations** | Si | Si | Si |
| **Calculadora de precios** | Si | Si | Si |
| **PDF de order/cotizacion** | Si | Si | Si |
| **Messaging compartir** | Si | Si | Si |
| **Portal del customer** | Si | Si | Si |
| **Payments (Payment Gateway)** | Si | Si | Si |
| | | | |
| **Dashboard avanzado** | Basico (stats) | Completo (graficas 30d) | Completo + exportar |
| **Quotes con envio** | No | Envia.com (cotizar) | Envia.com (cotizar + guias) |
| **Roles de user** | Owner unico | Owner + Sales + Operations | Todos los roles |
| **Reportes** | No | Mensuales | Avanzados + exportar CSV |
| **Branding personalizado** | No | Logo en PDF | Logo + colors + dominio |
| **Soporte** | Email | Email + Messaging | Prioritario + videollamada |
| **API acceso** | No | No | Si |

### Plan Enterprise (Futuro — no lanzar al inicio)

- **Precio:** Desde $2,499 USD/mes (personalizado)
- **Para:** Franquicias, cadenas de businesss, mayoristas
- **Extras:** Locationes ilimitadas, users ilimitados, SSO, SLA, onboarding dedicado, integraciones custom
- **Model:** Contactar sales

---

## 3. Limites por Plan — Detalle

### Por que estos limites

Los limites estan disenados para que el plan Starter sea suficiente para una business pequena real, y que el upgrade a Growth se sienta natural cuando el negocio crece.

| Limite | Starter (50) | Growth (200) | Pro (Ilimitado) | Justificacion |
|--------|:------------:|:------------:|:---------------:|---------------|
| Orders/mes | 50 | 200 | Sin limite | Imprenta de 1-3 personas hace ~30-50 orders/mes. Si supera 50, ya esta creciendo. |
| Users | 2 | 5 | 15 | Dueno + 1 ayudante. Si necesita mas, tiene equipo. |
| Locationes | 1 | 2 | 5 | Mayoria opera desde 1 ubicacion. 2 locationes = negocio establecido. |
| Items | 20 | 50 | Sin limite | Products SaaS tipicamente: 2 models x 5-10 colors = 10-20 items. |
| Customers | 200 | 1,000 | Sin limite | Base de customers tipica de business pequena: 100-200 customers activos. |

### Que pasa cuando se alcanza el limite

- **Notificacion:** Al 80% del limite se muestra banner en dashboard
- **Soft limit:** Al 100% se puede seguir creando orders por 7 dias (grace period)
- **Hard limit:** Despues de 7 dias, no se pueden crear orders nuevos (si editar existentes)
- **Upgrade path:** Boton "Upgrade" visible en todo momento, con calculo de ahorro

---

## 4. Add-Ons (Futuro — Post-lanzamiento)

Add-ons son features adicionales que se pueden agregar a cualquier plan por un costo extra mensual.

| Add-On | Precio | Descripcion | Disponible desde |
|--------|--------|-------------|-----------------|
| **Location extra** | $199 USD/mes | Agregar 1 location adicional al plan | Growth+ |
| **Users extra** (pack de 3) | $149 USD/mes | 3 users adicionales | Growth+ |
| **Integracion e-commerce** | $299 USD/mes | Sync con Shopify/WooCommerce | Pro |
| **Reportes avanzados** | $199 USD/mes | Reportes de rentabilidad, item mas vendido, customer top | Growth+ |
| **Dominio personalizado** | $99 USD/mes | tu-business.saas-template.app → orders.tu-business.com | Pro |
| **Automatizaciones Messaging** | $199 USD/mes | Mensajes automaticos en cada cambio de status | Growth+ |
| **API acceso** | $399 USD/mes | REST API para integraciones custom | Pro |

### Regla de add-ons

- Los add-ons NO se venden al inicio. Primero se valida demanda.
- Se agregan cuando un customer existente pida la funcionalidad y este dispuesto a pagar.
- Cada add-on debe tener al menos 5 customers potenciales antes de desarrollarlo.

---

## 5. Descuentos y Promociones

### Descuento anual

- **20% de descuento** al pagar anualmente
- Se muestra como "Ahorra $X al ano" en la pagina de pricing

### Early Adopter (primeros 50 customers)

- **50% de descuento de por vida** en el plan que elijan
- Limitado a los primeros 50 suscriptores pagados
- Condicion: dar feedback mensual y aceptar encuestas de item
- Se usa como caso de estudio y testimonial

### Referidos

- **1 mes gratis** por cada referido que se convierta en customer pagado
- El referido tambien recibe **1 mes gratis**
- Sin limite de referidos

---

## 6. Proyecciones de Revenue

### Escenario Conservador (Ano 1)

| Mes | Customers | MRR | Churn | Notas |
|-----|:--------:|:---:|:-----:|-------|
| 1-3 | 10 | $4,990 | 0% | Beta + early adopters |
| 4-6 | 30 | $14,970 | 5% | Primeras sales reales |
| 7-9 | 60 | $29,940 | 8% | Growth marketing |
| 10-12 | 100 | $49,900 | 10% | Estabilizacion |

**ARR estimado Ano 1:** ~$600K USD (~$35K USD)

### Distribucion esperada por plan

| Plan | % Customers | Revenue Share |
|------|:----------:|:-------------:|
| Starter | 50% | 25% |
| Growth | 35% | 40% |
| Pro | 15% | 35% |

### Metricas objetivo Ano 1

| Metrica | Objetivo |
|---------|----------|
| MRR al cierre | $50K USD |
| Customers activos | 100 |
| Churn mensual | <10% |
| LTV promedio | $4,990 USD (~10 meses) |
| CAC | <$1,000 USD |
| LTV/CAC ratio | >5x |
| ARPU | $499 USD |

**Nota sobre ARPU:** El ARPU ponderado sin descuentos seria ~$589 USD (50% Starter + 35% Growth + 15% Pro). Se proyecta $499 como ARPU conservador porque incluye descuentos early adopter (50% para los primeros 50 customers) y descuentos anuales (20%).

---

## 7. Analisis Competitivo de Precio

### Mercado Mexicano — Software para businesss

| Competidor | Tipo | Precio | Limitaciones |
|-----------|------|--------|--------------|
| Excel / Google Sheets | Gratis | $0 | Sin automatizacion, sin portal, sin payments |
| Notion | Productividad | $80-160 USD/mes | No entiende impresion, configuracion manual |
| Monday.com | Gestion proyectos | $400-1,600 USD/mes | Generico, sin pricing engine, sin operations |
| HubSpot CRM | CRM generico | Gratis-$3,200 USD/mes | Pipeline de sales, no operations |
| Odoo | ERP | $600-2,400 USD/mes | Complejo, requiere implementador, overkill |
| **SaaS Template** | **Vertical SaaS** | **$299-1,299 USD/mes** | **Especializado en businesss** |

### Posicion de precio

- **Mas barato que** CRMs genericos y ERPs
- **Mas caro que** herramientas gratuitas (Excel, Messaging)
- **Justificacion:** El valor no es "tener un software", es "recuperar 2-3 horas diarias y dejar de perder dinero"

### ROI para el customer

Una business que pierde 2 orders por semana por desorganizacion pierde ~$1,000-3,000 USD/semana.
SaaS Template a $499 USD/mes se paga solo con 1 order recuperado al mes.

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-17*
