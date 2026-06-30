# Admin UI (`/admin`) vs API (`/api` · `/docs`)

A common question: **is `http://localhost:8000/admin` the same thing as the API endpoints
documented at `http://localhost:8000/docs`?** Short answer: **no**. They are two different
*delivery layers* built on top of the same domain core. This guide explains what each one is, how
they differ, why they live in the same Laravel app, and whether they can be separated.

---

## TL;DR

- **`/admin`** = a **UI** (Filament, server-rendered Blade + Livewire). A human opens it in a browser.
- **`/api/*`** = **data endpoints** (JSON, stateless). Another program consumes them — the Next.js
  panel (`:3001`), a mobile app, etc.
- **`/docs`** = neither; it is the **Scribe documentation** of the `api/*` routes (a manual + a
  "try it" console), not a service itself.
- They live together because they share **one domain core**: the same Eloquent models, the same
  PostgreSQL database, the same multi-tenant scoping, the same business rules. One source of truth.

> Note: the **Next.js panel does not use `/admin`**. It talks only to `/api/agency/*`. So there are
> **two admin experiences over the same data**: Filament `/admin` (Laravel-native, for the SaaS
> operator) and the Next.js panel (for the agency, via the API).

---

## The three things, concretely

| Surface | What it is | Defined in |
|---|---|---|
| `/admin` | Filament admin panel UI | `app/Providers/Filament/AdminPanelProvider.php` (`->path('admin')->login()`) |
| `/super-admin` | Filament super-admin panel (SaaS operator) | `app/Providers/Filament/SuperAdminPanelProvider.php` |
| `/api/*` | JSON API (`/api/auth`, `/api/v1/*` e-commerce, `/api/agency/*` for the panel) | `routes/api.php` |
| `/docs` | Scribe-generated docs for `api/*` (`type => laravel`) | `config/scribe.php` → regenerate with `php artisan scribe:generate` |

---

## What characterizes each

| | `/admin` (Filament) | `/api/*` (described by `/docs`) |
|---|---|---|
| What it is | UI, server-rendered (Blade + Livewire) | API, JSON, stateless |
| Audience | a person in a browser | a program (Next.js panel, mobile) |
| Auth | **session + cookie** (`web` guard, `driver => session`) | **Sanctum token** (`auth:sanctum`) + `X-Tenant-ID` |
| State | stateful (server session) | stateless (each request carries its token) |
| Routes file | `routes/web.php` + the PanelProvider | `routes/api.php` |
| Returns | HTML | JSON |
| Multi-tenant | `EnsureTenant` middleware (web group) | `api.tenant` (`ResolveApiTenant`) middleware |

The API route groups (see `routes/api.php`):

```
/api/auth/login                 → issues a Sanctum token (POST email+password)
/api/v1/*    [auth:sanctum, api.tenant, api.pro, throttle]   → e-commerce / mobile API
/api/agency/*[auth:sanctum, api.tenant, throttle]            → the Next.js panel API (no Pro gate)
```

---

## Why they live together (modular monolith)

Both surfaces ultimately **read and write the same tables through the same Eloquent models**.
`/admin` editing a record and `POST /api/agency/clients` creating one both go through the same
domain layer, the same validation, the same tenant global scope.

```
        ┌────────────── Delivery layers ──────────────┐
        │                                              │
   /admin (Filament UI)              /api/* (JSON, Sanctum)
   session auth, HTML                token auth, stateless
        │                                              │
        └───────────────┬──────────────────────────────┘
                        ▼
            Shared domain core (one source of truth)
        Eloquent models · policies · tenant scoping ·
        services / business rules · migrations
                        ▼
                  PostgreSQL (multi-tenant)
```

Keeping them in **one app** means **no duplication** of models, validation, migrations, or auth
logic. Filament's `/admin` is for the backend operator; the API is for the decoupled frontends.

---

## Can they be separated?

**Logically, they already are.** Different route files, different auth guards, different middleware.
The API does not depend on Filament, and Filament does not depend on the API — you can change one
without breaking the other.

**At the deployment level**, you have options, from cheapest to heaviest:

1. **Keep one codebase, just restrict exposure (recommended).** They are already independent at the
   route level, so you don't need to split anything. Put `/admin` behind an IP allowlist / VPN /
   internal subdomain, and protect `/docs` (see below). This gives you most of the "separation"
   benefit (smaller public surface) with zero refactor.
2. **Toggle a surface off.** The codebase already gates features with `hasModule(...)`; you can
   register the Filament panels or the API route group conditionally per environment.
3. **Two physical services.** Extract the API into its own Laravel app and share the database or a
   package of models. You gain independent scaling/deploys and a smaller per-service attack surface,
   but you pay with a **domain model split/shared across two repos** and distributed-systems
   complexity. Usually **not worth it** until scale or team size forces it.

**Recommendation:** for a single product with a small team, the monolith is the right call. If the
concern is exposure, do option 1 — don't split the backend.

---

## Related security note

`/docs` (Scribe) is currently **public**. In production it exposes the full map of your API.
Put it behind middleware (`config/scribe.php` → `laravel.middleware`) or restrict it to non-prod
environments. Tracked in [`docs/features/46-agency-module-followups.md`](../features/46-agency-module-followups.md)
§3. (Separately, API error bodies are made generic in production via the `APP_DEBUG=false` +
`bootstrap/app.php` render callback — internal SQL/host/paths are never returned to API clients in prod.)

---

## See also

- [guides/02-architecture.md](02-architecture.md) — overall project structure
- [guides/04-api-development.md](04-api-development.md) — building API controllers/routes
- [guides/10-filament-admin.md](10-filament-admin.md) — the Filament admin panel
- [docs/api/agency-reference.md](../api/agency-reference.md) — the panel's API reference
