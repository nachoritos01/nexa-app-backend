# Documentation Index

Complete documentation index for the project. Full navigable index at [docs/README.md](../../docs/README.md).

## Development Guides

| File | Contents |
|---|---|
| [docs/guides/01-getting-started.md](../../docs/guides/01-getting-started.md) | Environment setup, requirements, first steps |
| [docs/guides/02-architecture.md](../../docs/guides/02-architecture.md) | Project structure and patterns |
| [docs/guides/03-models-database.md](../../docs/guides/03-models-database.md) | Eloquent models, relationships, migrations |
| [docs/guides/04-api-development.md](../../docs/guides/04-api-development.md) | REST API, controllers, routes, JSON responses |
| [docs/guides/05-livewire-components.md](../../docs/guides/05-livewire-components.md) | Livewire components, properties, computed |
| [docs/guides/06-services-logic.md](../../docs/guides/06-services-logic.md) | PdfGenerator, business logic services |
| [docs/guides/07-testing.md](../../docs/guides/07-testing.md) | Unit and integration tests |
| [docs/guides/08-best-practices.md](../../docs/guides/08-best-practices.md) | Conventions, standards, best practices |
| [docs/guides/09-commands-reference.md](../../docs/guides/09-commands-reference.md) | Artisan commands and scripts |
| [docs/guides/10-filament-admin.md](../../docs/guides/10-filament-admin.md) | Filament admin panel: resources, customization |
| [docs/guides/11-git-troubleshooting.md](../../docs/guides/11-git-troubleshooting.md) | Git error resolution |
| [docs/guides/12-railway-deployment.md](../../docs/guides/12-railway-deployment.md) | Railway deploy: Docker, PostgreSQL, variables |
| [docs/guides/13-dbeaver-railway-postgres.md](../../docs/guides/13-dbeaver-railway-postgres.md) | DBeaver connection to Railway PostgreSQL |
| [docs/guides/14-database-backup-restore.md](../../docs/guides/14-database-backup-restore.md) | DB backup and restore (pg_dump, DBeaver) |
| [docs/guides/17-stripe-cli-setup.md](../../docs/guides/17-stripe-cli-setup.md) | Stripe CLI setup |
| [docs/guides/18-stripe-products-setup.md](../../docs/guides/18-stripe-products-setup.md) | Stripe products and pricing setup |
| [docs/guides/19-aws-route53-railway-domain.md](../../docs/guides/19-aws-route53-railway-domain.md) | AWS Route53 + Railway domain setup |

## Reference

| File | Contents |
|---|---|
| [docs/api-reference.md](../../docs/api-reference.md) | Complete API endpoints with examples |
| [docs/api/v1-reference.md](../../docs/api/v1-reference.md) | V1 API reference |
| [docs/design-system.md](../../docs/design-system.md) | Color palette, gradients, UI/UX components |
| [docs/filament-guide.md](../../docs/filament-guide.md) | Filament panel guide |
| [docs/claude-context-guide.md](../../docs/claude-context-guide.md) | Claude context files guide |

## Feature Docs

### Implemented

| File | Contents |
|---|---|
| [18-customer-portal.done.md](../../docs/features/18-customer-portal.done.md) | Customer portal (My Account) |
| [23-rbac-roles-permissions.done.md](../../docs/features/23-rbac-roles-permissions.done.md) | RBAC roles and permissions |
| [24-registration-onboarding.done.md](../../docs/features/24-registration-onboarding.done.md) | Registration and onboarding flow |
| [25-billing-plan-swap.alta.done.md](../../docs/features/25-billing-plan-swap.alta.done.md) | Billing and plan swap |
| [26-team-management.done.md](../../docs/features/26-team-management.done.md) | Team management |
| [27-activate-module-flags.done.md](../../docs/features/27-activate-module-flags.done.md) | Module feature flags |
| [27-plugin-marketplace-billing.done.md](../../docs/features/27-plugin-marketplace-billing.done.md) | Plugin marketplace + billing |
| [28-loyalty-program.done.md](../../docs/features/28-loyalty-program.done.md) | Loyalty program (points, tiers, rewards) |
| [29-super-admin-tenant-plugins.done.md](../../docs/features/29-super-admin-tenant-plugins.done.md) | SuperAdmin plugin management |
| [30-billing-history.done.md](../../docs/features/30-billing-history.done.md) | Billing event history |

### Audit Findings — Pending

| File | Priority | Area | Contents |
|---|---|---|---|
| [31-fix-subscription-fk-constraints.md](../../docs/features/31-fix-subscription-fk-constraints.md) | HIGH | Database | Missing FK constraints |
| [32-fix-revenue-chart-n-queries.md](../../docs/features/32-fix-revenue-chart-n-queries.md) | HIGH | Performance | RevenueChart 30 queries |
| [33-fix-tenant-resource-n-plus-1.md](../../docs/features/33-fix-tenant-resource-n-plus-1.md) | HIGH | Performance | TenantResource N+1 plugins |
| [34-fix-tenant-growth-chart-queries.md](../../docs/features/34-fix-tenant-growth-chart-queries.md) | HIGH | Performance | TenantGrowthChart 8 queries |
| [35-add-loyalty-service-tests.md](../../docs/features/35-add-loyalty-service-tests.md) | HIGH | Tests | LoyaltyService tests |
| [36-add-customer-portal-tests.md](../../docs/features/36-add-customer-portal-tests.md) | HIGH | Tests | CustomerPortalController tests |
| [37-fix-customer-auth-tenant-scope.md](../../docs/features/37-fix-customer-auth-tenant-scope.md) | MEDIUM | Security | Cross-tenant login |
| [38-fix-personal-access-token-scope.md](../../docs/features/38-fix-personal-access-token-scope.md) | MEDIUM | Security | Missing BelongsToTenant |
| [39-fix-trial-commands-eager-loading.md](../../docs/features/39-fix-trial-commands-eager-loading.md) | MEDIUM | Performance | N+1 in commands |
| [40-cache-dashboard-widgets.md](../../docs/features/40-cache-dashboard-widgets.md) | MEDIUM | Performance | Uncached dashboard stats |
| [41-add-customer-auth-tests.md](../../docs/features/41-add-customer-auth-tests.md) | MEDIUM | Tests | CustomerAuth tests |
| [42-add-webhook-handler-tests.md](../../docs/features/42-add-webhook-handler-tests.md) | MEDIUM | Tests | Webhook handler tests |
| [43-extract-billing-service.md](../../docs/features/43-extract-billing-service.md) | MEDIUM | Code | Extract billing logic to service |
| [44-extract-health-score-config.md](../../docs/features/44-extract-health-score-config.md) | MEDIUM | Code | Hardcoded health thresholds |

## SaaS Architecture

| File | Contents |
|---|---|
| [docs/saas/](../../docs/saas/) | SaaS vision, pricing, multitenancy, architecture, metrics, deploy |

## Conversion Roadmap

| File | Contents |
|---|---|
| [docs/GENERIC_SAAS_CONVERSION_ROADMAP.md](../../docs/GENERIC_SAAS_CONVERSION_ROADMAP.md) | Full 22-phase conversion roadmap |

## Operations

| File | Contents |
|---|---|
| [docs/git-flow.md](../../docs/git-flow.md) | Git Flow methodology |
| [docs/quick-start.md](../../docs/quick-start.md) | Quick start guide |
