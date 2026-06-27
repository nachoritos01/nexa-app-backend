# SaaS Metrics: SaaS Template

> Metricas clave y como el item debe medirlas.

---

## 1. Metricas Primarias (North Star)

### MRR — Monthly Recurring Revenue

**Que es:** Ingreso mensual recurrente de todas las suscripciones activas.

**Formula:**
```
MRR = SUM(precio_plan de cada tenant activo con suscripcion pagada)
```

**Como medirlo en SaaS Template:**
- Fuente: table `subscriptions` (Laravel Cashier / Stripe)
- Query: sumar `price` de todas las subscriptions con `status = active`
- Excluir: trials, cancelled, past_due
- Incluir: descuentos anuales prorrateados a mensual

**Variantes:**
- **New MRR:** ingreso de customers nuevos este mes
- **Expansion MRR:** upgrades de plan (Starter → Growth)
- **Contraction MRR:** downgrades de plan (Growth → Starter)
- **Churned MRR:** ingreso perdido por cancelaciones

```
Net New MRR = New MRR + Expansion MRR - Contraction MRR - Churned MRR
```

**Objetivo Ano 1:** $50K USD/mes al mes 12

**Donde mostrarlo:** Super-admin dashboard (solo equipo SaaS Template, no tenants)

---

### ARR — Annual Recurring Revenue

**Que es:** MRR x 12. Proyeccion anualizada.

**Formula:**
```
ARR = MRR * 12
```

**Objetivo Ano 1:** $600K USD

---

## 2. Metricas de Retencion

### Churn Rate — Tasa de Cancelacion

**Que es:** Porcentaje de customers que cancelan su suscripcion en un periodo.

**Formula:**
```
Monthly Churn Rate = (Customers que cancelaron en el mes / Customers activos al inicio del mes) * 100
```

**Como medirlo:**
- Fuente: Stripe webhooks (`subscription.deleted`, `subscription.canceled`)
- Table `subscriptions`: contar registrations con `ends_at` en el mes actual
- Separar: churn voluntario (cancelacion) vs involuntario (payment fallido)

**Objetivo:** <10% mensual (Ano 1), <5% mensual (Ano 2)

**Acciones anti-churn:**
1. **Dunning emails:** Stripe envia automaticamente cuando falla el cobro (3 reintentos)
2. **Exit survey:** Al cancelar, preguntar razon (precio, features, competencia, cerro negocio)
3. **Win-back:** Email 7 dias despues de cancelar con oferta de 1 mes gratis
4. **Health score:** Alertar cuando un tenant deja de crear orders por 2+ semanas

---

### Revenue Churn (Churn de ingreso)

**Formula:**
```
Revenue Churn = Churned MRR / MRR al inicio del mes * 100
```

**Por que importa:** Un customer Pro que cancela ($1,299) duele mas que un Starter ($299).

**Objetivo:** <8% mensual revenue churn

---

### Net Revenue Retention (NRR)

**Que es:** Cuanto ingreso retienes + expandes de customers existentes.

**Formula:**
```
NRR = (MRR inicio + Expansion - Contraction - Churn) / MRR inicio * 100
```

**Interpretacion:**
- NRR > 100% = creces sin adquirir customers nuevos (excelente)
- NRR = 90-100% = crecimiento moderado
- NRR < 90% = problema de retencion

**Objetivo:** >95% NRR en Ano 1

---

## 3. Metricas de Adquisicion

### CAC — Customer Acquisition Cost

**Que es:** Cuanto cuesta adquirir un customer pagado.

**Formula:**
```
CAC = (Gasto total en marketing + sales en el periodo) / Customers nuevos pagados en el periodo
```

**Como medirlo:**
- Fuente: gastos de ads (Facebook, Google) + salarios de sales + herramientas
- Dividir entre customers que pasaron de trial a pagado

**Objetivo:** <$1,000 USD

**Canales esperados:**
| Canal | CAC estimado | Volumen |
|-------|:------------:|:-------:|
| Referidos | $0-200 USD | Bajo |
| Contenido organico (SEO/YT) | $300-500 USD | Medio |
| Facebook Ads | $500-1,000 USD | Alto |
| Google Ads | $800-1,500 USD | Medio |
| Sales directas | $1,000-2,000 USD | Bajo |

---

### LTV — Lifetime Value

**Que es:** Ingreso total esperado de un customer durante toda su relacion.

**Formula:**
```
LTV = ARPU / Monthly Churn Rate
```

**Example:** Si ARPU = $499 USD y churn = 10%:
```
LTV = $499 / 0.10 = $4,990 USD (~10 meses promedio)
```

**Objetivo:** LTV > $5,000 USD (requiere churn <10% o ARPU >$500)

---

### LTV/CAC Ratio

**Que es:** Relacion entre el valor del customer y el costo de adquirirlo.

**Formula:**
```
LTV/CAC = LTV / CAC
```

**Interpretacion:**
- LTV/CAC < 1x = pierdes dinero
- LTV/CAC = 3x = saludable
- LTV/CAC > 5x = puedes invertir mas en adquisicion

**Objetivo:** >5x

---

### Payback Period

**Que es:** Meses para recuperar el costo de adquirir un customer.

**Formula:**
```
Payback = CAC / ARPU
```

**Example:** CAC = $1,000, ARPU = $499 → Payback = ~2 meses

**Objetivo:** <3 meses

---

## 4. Metricas de Activacion

### Trial-to-Paid Conversion Rate

**Que es:** Porcentaje de trials que se convierten en customers pagados.

**Formula:**
```
Conversion = (Trials convertidos a pagado / Trials iniciados) * 100
```

**Como medirlo:**
- Fuente: table `tenants` (trial_ends_at vs subscribed_at)
- Tenant con `subscribed_at IS NOT NULL` = convertido
- Tenant con `trial_ends_at < now() AND subscribed_at IS NULL` = churned trial

**Objetivo:** >25%

**Factores de conversion:**
- Onboarding completado (>80% → 3x mas probable de convertir)
- Crear primer order en primeras 48h
- Agregar al menos 5 items
- Invitar a 1 user adicional

---

### Activation Rate

**Que es:** Porcentaje de trials que llegan al "momento aha" — el punto donde el user entiende el valor.

**"Momento Aha" de SaaS Template:** Crear el primer order completo y compartir el PDF por Messaging.

**Formula:**
```
Activation = (Trials que crearon ≥1 order en primeros 7 dias / Trials totales) * 100
```

**Hitos de activacion:**

| Hito | Descripcion | % Target |
|------|-------------|:--------:|
| Signup completado | Creo cuenta exitosamente | 100% |
| Onboarding completado | Info de negocio + primera location | 80% |
| Primer item creado | Agrego al menos 1 item | 70% |
| Primer order creado | Completo el wizard de order | 50% |
| PDF compartido | Descargo o envio PDF por Messaging | 40% |
| Segundo order | Uso recurrente confirmado | 30% |
| Primer payment registrado | Registration un payment en el sistema | 25% |

---

### Time to Value (TTV)

**Que es:** Tiempo desde signup hasta el "momento aha".

**Como medirlo:**
```
TTV = timestamp(primer_order_creado) - timestamp(cuenta_creada)
```

**Objetivo:** <24 horas (primer order creado el mismo dia)

---

## 5. Metricas de Engagement

### DAU / MAU Ratio

**Que es:** Users activos diarios sobre users activos mensuales. Mide "stickiness".

**Formula:**
```
Stickiness = DAU / MAU * 100
```

**Como medirlo:**
- DAU: users unicos que hacen login en un dia
- MAU: users unicos que hacen login en 30 dias
- Fuente: table `sessions` o middleware de tracking

**Interpretacion:**
- >50% = el user lo abre casi todos los dias (ideal para business activa)
- 25-50% = uso regular pero no diario
- <25% = uso esporadico (riesgo de churn)

**Objetivo:** >40% (businesss activas abren el sistema a diario)

---

### Feature Adoption

**Que es:** Que porcentaje de tenants usan cada feature.

| Feature | Target Adoption | Impacto en Retencion |
|---------|:---------------:|:--------------------:|
| Wizard de order | >95% | Critico |
| Tablero de operations | >60% | Alto |
| Portal del customer | >40% | Alto |
| Links de payment (Payment Gateway) | >30% | Alto |
| Quotes PDF | >50% | Medio |
| Envios (Envia.com) | >20% | Medio |
| Dashboard metricas | >70% | Medio |

**Como medirlo:**
- Contar tenants que usaron la feature al menos 1 vez en 30 dias
- Table `feature_usage`: tenant_id, feature, used_at

---

## 6. Metricas de Item (por Tenant)

Estas metricas las ve cada business en su propio dashboard:

| Metrica | Formula | Para que sirve |
|---------|---------|---------------|
| Orders este mes | COUNT(orders WHERE month = current) | Volumen |
| Ingresos este mes | SUM(orders.total WHERE month = current) | Revenue |
| Saldo pendiente total | SUM(orders.balance WHERE status != delivered/cancelled) | Cobros |
| Ticket promedio | AVG(orders.total) | Pricing |
| Tiempo promedio de entrega | AVG(delivered_at - created_at) | Operacion |
| Items producidos hoy | COUNT(order_lines WHERE produced_at = today) | Productividad |
| Customers nuevos este mes | COUNT(customers WHERE month = current) | Crecimiento |
| Top items | GROUP BY product ORDER BY COUNT DESC | Inventario |
| Top customers | GROUP BY customer ORDER BY SUM(total) DESC | CRM |
| Conversion de quotes | Quotes → Orders | Sales |

---

## 7. Dashboard de Metricas SaaS (Super-Admin)

Solo visible para el equipo SaaS Template (no para tenants):

```
┌─────────────────────────────────────────────────┐
│  SaaS Template SaaS Metrics                         │
├─────────────────────────────────────────────────┤
│                                                  │
│  MRR: $42,500 USD    ARR: $510,000 USD         │
│  ▲ +12% vs mes anterior                         │
│                                                  │
│  Tenants activos: 85   Trials activos: 12       │
│  Churn este mes: 3 (3.5%)                       │
│  NRR: 97%                                       │
│                                                  │
│  ┌──────────────────────────────────────┐       │
│  │  MRR Growth (last 12 months)         │       │
│  │  ▁▂▃▃▄▅▅▆▆▇▇█                       │       │
│  └──────────────────────────────────────┘       │
│                                                  │
│  Top Tenants por MRR:                           │
│  1. Imprenta XYZ — Pro — $1,299/mes             │
│  2. SaaS Cancun — Growth — $699/mes              │
│  3. Promo Express — Growth — $699/mes           │
│                                                  │
│  Trials por convertir (expiran pronto):          │
│  - Estampados MX — 3 dias — 12 orders          │
│  - PrintHouse — 5 dias — 3 orders              │
│                                                  │
│  Health Alerts:                                  │
│  ⚠ Imprenta ABC — 0 orders en 14 dias          │
│  ⚠ Copy Center — trial expira manana, 0 orders │
│                                                  │
└─────────────────────────────────────────────────┘
```

---

## 8. Instrumentacion

### Como implementar el tracking

| Metrica | Fuente | Metodo |
|---------|--------|--------|
| MRR / ARR | Stripe API | `Subscription::active()->sum('price')` |
| Churn | Stripe webhooks | `subscription.deleted` event |
| CAC | Google Ads + FB Ads API | Manual o via attribution tool |
| LTV | Calculated | `ARPU / churn_rate` |
| Trial conversion | DB query | `tenants WHERE subscribed_at IS NOT NULL` |
| Activation | DB query | `orders WHERE tenant created < 7 days ago` |
| DAU/MAU | Session middleware | Log `user_id + tenant_id + date` en table analytics |
| Feature adoption | Event tracking | Log feature usage en table `feature_usage` |
| Health score | Scheduled job | Artisan command diario que calcula score por tenant |

### Health Score por Tenant

```
Health Score = weighted average of:
  - Login reciente (30%): login en ultimos 7 dias = 100, 14d = 50, 30d = 0
  - Orders recientes (30%): orders en ultimos 7 dias = 100, 14d = 50, 30d = 0
  - Features usadas (20%): % de features core usadas en 30 dias
  - Users activos (20%): % de users del tenant que hicieron login en 30 dias

Score:
  80-100 = Saludable (verde)
  50-79 = En riesgo (amarillo) → email proactivo
  0-49 = Critico (rojo) → llamada del equipo
```

---

*Documento creado: 2026-02-16*
*Ultima actualizacion: 2026-02-17*
