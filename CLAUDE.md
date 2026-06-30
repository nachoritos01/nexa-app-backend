# Generic Multi-Tenant SaaS Template

Monolith MVC: Laravel 12 + Livewire 4.1 + Filament 3.3 + PostgreSQL 15.
Deploy: Railway (Docker).

## Common Commands

```bash
composer test          # Testing (uses DB_PORT=5432 from phpunit.xml)
composer analyse       # PHPStan level 5
composer format        # Code style
php artisan serve      # Local dev
php artisan migrate:fresh --seed   # Reset DB
```

> **Docker DB:** PostgreSQL runs in the **shared** Docker container `shared-postgres` on port
> **5432** (Redis: `shared-redis` on 6379). Both `.env` (dev → db `saas_template`) and `phpunit.xml`
> (tests → db `saas_template_test`) already point to 5432, so **no `DB_PORT=` prefix is needed**:
> ```bash
> php artisan migrate:fresh --seed
> composer test
> ```
> (Historically this was a dedicated `nexa-app-backend-postgres-1` container on 5433 — removed
> 2026-06-30 in favour of the shared container. Ignore any older `DB_PORT=5433` references.)

## Important Rules

- NEVER create unnecessary files — ALWAYS prefer editing existing ones
- Follow established patterns in the codebase
- PHP 8.5 (`php` system, via ppa:ondrej/php)
- ALWAYS use `config()` in models/controllers, NEVER `env()` directly (fails with config:cache)
- Seeders MUST be idempotent (`firstOrCreate` or `updateOrCreate`)
- Password cast `'hashed'` on User: DO NOT use `bcrypt()` when creating via model (double hash)

## Git Flow

- `main` → Production | `develop` → Integration
- `feature/*`, `bugfix/*`, `docs/*`, `hotfix/*` → from develop (hotfix from main)
- Detailed conventions: `docs/git-flow.md`

### Commits and PRs

- Language: **English** for commits, conventional commits (feat/fix/docs/refactor/build)
- DO NOT add "Generated with Claude Code" or "Co-Authored-By: Claude"
- Commit after each significant task, DO NOT accumulate
- Tag + release after each merge to develop (patch/minor/major)

## Context Files (read as needed)

| File | When to read |
|---|---|
| [.claude/context/current.md](.claude/context/current.md) | **Session start** — active branch, status, tech debt |
| [.claude/context/architecture.md](.claude/context/architecture.md) | Modify models, relationships, data flow |
| [.claude/context/conventions.md](.claude/context/conventions.md) | Naming, file structure, imports |
| [.claude/context/docs-index.md](.claude/context/docs-index.md) | Find specific documentation (guides, features, SaaS, mobile) |
| [.claude/context/saas-completed.md](.claude/context/saas-completed.md) | SaaS reference (completed) |

## Browser Verification (MCP Chrome DevTools)

- After each commit that adds UI (views, Filament resources, portal tabs), verify visually using MCP Chrome DevTools
- Start dev server: `php artisan serve --port=8000 &` (DB on `shared-postgres`:5432 via `.env`)
- Use `navigate_page`, `take_screenshot`, `take_snapshot`, `fill_form`, `click` to test login flows, admin CRUD, and customer portal
- Verify: pages load without errors, sidebar navigation shows correct items, data displays correctly
- Stop server after verification: `kill %1`

## Session Protocol

- Read `.claude/context/current.md` at the start of each session
- Update `current.md` at the end (active branch, work done, tech debt)

## Deploy Notes (Railway)

- `env()` returns `null` with `config:cache` active outside `config/*.php` → use `config()`
- Trusted Proxies required: `$middleware->trustProxies(at: '*')` in `bootstrap/app.php`
- Entrypoint: `config:clear` → `migrate` → `db:seed` → `storage:link` → `config:cache` → `route:cache` → `view:cache` → `queue:work &` → `serve`
- Seeders run on every deploy (idempotent)

---
*Last updated: 2026-03-13*
