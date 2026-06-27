# Multi-Tenant SaaS Template

Laravel 12 template for multi-tenant SaaS applications. Livewire storefront, Filament admin panel, Stripe billing, plugin marketplace, customer portal, and tenant management — ready to configure via `.env`.

## Features

- **Multi-Tenancy** — Automatic tenant scoping, subdomain routing, tenant-aware models
- **Admin Panel** — Filament 3.3 with dashboard widgets, stats, and charts
- **Plugin Marketplace** — Per-tenant module activation with Stripe billing for paid plugins
- **Customer Portal** — Login, order history, addresses, payment history
- **Billing** — Stripe integration with subscriptions, checkout, plan add-ons, and webhooks
- **Order Management** — Order wizard, status tracking, PDF generation
- **Notifications** — Email notifications for order updates
- **SaaS Landing** — Public landing page with pricing plans

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.4+ |
| Frontend | Blade, Livewire 4.1, Tailwind CSS 4 |
| Admin | Filament 3.3 |
| Database | PostgreSQL 15 |
| Billing | Stripe (via Laravel Cashier) |
| Deploy | Railway (Docker multi-stage) |

## Quick Start

```bash
# Clone and install
git clone <repo-url> my-saas
cd my-saas
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate

# Set up database (PostgreSQL)
php artisan migrate:fresh --seed

# Run
php artisan serve
```

> **Docker DB:** PostgreSQL runs in Docker on port 5433. Prefix commands:
> ```bash
> DB_PORT=5433 php artisan migrate:fresh --seed
> DB_PORT=5433 composer test
> ```

## Configuration

All business-specific values are centralized in `config/business.php` and controlled via `.env` variables.

### To configure for your business:

1. Update `BUSINESS_*` variables in `.env`:

```env
BUSINESS_NAME="My Business"
BUSINESS_SLOGAN="Your slogan here"
BUSINESS_EMAIL=contact@mybusiness.com
BUSINESS_PHONE="+1234567890"
BUSINESS_CITY="Your City"
BUSINESS_STATE="Your State"
BUSINESS_COUNTRY_CODE="US"
```

2. Replace seed data in `database/seeders/ProductSeeder.php` with your products

3. Update brand colors in `resources/css/app.css` (`@theme` block) and run `npm run build`

4. Run `php artisan migrate:fresh --seed`

See `config/business.php` for all available configuration options including business hours, deposit rules, and admin panel color.

## Commands

```bash
php artisan serve          # Development server
composer test              # Run tests (285 tests, 746 assertions)
composer analyse           # PHPStan level 5
composer format            # PHP CS Fixer
php artisan migrate:fresh --seed  # Reset database
```

## Project Structure

```
app/
├── Enums/              # OrderStatus, PaymentMethod, DeliveryType, etc.
├── Filament/           # Admin panel resources and widgets
├── Http/Controllers/   # Web controllers
├── Livewire/           # Interactive components
├── Models/             # Eloquent models with tenant scoping
└── Services/           # PdfGenerator, PluginBillingService, etc.

config/
├── business.php        # Business-specific configuration
├── modules.php         # Feature flags for optional modules
└── saas.php            # SaaS plans and tenant settings

database/
├── migrations/         # Database schema
└── seeders/            # Idempotent seed data
```

## Plugin Marketplace

Tenants can activate/deactivate plugins from the marketplace. Plugins can be:

- **Included** — Bundled with specific plans at no extra cost
- **Free** — Available to all tenants regardless of plan
- **Paid** — Added as Stripe subscription items (billed monthly)

Manage plugins from SuperAdmin (`/super-admin/plugins`). Module visibility is controlled via `config/modules.php` global flags and per-tenant activation.

## Deploy

Ready for Railway deployment with Docker multi-stage build.

```bash
# Required environment variables
DATABASE_URL=postgresql://...
APP_KEY=base64:...
ADMIN_EMAIL=admin@mybusiness.com
ADMIN_PASSWORD=your-password
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
```

See `docs/guides/12-railway-deployment.md` for full deployment guide.

## Documentation

See `docs/` for development guides, API reference, and architecture docs.

## License

All rights reserved.
