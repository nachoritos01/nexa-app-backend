# System Architecture

## Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      Browser                                 │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   Laravel Application                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Routes    │→ │ Controllers │→ │      Services       │  │
│  │  web.php    │  │ Page        │  │ PdfGenerator        │  │
│  │  api.php    │  │ CustomerAuth│  │ TenantSeedService   │  │
│  │             │  │ Portal      │  │ WebhookService      │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│         │                │                    │              │
│         ▼                ▼                    ▼              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Livewire   │  │   Models    │  │     Eloquent        │  │
│  │ Components  │  │  Eloquent   │  │       ORM           │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   PostgreSQL (Docker)                        │
│                     saas_template                            │
└─────────────────────────────────────────────────────────────┘
```

## Components

### Frontend (Blade + Livewire)
- **Template Engine**: Blade
- **Interactivity**: Livewire 4.1
- **Styling**: Tailwind CSS
- **Icons**: Heroicons (Filament default)

### Backend (Laravel)
- **Framework**: Laravel 12.49.0
- **ORM**: Eloquent
- **PDF**: DomPDF 3.1.1
- **Analysis**: PHPStan + Larastan

### Database
- **Type**: PostgreSQL 15
- **Container**: saas-postgres
- **Connection**: localhost:5432

## Data Flow

### Quote Creation
```
User Input → Filament Resource → Quote Model → Database → PDF Generation
```

### Order Creation
```
Filament Form → Order Model → OrderLines → Database → Confirmation
```

## Models & Relationships

```
Tenant (multi-tenant root, uses Billable)
├── name, slug, plan, referral_code, owner_id
├── settings[], is_active
├── trial_ends_at, subscribed_at
├── onboarding_steps[], onboarding_completed_at
├── referral_bonus_days, health_score, health_score_calculated_at
├── stripe_id, pm_type, pm_last_four
├── Relations: owner(), users() (M2M pivot role), orders(), customers(),
│   items(), locations(), featureUsages(), cancellationSurveys(),
│   referrals(), referredBy()
└── Helpers: isOnTrial(), isSubscribed(), planLimits(), usageCounts(), isAtLimit()

User (Filament admin, implements FilamentUser)
├── name, email, password (hashed), is_super_admin
├── last_login_at, push_token
├── Relations: tenants() (M2M with pivot role)
└── Traits: HasRoles (Spatie), HasApiTokens (Sanctum)

Item (BelongsToTenant, SoftDeletes)
├── tenant_id, name, description, category, sku
├── price (decimal:2), variants[], photos[], tags[], metadata[]
├── is_active, sort_order
├── Scopes: active(), byCategory(), ordered()
└── Accessors: photoUrls, mainPhoto

Location (BelongsToTenant)
├── tenant_id, name, type, address, city, state, zip, country
├── phone, email, schedule
├── lat (decimal:7), lng (decimal:7), maps_url, metadata[]
├── is_active
├── Relations: orders()
└── Scopes: active(), byType()

Order (BelongsToTenant, SoftDeletes)
├── tenant_id, customer_id (FK), location_id (FK)
├── customer_name, customer_phone, customer_email
├── status (OrderStatus enum), priority (OrderPriority enum)
├── subtotal, discount_amount, tax, total, total_paid (all decimal:2)
├── paid_at, estimated_at, confirmed_at, completed_at, cancelled_at
├── notes, attachments[], metadata[]
├── Relations: customer(), location(), lines(), payments(), invoices()
├── Scopes: byStatus(), active()
├── State: confirm(), complete(), cancel()
└── Helpers: recalculateTotals(), syncTotalPaid(), duplicate()

OrderLine (BelongsToTenant)
├── tenant_id, order_id (FK), item_id (FK)
├── description, variant
├── quantity (int), unit_price (decimal:2), subtotal (decimal:2)
├── metadata[]
├── Relations: order(), item()
└── Boot: saving() calculates subtotal = quantity * unit_price

Quote (BelongsToTenant)
├── tenant_id, customer_id (FK), order_id (FK)
├── customer_name, customer_phone, customer_email, title
├── items[] (JSON), subtotal, tax, discount, total (all decimal:2)
├── notes, metadata[], expires_at, accepted_at
├── Relations: customer(), order()
└── Accessors: isExpired, isAccepted

Customer (extends Authenticatable, BelongsToTenant)
├── tenant_id, name, phone, email, password (hashed)
├── notes, tags[], metadata[], referral_code, referred_by
├── birthday (date), is_active
├── Relations: orders(), addresses(), defaultAddress(), payments(),
│   referrer(), referrals()
├── Scopes: search(), active()
└── Accessors: initials

CustomerAddress
├── customer_id (FK → customers)
├── label, street, district, city, state, zip, country
├── references, metadata[], is_default
└── Relation: customer()

Invoice (BelongsToTenant)
├── tenant_id, order_id (FK), uuid, number, status
├── customer_name, customer_email, customer_tax_id
├── subtotal, tax, total (all decimal:2)
├── pdf_path, metadata[], issued_at, cancelled_at
├── Relations: order(), tenant()
└── Helpers: isIssued(), isCancelled()

Payment (BelongsToTenant)
├── tenant_id, order_id (FK)
├── amount (decimal:2), method (PaymentMethod enum), reference
├── gateway_id, payment_type, notes, metadata[]
├── received_at, received_by (FK → users)
├── Relations: order(), receivedByUser()
└── Boot: saved() syncs total_paid on Order + dispatches PaymentReceived

BusinessConfig
├── key, value, type, group
└── Static: get(), set(), getGroup()
```

## Authentication

| Guard | Provider | Model | Purpose |
|-------|----------|-------|---------|
| `web` | users | User | Admin panel (Filament, multi-tenant) |
| `customer` | customers | Customer | Customer portal (/my-account) |

## Security

- **CSRF**: Laravel default
- **XSS**: Blade escaping
- **SQL Injection**: Eloquent parameterized queries
- **Admin Auth**: Filament (guard web, multi-tenant)
- **Customer Auth**: Guard customer (phone + password)
- **API Auth**: Sanctum (HasApiTokens on User)
- **Roles/Permissions**: Spatie laravel-permission (HasRoles on User)
- **Tenant Isolation**: BelongsToTenant trait with global scopes

## External Integrations

| Service | Purpose | Status |
|---------|---------|--------|
| Stripe (Cashier) | Subscriptions & billing (SaaS plans) | Active |
| Railway | Hosting (Docker) | Deployed |

---
*Update when architecture changes.*
