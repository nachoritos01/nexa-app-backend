# SaaS Template API v1 Reference

## Authentication

All API v1 endpoints require a Bearer token. Generate tokens in **Settings > API** within the admin panel.

```
Authorization: Bearer {your-api-token}
```

**Requirements:**
- Pro plan subscription
- Active tenant (not suspended)
- Valid, non-expired token

### Error Responses

| Status | Code | Description |
|--------|------|-------------|
| 401 | `UNAUTHENTICATED` | Missing or invalid token |
| 403 | `INVALID_TOKEN` | Token not associated with a tenant |
| 403 | `TENANT_INACTIVE` | Tenant is suspended or inactive |
| 403 | `PLAN_UPGRADE_REQUIRED` | API access requires Pro plan |
| 429 | — | Rate limit exceeded |

---

## Base URL

```
https://your-app.example.com/api/v1
```

---

## Rate Limits

| Plan | Limit |
|------|-------|
| Pro | 300 requests/min |

Headers included in every response:
- `X-RateLimit-Limit` — Max requests per window
- `X-RateLimit-Remaining` — Remaining requests
- `Retry-After` — Seconds until reset (only on 429)

---

## Pagination

List endpoints return paginated results.

**Query Parameters:**
- `page` — Page number (default: 1)
- `per_page` — Items per page (default: 15, max: 100)

**Response Meta:**
```json
{
  "data": [...],
  "meta": {
    "total": 42,
    "page": 1,
    "per_page": 15,
    "last_page": 3
  }
}
```

---

## Endpoints

### Orders

#### List Orders

```
GET /api/v1/orders
```

**Query Parameters:**
- `status` — Filter by status: `received`, `confirmed`, `in_progress`, `ready`, `delivered`, `cancelled`
- `date_from` — Filter orders created on or after (YYYY-MM-DD)
- `date_to` — Filter orders created on or before (YYYY-MM-DD)
- `per_page` — Items per page (1-100)

**Example:**

```bash
curl -H "Authorization: Bearer {token}" \
  "https://your-app.example.com/api/v1/orders?status=confirmed&per_page=10"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "customer_name": "John Doe",
      "customer_phone": "5551234567",
      "status": "confirmed",
      "total": "250.00",
      "total_paid": "100.00",
      "created_at": "2026-02-20T10:00:00.000000Z",
      "customer": { "id": 1, "name": "John Doe", "phone": "5551234567" },
      "order_lines": [...]
    }
  ],
  "meta": { "total": 1, "page": 1, "per_page": 10, "last_page": 1 }
}
```

#### Get Order

```
GET /api/v1/orders/{id}
```

Returns order with customer, order_lines, and payments.

```bash
curl -H "Authorization: Bearer {token}" \
  "https://your-app.example.com/api/v1/orders/1"
```

#### Create Order

```
POST /api/v1/orders
```

**Body:**
```json
{
  "customer_name": "Jane Smith",
  "customer_phone": "5559876543",
  "customer_email": "jane@email.com",
  "notes": "Urgent delivery",
  "order_lines": [
    {
      "item_id": 1,
      "variant": "Default",
      "quantity": 10,
      "unit_price": 15.00
    }
  ]
}
```

**Required fields:** `customer_name`, `customer_phone`

```bash
curl -X POST -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"Jane","customer_phone":"5559876543"}' \
  "https://your-app.example.com/api/v1/orders"
```

**Response:** `201 Created`

---

### Customers

#### List Customers

```
GET /api/v1/customers
```

**Query Parameters:**
- `search` — Search by name or phone
- `per_page` — Items per page

```bash
curl -H "Authorization: Bearer {token}" \
  "https://your-app.example.com/api/v1/customers?search=john"
```

#### Get Customer

```
GET /api/v1/customers/{id}
```

Returns customer with `orders_count`.

---

### Items

#### List Items

```
GET /api/v1/items
```

**Query Parameters:**
- `active` — Filter active items (default: true)
- `per_page` — Items per page

```bash
curl -H "Authorization: Bearer {token}" \
  "https://your-app.example.com/api/v1/items"
```

#### Get Item

```
GET /api/v1/items/{id}
```

Returns item with category, description, and available variants.

---

### Payments

#### List Payments

```
GET /api/v1/payments
```

**Query Parameters:**
- `order_id` — Filter by order
- `date_from` — Filter by date range start
- `date_to` — Filter by date range end
- `per_page` — Items per page

```bash
curl -H "Authorization: Bearer {token}" \
  "https://your-app.example.com/api/v1/payments?order_id=1"
```

#### Get Payment

```
GET /api/v1/payments/{id}
```

Returns payment with associated order.

---

## Webhooks (Outgoing)

Configure outgoing webhooks in **Settings > Webhooks** to receive real-time notifications.

**Events:**
- `order.created` — New order created
- `order.status_changed` — Order status updated
- `payment.received` — Payment recorded

**Payload format:**
```json
{
  "id": "wh_abc123",
  "event": "order.created",
  "created_at": "2026-02-20T10:00:00Z",
  "data": { ... }
}
```

**Signature verification:**
Each webhook includes an `X-Webhook-Signature` header with an HMAC-SHA256 signature.

```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
$valid = hash_equals($signature, $request->header('X-Webhook-Signature'));
```

---

## Error Format

```json
{
  "error": "Human-readable error message",
  "code": "ERROR_CODE"
}
```

Validation errors (422):
```json
{
  "message": "The customer name field is required.",
  "errors": {
    "customer_name": ["The customer name field is required."]
  }
}
```
