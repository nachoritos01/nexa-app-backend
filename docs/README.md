# Documentation

Complete documentation index for the Generic Multi-Tenant SaaS Template.

---

## Quick Start

| Doc | Description |
|-----|-------------|
| [quick-start.md](quick-start.md) | Environment setup and first steps |
| [GENERIC_SAAS_CONVERSION_ROADMAP.md](GENERIC_SAAS_CONVERSION_ROADMAP.md) | Full 22-phase conversion roadmap |
| [git-flow.md](git-flow.md) | Git Flow methodology and branch conventions |

## Development Guides

| # | Doc | Description |
|---|-----|-------------|
| 01 | [Getting Started](guides/01-getting-started.md) | Requirements, environment setup |
| 02 | [Architecture](guides/02-architecture.md) | Project structure and patterns |
| 03 | [Models & Database](guides/03-models-database.md) | Eloquent models, relationships, migrations |
| 04 | [API Development](guides/04-api-development.md) | REST API, controllers, routes, JSON responses |
| 05 | [Livewire Components](guides/05-livewire-components.md) | Livewire properties, computed, reactivity |
| 06 | [Services & Logic](guides/06-services-logic.md) | Business logic services, PdfGenerator |
| 07 | [Testing](guides/07-testing.md) | Unit and integration tests |
| 08 | [Best Practices](guides/08-best-practices.md) | Conventions, standards |
| 09 | [Commands Reference](guides/09-commands-reference.md) | Artisan commands and scripts |
| 10 | [Filament Admin](guides/10-filament-admin.md) | Filament admin panel, resources, customization |
| 11 | [Git Troubleshooting](guides/11-git-troubleshooting.md) | Git error resolution |
| 12 | [Railway Deployment](guides/12-railway-deployment.md) | Docker, PostgreSQL, environment variables |
| 13 | [DBeaver + Railway](guides/13-dbeaver-railway-postgres.md) | DBeaver connection to Railway PostgreSQL |
| 14 | [Database Backup](guides/14-database-backup-restore.md) | pg_dump, DBeaver backup/restore |
| 17 | [Stripe CLI Setup](guides/17-stripe-cli-setup.md) | Stripe CLI installation and config |
| 18 | [Stripe Products](guides/18-stripe-products-setup.md) | Stripe products and pricing setup |
| 19 | [AWS Route53 + Railway](guides/19-aws-route53-railway-domain.md) | Custom domain setup |

## Reference

| Doc | Description |
|-----|-------------|
| [API Reference](api-reference.md) | Complete API endpoints with examples |
| [API v1 Reference](api/v1-reference.md) | V1 API detailed reference |
| [Design System](design-system.md) | Color palette, gradients, UI/UX components |
| [Filament Guide](filament-guide.md) | Filament panel guide |
| [Claude Context Guide](claude-context-guide.md) | How to use Claude context files |

## SaaS Architecture

| # | Doc | Description |
|---|-----|-------------|
| 1 | [SaaS Vision](saas/1-SAAS_VISION.md) | Product vision and goals |
| 2 | [Pricing Strategy](saas/2-PRICING_STRATEGY.md) | Plans, tiers, pricing model |
| 3 | [Multitenancy Strategy](saas/3-MULTITENANCY_STRATEGY.md) | Tenant isolation approach |
| 4 | [SaaS Architecture](saas/4-SAAS_ARCHITECTURE.md) | Technical architecture |
| 5 | [Feature Gap Analysis](saas/5-FEATURE_GAP_ANALYSIS.md) | Feature gap analysis |
| 6 | [SaaS Metrics](saas/6-SAAS_METRICS.md) | KPIs, MRR, churn, health score |
| 7 | [SaaS Roadmap](saas/7-SAAS_ROADMAP.md) | Feature roadmap |
| 8 | [Database Schema](saas/8-DATABASE_SAAS_SCHEMA.md) | Multi-tenant database schema |
| 9 | [Deploy Strategy](saas/9-DEPLOY_STRATEGY.md) | Deployment strategy |
| 10 | [Security Hardening](saas/10-SECURITY_HARDENING.md) | Security measures |
| 11 | [Evolution Roadmap](saas/11-SAAS_EVOLUTION_ROADMAP.md) | Long-term evolution plan |

## Feature Docs

### Implemented

| # | Doc | Description |
|---|-----|-------------|
| 18 | [Customer Portal](features/18-customer-portal.done.md) | My Account portal |
| 23 | [RBAC Roles & Permissions](features/23-rbac-roles-permissions.done.md) | Spatie Permission roles |
| 24 | [Registration & Onboarding](features/24-registration-onboarding.done.md) | Registration flow |
| 25 | [Billing & Plan Swap](features/25-billing-plan-swap.alta.done.md) | Stripe billing, plan changes |
| 26 | [Team Management](features/26-team-management.done.md) | Team invitations, roles |
| 27a | [Module Flags](features/27-activate-module-flags.done.md) | Feature flag activation |
| 27b | [Plugin Marketplace](features/27-plugin-marketplace-billing.done.md) | Plugin marketplace + billing |
| 28 | [Loyalty Program](features/28-loyalty-program.done.md) | Points, tiers, rewards |
| 29 | [SuperAdmin Tenant Plugins](features/29-super-admin-tenant-plugins.done.md) | Plugin management per tenant |
| 30 | [Billing History](features/30-billing-history.done.md) | Billing event history |

### Audit Findings — Pending

Found by `/audit all` on 2026-03-16. Ordered by priority.

**HIGH:**

| # | Doc | Area | Description |
|---|-----|------|-------------|
| 31 | [Fix Subscription FK Constraints](features/31-fix-subscription-fk-constraints.md) | Database | Missing FK constraints on subscriptions tables |
| 32 | [Fix RevenueChart N+1](features/32-fix-revenue-chart-n-queries.md) | Performance | 30 SUM queries in loop |
| 33 | [Fix TenantResource N+1](features/33-fix-tenant-resource-n-plus-1.md) | Performance | 2 extra queries per row in plugins |
| 34 | [Fix TenantGrowthChart](features/34-fix-tenant-growth-chart-queries.md) | Performance | 8 COUNT queries in loop |
| 35 | [Add LoyaltyService Tests](features/35-add-loyalty-service-tests.md) | Tests | Zero test coverage on core service |
| 36 | [Add CustomerPortal Tests](features/36-add-customer-portal-tests.md) | Tests | Zero test coverage on portal controller |

**MEDIUM:**

| # | Doc | Area | Description |
|---|-----|------|-------------|
| 37 | [Fix Customer Auth Tenant Scope](features/37-fix-customer-auth-tenant-scope.md) | Security | Cross-tenant login possible |
| 38 | [Fix PersonalAccessToken Scope](features/38-fix-personal-access-token-scope.md) | Security | Missing BelongsToTenant trait |
| 39 | [Fix Trial Commands Eager Loading](features/39-fix-trial-commands-eager-loading.md) | Performance | N+1 in scheduled commands |
| 40 | [Cache Dashboard Widgets](features/40-cache-dashboard-widgets.md) | Performance | 4 queries uncached per load |
| 41 | [Add CustomerAuth Tests](features/41-add-customer-auth-tests.md) | Tests | No tests for login/logout |
| 42 | [Add Webhook Handler Tests](features/42-add-webhook-handler-tests.md) | Tests | No tests for Stripe handlers |
| 43 | [Extract BillingService](features/43-extract-billing-service.md) | Code | Business logic in controller |
| 44 | [Extract Health Score Config](features/44-extract-health-score-config.md) | Code | Hardcoded thresholds (70/50) |

## Mobile App

| Doc | Description |
|-----|-------------|
| [README](mobile/README.md) | Mobile app overview |
| [Architecture](mobile/MOBILE_ARCHITECTURE.md) | Mobile architecture |
| [Project Setup](mobile/PROJECT_SETUP.md) | Setup guide |
| [Authentication Flow](mobile/AUTHENTICATION_FLOW.md) | Auth flow |
| [API Integration](mobile/API_INTEGRATION.md) | API integration |
| [Core Features](mobile/CORE_FEATURES.md) | Core features |
| [Testing Strategy](mobile/TESTING_STRATEGY.md) | Testing approach |
| [CI/CD](mobile/CI_CD.md) | CI/CD pipeline |
| [Deployment](mobile/DEPLOYMENT.md) | App deployment |
| [Roadmap](mobile/MOBILE_ROADMAP.md) | Mobile roadmap |

## QA & Testing

| Feature | Docs |
|---------|------|
| Billing Plan Swap | [test-plan](qa/billing-plan-swap/test-plan.md), [summary](qa/billing-plan-swap/test-summary.md) |
| RBAC (Phase 2) | [test-plan](qa/fase2-rbac/test-plan.md), [cases](qa/fase2-rbac/test-cases.md), [matrix](qa/fase2-rbac/test-matrix.md), [summary](qa/fase2-rbac/test-summary.md) |
| Registration (Phase 3) | [summary](qa/fase3-registration-onboarding/test-summary.md) |
| Stripe Plans (Phase 4) | [test-plan](qa/fase4-stripe-plans/test-plan.md), [cases](qa/fase4-stripe-plans/test-cases.md), [summary](qa/fase4-stripe-plans/test-summary.md) |
| Trial Conversion (Phase 5) | [test-plan](qa/fase5-trial-conversion/test-plan.md), [cases](qa/fase5-trial-conversion/test-cases.md), [summary](qa/fase5-trial-conversion/test-summary.md) |
| Retention PR1 (Phase 6) | [test-plan](qa/fase6-retention-pr1/test-plan.md), [cases](qa/fase6-retention-pr1/test-cases.md) |
| Retention PR2 (Phase 6) | [test-plan](qa/fase6-retention-pr2/test-plan.md), [cases](qa/fase6-retention-pr2/test-cases.md) |
| Premium PR1 (Phase 7) | [test-plan](qa/fase7-premium-pr1/test-plan.md), [cases](qa/fase7-premium-pr1/test-cases.md), [summary](qa/fase7-premium-pr1/test-summary.md) |
| Premium PR2 (Phase 7) | [test-plan](qa/fase7-premium-pr2/test-plan.md), [cases](qa/fase7-premium-pr2/test-cases.md), [summary](qa/fase7-premium-pr2/test-summary.md) |
| Integrations PR1 (Phase 8) | [test-plan](qa/fase8-integrations-pr1/test-plan.md), [cases](qa/fase8-integrations-pr1/test-cases.md), [summary](qa/fase8-integrations-pr1/test-summary.md) |
| Integrations PR2 (Phase 8) | [test-plan](qa/fase8-integrations-pr2/test-plan.md), [cases](qa/fase8-integrations-pr2/test-cases.md), [summary](qa/fase8-integrations-pr2/test-summary.md) |
| Optimization PR1 (Phase 9) | [test-plan](qa/fase9-optimization-pr1/test-plan.md), [cases](qa/fase9-optimization-pr1/test-cases.md), [summary](qa/fase9-optimization-pr1/test-summary.md) |
| Optimization PR2 (Phase 9) | [test-plan](qa/fase9-optimization-pr2/test-plan.md), [cases](qa/fase9-optimization-pr2/test-cases.md), [summary](qa/fase9-optimization-pr2/test-summary.md) |
| Order Delivery | [test-plan](qa/order-delivery-improvements/test-plan.md), [cases](qa/order-delivery-improvements/test-cases.md), [summary](qa/order-delivery-improvements/test-summary.md) |
| Security Cross-Tenant | [test-plan](qa/security-cross-tenant/test-plan.md), [cases](qa/security-cross-tenant/test-cases.md), [summary](qa/security-cross-tenant/test-summary.md) |
| Customer Address Bugfix | [test-plan](qa/bugfix-customer-address-label/test-plan.md), [cases](qa/bugfix-customer-address-label/test-cases.md), [summary](qa/bugfix-customer-address-label/test-summary.md) |
| [Bug Report Template](qa/bug-report-template.md) | Template for reporting bugs |

## Reports

| Doc | Description |
|-----|-------------|
| [Phase Testing Report](reports/fase-testing-report.md) | Cross-phase testing report |
| [Filament Capabilities](reports/filament-capabilities-report.md) | Filament panel capabilities |

## SQL & Troubleshooting

| Doc | Description |
|-----|-------------|
| [Billing Queries](sql/billing-queries.md) | Useful billing SQL queries |
| [SQL Reference](troubleshooting/sql-queries-reference.md) | General SQL reference |
| [Tenant ID Null Violation](troubleshooting/01-tenant-id-null-violation.resolved.md) | Resolved: tenant_id null constraint |
