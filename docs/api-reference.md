# API Reference

Documentation de la API REST de SaaS Template.

**Base URL:** `http://localhost:8000/api`

## Health Check

```http
GET /api
```

**Response:**
```json
{
  "name": "SaaS Template API",
  "version": "1.0.0",
  "status": "ok",
  "timestamp": "2026-02-03T21:40:53.395136Z"
}
```

---

## Pricing

### Obtener Reglas de Precios

```http
GET /api/pricing/rules
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "model": "basicas",
      "size_type": "standard",
      "min_qty": 1,
      "max_qty": 5,
      "price": 200,
      "label": "1-5 piezas"
    }
  ],
  "count": 12
}
```

### Obtener Niveles de Precio

```http
GET /api/pricing/tiers?model=basicas&size=MD
```

| Parameter | Tipo | Requerido | Valores |
|-----------|------|-----------|---------|
| model | string | No | `basicas`, `premium` |
| size | string | No | `CH`, `MD`, `GD`, `EG`, `XX` |

**Response:**
```json
{
  "model": "basicas",
  "size": "MD",
  "sizeType": "standard",
  "tiers": [
    {"min_qty": 1, "max_qty": 5, "price": 200, "label": "1-5 piezas"},
    {"min_qty": 6, "max_qty": 12, "price": 185, "label": "6-12 piezas"},
    {"min_qty": 13, "max_qty": null, "price": 175, "label": "13+ piezas"}
  ],
  "recommendedRange": {"min": 6, "max": 12}
}
```

### Calcular Precio

```http
GET /api/pricing/calculate?model=basicas&size=MD&quantity=10
```

| Parameter | Tipo | Requerido | Valores |
|-----------|------|-----------|---------|
| model | string | ✅ | `basicas`, `premium` |
| size | string | ✅ | `CH`, `MD`, `GD`, `EG`, `XX` |
| quantity | integer | ✅ | 1-1000 |

**Response:**
```json
{
  "model": "basicas",
  "size": "MD",
  "sizeType": "standard",
  "quantity": 10,
  "unitPrice": 185,
  "subtotal": 1850,
  "deposit50": 925,
  "deliveryDays": 5,
  "timestamp": "2026-02-03T21:41:22.226423Z"
}
```

### Obtener Sugerencia de Ahorro

```http
GET /api/pricing/suggestions?model=basicas&size=MD&quantity=4
```

**Response (con sugerencia):**
```json
{
  "hasSuggestion": true,
  "currentQuantity": 4,
  "suggestedQuantity": 6,
  "currentUnitPrice": 200,
  "newUnitPrice": 185,
  "savingsPerPiece": 15,
  "totalSavings": 90,
  "additionalPieces": 2,
  "message": "Compra 2 pieza(s) more y ahorra $15 por pieza"
}
```

**Response (sin sugerencia):**
```json
{
  "hasSuggestion": false,
  "message": "Ya iss en el mejor precio disponible"
}
```

### Validar Dimensions

```http
POST /api/pricing/validate-dimensions
Content-Type: application/json

{
  "size": "MD",
  "side": "front",
  "width": 25,
  "height": 30
}
```

| Campo | Tipo | Requerido | Valores |
|-------|------|-----------|---------|
| size | string | ✅ | `CH`, `MD`, `GD`, `EG`, `XX` |
| side | string | ✅ | `front`, `back` |
| width | integer | ✅ | 1-100 |
| height | integer | ✅ | 1-100 |

**Response (valid):**
```json
{
  "valid": true,
  "size": "MD",
  "side": "front",
  "requested": {"width": 25, "height": 30},
  "limits": {"max_width": 28, "max_height": 35}
}
```

**Response (invalid - 422):**
```json
{
  "valid": false,
  "error": "Las dimensiones exceden el limit de 28x35 cm"
}
```

---

## Products

### Listar Items

```http
GET /api/products
GET /api/products?technique=DTF
GET /api/products?model=basicas
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Product Basic Blanca",
      "description": "Product de cotton 100%...",
      "technique": "DTF",
      "material": "100% Cotton",
      "sizes": ["CH", "MD", "GD", "EG", "XX"],
      "colors": ["Blanco"],
      "is_active": true,
      "product_model": {"code": "basicas", "name": "Basic"},
      "available_sizes": [...]
    }
  ],
  "count": 5
}
```

### Obtener Item

```http
GET /api/products/{id}
```

### Crear Item

```http
POST /api/products
Content-Type: application/json

{
  "title": "Nueva Product",
  "description": "Description del item",
  "material": "100% Cotton",
  "model_id": 1,
  "sizes": ["CH", "MD", "GD"],
  "colors": ["Blanco", "Negro"]
}
```

### Actualizar Item

```http
PUT /api/products/{id}
Content-Type: application/json

{
  "title": "Title actualizado",
  "is_active": false
}
```

### Eliminar Item

```http
DELETE /api/products/{id}
```

---

## Orders

### Listar Orders

```http
GET /api/orders
GET /api/orders?status=confirmado
GET /api/orders?pending=true
GET /api/orders?active=true
```

### Crear Orden

```http
POST /api/orders
Content-Type: application/json

{
  "customer_name": "Juan Perez",
  "customer_phone": "9991234567",
  "customer_email": "juan@email.com",
  "notes": "Entregar en la tarde",
  "order_lines": [
    {
      "product_id": 1,
      "size": "MD",
      "color": "Blanco",
      "quantity": 5,
      "unit_price": 200
    }
  ]
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "customer_name": "Juan Perez",
    "status": "recibido",
    "total": 1000,
    "deposit": 500,
    "order_lines": [...]
  },
  "message": "Orden creada exitosamente"
}
```

### Cambiar Estado de Orden

```http
PATCH /api/orders/{id}/status
Content-Type: application/json

{
  "status": "confirmado"
}
```

| Estado | Description |
|--------|-------------|
| `received` | Orden recibida |
| `confirmed` | Payment confirmado |
| `in_progress` | En operations |
| `ready` | Listo para entrega |
| `delivered` | Entregado |
| `cancelled` | Cancelado |

---

## Quotes

### Crear Quote

```http
POST /api/quotes
Content-Type: application/json

{
  "model": "basicas",
  "size": "MD",
  "quantity": 10,
  "customer_name": "Juan Perez",
  "customer_phone": "9991234567"
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "payload": {...},
    "unit_price": 185,
    "subtotal": 1850,
    "deposit50": 925
  },
  "pricing": {
    "unitPrice": 185,
    "subtotal": 1850,
    "deposit50": 925
  },
  "message": "Quote creada exitosamente"
}
```

### Descargar PDF

```http
GET /api/quotes/{id}/pdf
```

Retorna file PDF para descarga.

---

## Content

### Listar Contenido Disponible

```http
GET /api/content
```

**Response:**
```json
{
  "data": [
    {"type": "faq", "exists": true, "url": "/api/content/faq"},
    {"type": "policies", "exists": true, "url": "/api/content/policies"}
  ]
}
```

### Obtener Contenido

```http
GET /api/content/faq
GET /api/content/policies
```

**Response:**
```json
{
  "type": "faq",
  "content": "# Preguntas Frecuentes\n\n...",
  "html": "<h1>Preguntas Frecuentes</h1>...",
  "updatedAt": "2026-02-03 15:30:00"
}
```

---

## Codes de Error

| Code | Description |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 400 | Bad Request - Datos invalids |
| 404 | No encontrado |
| 422 | Validation fallida |
| 500 | Error del servidor |

### Example de Error de Validation

```json
{
  "message": "The model field is required.",
  "errors": {
    "model": ["The model field is required."],
    "size": ["The size field is required."]
  }
}
```

---

## Examples con cURL

```bash
# Health check
curl http://localhost:8000/api

# Calcular precio
curl "http://localhost:8000/api/pricing/calculate?model=basicas&size=MD&quantity=10"

# Crear orden
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Juan",
    "customer_phone": "999123456",
    "order_lines": [{"product_id": 1, "size": "MD", "color": "Blanco", "quantity": 5, "unit_price": 200}]
  }'

# Validar dimensiones
curl -X POST http://localhost:8000/api/pricing/validate-dimensions \
  -H "Content-Type: application/json" \
  -d '{"size": "MD", "side": "front", "width": 25, "height": 30}'
```
