# SaaS Template Mobile — Documentation

> App mobile React Native para el CRM de businesss SaaS Template.

---

## Stack

| Capa | Technology |
|------|-----------|
| Framework | React Native 0.74+ con Expo SDK 51 |
| Language | TypeScript 5 |
| Navigation | Expo Router (file-based) |
| State | Zustand + React Query |
| HTTP | Axios + React Query |
| Auth | Sanctum tokens + SecureStore |
| UI | React Native Paper + custom |
| Testing | Jest + Maestro |
| CI/CD | GitHub Actions + EAS |

---

## Documentos

| File | Contenido |
|---------|-----------|
| [MOBILE_ROADMAP.md](MOBILE_ROADMAP.md) | **Roadmap activo**: 6 fases, tareas numeradas, criterios de aceptacion. LEER PRIMERO. |
| [PROJECT_SETUP.md](PROJECT_SETUP.md) | Setup inicial, stack, libraries base |
| [MOBILE_ARCHITECTURE.md](MOBILE_ARCHITECTURE.md) | Arquitectura por features, capas, carpetas |
| [AUTHENTICATION_FLOW.md](AUTHENTICATION_FLOW.md) | Login, tokens, session, multi-tenant |
| [CORE_FEATURES.md](CORE_FEATURES.md) | MVP: Dashboard, Customers, Quotes, Orders |
| [API_INTEGRATION.md](API_INTEGRATION.md) | Customer HTTP, interceptors, offline |
| [TESTING_STRATEGY.md](TESTING_STRATEGY.md) | Unit, integration, E2E |
| [CI_CD.md](CI_CD.md) | GitHub Actions, builds, distribution |
| [DEPLOYMENT.md](DEPLOYMENT.md) | App Store y Play Store |

---

## Backend de Referencia

- **URL operations:** `https://your-app.example.com`
- **API base:** `/api`
- **Auth:** Laravel Sanctum (Bearer token)
- **Health check:** `GET /api/health` (DB, cache, storage checks)
- **Docs API:** [../api-reference.md](../api-reference.md)
- **Arquitectura SaaS:** [../saas/4-SAAS_ARCHITECTURE.md](../saas/4-SAAS_ARCHITECTURE.md)

### Estado del Backend (v1.0.0)

| Feature | Estado |
|---------|--------|
| SaaS multi-tenant (column isolation) | Implementado (9 fases) |
| Stripe billing (3 planes) | Implementado |
| API public (pricing, products, orders, quotes) | Implementado |
| API v1 tenant-scoped (Sanctum + throttle) | Implementado |
| Security headers (OWASP) | Implementado |
| Encrypted settings (HasEncryptedSettings) | Implementado |
| Caching layer (dashboard 60s, usage 5min) | Implementado |
| CI/CD (GitHub Actions) | Implementado |
| Sentry error tracking | Implementado |
| Queue worker (background jobs) | Implementado |
| Auth endpoints para mobile (`/api/auth/*`) | Implementado |
| Dashboard stats API (`/api/v1/dashboard/stats`) | Implementado |
| Customers CRUD completo (`/api/v1/customers`) | Implementado (index, show, store, update, orders) |

> **286 tests, 745 assertions.** Backend GROWTH READY.

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-24*
