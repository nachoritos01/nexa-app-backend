# Agency API Reference

REST API for the NexaDigital admin panel (`panel-administrativo-nexa-digital`, :3001).
First-party, tenant-scoped module. Separate from the e-commerce v1 API
(see [`v1-reference.md`](v1-reference.md)).

- **Base URL:** `http://localhost:8000/api`
- **Interactive docs (Scribe):** `http://localhost:8000/docs` · OpenAPI: `/docs.openapi` · Postman: `/docs.postman`

## Authentication

Every `/api/agency/*` endpoint requires:

| Header | Value |
|---|---|
| `Authorization` | `Bearer {token}` |
| `X-Tenant-ID` | `{tenantId}` (from the login response) |
| `Accept` | `application/json` |
| `Content-Type` | `application/json` (for POST/PUT) |

### `POST /api/auth/login`
```json
{ "email": "admin@example.com", "password": "admin123", "device_name": "panel-web" }
```
Response:
```json
{
  "user": {
    "id": 1, "name": "Admin", "email": "admin@example.com",
    "role": "owner",
    "permissions": ["agency.view", "agency.manage"]
  },
  "tenants": [ { "id": 1, "name": "MySaaS", "slug": "default", "plan": "pro", "role": "owner" } ],
  "token": "1|xxxxxxxx",
  "token_type": "Bearer"
}
```
Use `token` as the Bearer token and `tenants[0].id` as `X-Tenant-ID`. `user.permissions`
is filtered to the `agency.*` namespace (see RBAC below); `user.role` is the user's global role.

### `GET /api/auth/me`
Same `user` (with `role` + `permissions`) and `tenants` shape as login. Send the Bearer header.

### `POST /api/auth/logout`
Revokes the current token. Send the Bearer header.

## RBAC (agency permissions)

All `/api/agency/*` routes are gated by the `agency.access` middleware:

- Safe methods (`GET`/`HEAD`) require **`agency.view`**.
- Writes (`POST`/`PUT`/`DELETE`) require **`agency.manage`**.

A request lacking the needed permission gets `403 { "error": "...", "code": "FORBIDDEN" }`.
Seeded roles: `owner`/`admin` have both permissions; `ventas`/`produccion`/`contabilidad` have
`agency.view` only (read-only). The panel reads `user.permissions` from login/me to hide or
disable write actions for read-only roles.

## Conventions

- **Envelope:** single resources return `{ "data": { ... } }`; collections return `{ "data": [ ... ] }`.
- **IDs:** server-generated UUID strings. Do **not** send `id` on create.
- **Timestamps:** `createdAt` / `updatedAt` are ISO-8601, server-managed (read-only).
- **Field names:** camelCase, matching the panel's TypeScript types.
- **Partial updates:** `PUT /{id}` accepts any subset of fields; omitted fields are unchanged.
- **Reference ids** (`clientId`, `projectId`, …) are opaque strings (UUIDs of other agency resources).
- **Embedded collections** (`items`, `tasks`, `teamMembers`, …) are JSON arrays stored as-is.

## CRUD pattern (all resources)

Every resource below supports the same five operations:

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/agency/{resource}` | List all (no pagination) → `{ data: [...] }` |
| `POST` | `/api/agency/{resource}` | Create → `201 { data: {...} }` |
| `GET` | `/api/agency/{resource}/{id}` | Show one → `{ data: {...} }` |
| `PUT` | `/api/agency/{resource}/{id}` | Update (partial) → `{ data: {...} }` |
| `DELETE` | `/api/agency/{resource}/{id}` | Delete → `204` (no body) |

Example:
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"admin@example.com","password":"admin123"}' | jq -r .token)

curl -s http://localhost:8000/api/agency/projects \
  -H "Authorization: Bearer $TOKEN" -H 'X-Tenant-ID: 1' -H 'Accept: application/json'
```

## Resources

`{resource}` is one of: `clients`, `projects`, `services`, `suppliers`, `team`,
`quotes`, `invoices`, `expenses`. Below are the writable fields per resource
(plus the read-only `id`, `createdAt`, `updatedAt`).

### `clients` — Client *(Spanish field names)*
| Field | Type | Notes |
|---|---|---|
| `name` | string | **required** (empresa) |
| `contactName` | string | |
| `telefono` | string | |
| `email` | string (email) | |
| `sitioWeb` | string | |
| `industria` | string | |
| `tipo` | string | TipoCliente |
| `origen` | string | OrigenCliente |
| `estado` | string | `Prospecto`\|`Activo`\|`Inactivo`\|`Perdido` |
| `etapaPipeline` | string | `Lead`\|`Contactado`\|`Reunión`\|`Propuesta enviada`\|`Negociación`\|`Ganado`\|`Perdido` |
| `notas` | string | |
| `rating` | int (0–5) | |
| `valorPotencial` | number | |
| `logo` `direccion` `rfc` | string | |
| `fechaCreacion` `fechaActualizacion` | ISO | read-only (mapped to timestamps) |

### `projects` — Project
| Field | Type | Notes |
|---|---|---|
| `name` | string | **required** |
| `description` | string | |
| `clientId` | string | id of a client |
| `type` | string | `web_development`\|`mobile_app`\|`design`\|`branding`\|`marketing`\|`seo`\|`social_media`\|`consulting`\|`other` |
| `status` | string | `pending`\|`in_progress`\|`review`\|`completed`\|`cancelled` |
| `priority` | string | `low`\|`medium`\|`high`\|`urgent` |
| `budget` | number | |
| `startDate` `endDate` | string (YYYY-MM-DD) | |
| `teamMembers` | string[] | team member ids |
| `tasks` | object[] | `{ id, name, status, assignedTo[], createdAt }` |
| `progress` | int (0–100) | |
| `comments` | object[] | `{ id, userId, content, createdAt }` |
| `files` | object[] | `{ id, name, size }` |

### `services` — Service
| `name`* string · `description` string · `category` string · `basePrice` number · `estimatedHours` int · `isActive` bool |

### `suppliers` — Supplier
| `name`* string · `email` · `phone` · `contactName` · `category` · `address` · `website` · `taxId` · `notes` · `isActive` bool |

### `team` — TeamMember
| `name`* string · `email` · `phone` · `role` · `department` · `salary` number · `avatar` · `isActive` bool |

### `quotes` — Quote
| Field | Type | Notes |
|---|---|---|
| `number` | string | **required** |
| `clientId` | string | |
| `date` `validUntil` | string (YYYY-MM-DD) | |
| `status` | string | `draft`\|`sent`\|`accepted`\|`rejected`\|`expired` |
| `items` | object[] | `{ id, description, quantity, unitPrice, total, serviceId? }` |
| `discount` `tax` `subtotal` `total` | number | |
| `terms` `notes` | string | |

### `invoices` — Invoice
| Field | Type | Notes |
|---|---|---|
| `number` | string | **required** |
| `clientId` `projectId` `quoteId` | string | |
| `date` `dueDate` | string (YYYY-MM-DD) | |
| `status` | string | `draft`\|`sent`\|`paid`\|`overdue`\|`cancelled` |
| `items` | object[] | same shape as quote items |
| `subtotal` `tax` `total` | number | |
| `notes` | string | |

### `expenses` — Expense
| Field | Type | Notes |
|---|---|---|
| `description` | string | **required** |
| `amount` | number | |
| `category` | string | `general`\|`payroll`\|`software`\|`marketing`\|`hosting`\|`office`\|`travel`\|`taxes`\|`other` |
| `date` | string (YYYY-MM-DD) | |
| `status` | string | `pending`\|`paid`\|`cancelled` |
| `supplierId` `projectId` | string | |
| `notes` | string | |

## PDF export

Download a rendered PDF (tenant-scoped, `application/pdf`, `Content-Disposition: attachment`).
Reuses `App\Services\Agency\AgencyPdfGenerator` (dompdf) with per-tenant branding.

| Method & path | Returns |
|---|---|
| `GET /api/agency/quotes/{id}/pdf` | `cotizacion_{number}.pdf` |
| `GET /api/agency/invoices/{id}/pdf` | `factura_{number}.pdf` |

A resource that does not belong to the caller's tenant returns **404**.

## Email delivery

Send the document to its client by email, with the PDF attached (reuses the PDF above).

| Method & path | Behaviour |
|---|---|
| `POST /api/agency/quotes/{id}/send` | Emails the quote PDF to the client's email |
| `POST /api/agency/invoices/{id}/send` | Emails the invoice PDF to the client's email |

- **200** `{ "message": "… enviada a <email>" }` on success.
- **422** `{ "message": "El cliente no tiene un email registrado." }` when the client has no email.
- **404** for a resource outside the caller's tenant.

## Settings (singleton, per tenant)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/agency/settings` | Returns `{ data: { ...settings } }` (or `{}` if unset) |
| `PUT` | `/api/agency/settings` | Merges the sent fields into the stored settings |

Fields: `companyName`, `companyEmail`, `companyPhone`, `companyAddress`, `companyWebsite`,
`taxId`, `logo`, `defaultTax`, `defaultPaymentTerms`, `invoicePrefix`, `quotePrefix`,
`invoiceNotes`, `quoteTerms`, `emailNotifications`, `projectUpdates`, `invoiceReminders`,
`quoteExpiry`, `weeklyReports`, `theme`, `language`, `dateFormat`, `currency`.

## Errors

| Status | Meaning |
|---|---|
| `401` | Missing/invalid/expired token (the panel clears the session and redirects to `/login`) |
| `403` | Token not associated with a tenant, or `X-Tenant-ID` for a tenant the user doesn't belong to |
| `404` | Resource not found **within the current tenant** (cross-tenant ids return 404) |
| `422` | Validation error: `{ "message": "...", "errors": { "field": ["..."] } }` |

## Regenerating the interactive docs

```bash
php artisan scribe:generate     # rebuilds /docs, /docs.openapi, /docs.postman
```
Config: `config/scribe.php` (routes limited to `api/agency/*` + `api/auth/*`).
