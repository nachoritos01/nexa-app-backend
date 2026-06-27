---
name: audit
description: Audit the application for multi-tenant security, database integrity, code quality, and scalability. Use when you want a comprehensive health check of the template.
disable-model-invocation: false
argument-hint: "[area: all|security|database|code|performance]"
---

# SaaS Template Audit

Audita la aplicacion completa como template SaaS multi-tenant. El objetivo es encontrar problemas reales, areas de mejora, y asegurar que el template sea estable y escalable para diferentes aplicaciones.

**NO escondas problemas. Reporta TODO lo que encuentres.** Los errores son utiles para resolver. Estamos en pro de mejoras continuas.

Area a auditar: $ARGUMENTS (default: all)

---

## 1. Multi-Tenant Security Audit

Run these checks and report findings:

### 1.1 Tenant Isolation
- Verify ALL models with `BelongsToTenant` trait have global scopes applied
- Check for models that access tenant data but DON'T use the trait
- Search for raw DB queries (`DB::select`, `DB::statement`) that might bypass tenant scoping
- Check that all Filament resources respect tenant context
- Verify API endpoints filter by tenant_id

### 1.2 Cross-Tenant Leaks
- Search for `withoutGlobalScopes()` usage — each instance must be justified
- Check routes that might expose data across tenants
- Verify customer portal isolates by tenant
- Check that file uploads/storage paths include tenant_id

### 1.3 Authentication & Authorization
- Verify Spatie Permission roles are tenant-scoped
- Check middleware stack for auth gaps
- Verify super-admin panel is properly gated
- Check for mass assignment vulnerabilities (`$guarded = []`)

## 2. Database Integrity Audit

### 2.1 Schema Analysis
```bash
DB_PORT=5433 php artisan tinker --execute="
\$tables = \DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename\");
foreach (\$tables as \$t) {
    \$cols = \DB::select(\"SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = '\" . \$t->tablename . \"' ORDER BY ordinal_position\");
    echo \$t->tablename . ' (' . count(\$cols) . ' cols)' . PHP_EOL;
}
"
```

### 2.2 Foreign Key Validation
- Check all foreign keys have proper ON DELETE constraints
- Verify no orphaned records exist (customers without tenant, orders without customer, etc.)
- Check for missing indexes on frequently queried columns

### 2.3 Data Consistency
- Verify tenant_id consistency across related records (order.tenant_id == customer.tenant_id)
- Check for NULL values in required fields
- Verify enum values match code definitions
- Check loyalty points balance_after matches actual point sum

### 2.4 Migration Safety
- Check for destructive migrations that could fail in production
- Verify all migrations are reversible (have `down()`)
- Check for missing indexes on tenant_id columns

## 3. Code Quality Audit

### 3.1 Architecture
- Check for business logic in controllers (should be in Services)
- Verify all Services are properly injected (not using `new Service()`)
- Check for `env()` usage outside config files
- Verify seeders are idempotent (`firstOrCreate`/`updateOrCreate`)

### 3.2 Error Handling
- Check for bare `catch (\Exception $e)` without logging
- Verify critical operations have proper try/catch
- Check webhook handlers for error resilience

### 3.3 Performance
- Search for N+1 query patterns (missing `with()` eager loading)
- Check for queries inside loops
- Verify caching is used on heavy queries
- Check for missing database indexes

### 3.4 Test Coverage
- Run `composer test` and report results
- Identify untested critical paths (billing, plugin activation, loyalty)
- Check for tests that don't assert anything meaningful

## 4. Scalability Review

### 4.1 Multi-Tenant Scalability
- Check if queries scale with tenant count
- Verify indexes support per-tenant filtering
- Check for shared resources that could bottleneck

### 4.2 Plugin System
- Verify `hasModule()` memoization works correctly
- Check plugin activation/deactivation is atomic
- Verify Stripe webhook handling is idempotent

### 4.3 Configuration
- Check all features use `config()` not `env()`
- Verify plan limits are configurable
- Check that business rules are not hardcoded

## Output Format

Create TWO outputs:

### Output 1: Findings Table

Create a summary table with ALL findings:

```markdown
| # | Area | Severity | File/Location | Finding | Impact |
|---|------|----------|---------------|---------|--------|
| 1 | Security | CRITICAL | app/Models/X.php | Missing BelongsToTenant | Data leak |
| 2 | Database | HIGH | migrations/... | Missing index on tenant_id | Slow queries |
| 3 | Code | MEDIUM | app/Http/... | env() used in controller | Config cache breaks |
| 4 | Performance | LOW | app/Services/... | N+1 query pattern | Slow page load |
```

Severity levels: CRITICAL > HIGH > MEDIUM > LOW > INFO

### Output 2: Feature Docs

For each CRITICAL or HIGH finding, create a feature/fix document in `docs/features/` with the naming convention:

```
docs/features/fix-{number}-{short-description}.md
```

Each file should contain:
- Problem description
- Impact assessment
- Proposed solution
- Files to modify
- Acceptance criteria

---

After completing the audit, update `.claude/context/current.md` with the audit results summary.
