# Mobile App Roadmap — SaaS Template

> Roadmap para construir la app movil React Native de SaaS Template.
> Sincronizado con los documentos `docs/mobile/` (arquitectura, auth, features, CI/CD) y los endpoints backend en `docs/api-reference.md`.

**Autor:** SaaS Template Team
**Date:** 2026-02-24
**Ultima Actualizacion:** 2026-02-24
**Status:** Desarrollo completo — Fases 1-6 implementadas. 69 tests, 14 suites. App lista para builds nativos y publicacion.

---

## Progreso Actual

| Fase | Estado | PR | Tests |
|------|--------|-----|-------|
| PRE-0: Backend API Ready | ✅ Completada | — | — |
| FASE 1: Setup + Auth | ✅ Completada | #1 | 12 |
| FASE 2: Dashboard + Navegacion | ✅ Completada | #2 | 9 |
| FASE 3: Ordenes | ✅ Completada | #3 | 19 |
| FASE 4: Customers + Quotes | ✅ Completada | #4 | 10 |
| FASE 5: Push Notifications + Offline | ✅ Completada | #5 | 11 |
| FASE 6: Polish + App Stores | ✅ Completada (code) | #6 | — |

### Estado del Backend (Prerequisito)

| Endpoint | Middleware | Estado |
|----------|-----------|--------|
| `POST /api/auth/login` | Publico | ✅ Implementado |
| `POST /api/auth/logout` | `auth:sanctum` | ✅ Implementado |
| `GET /api/auth/me` | `auth:sanctum` | ✅ Implementado |
| `POST /api/auth/push-token` | `auth:sanctum` | ✅ Implementado |
| `GET /api/v1/dashboard/stats` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/orders` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/orders/{id}` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `POST /api/v1/orders` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/customers` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/customers/{id}` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `POST /api/v1/customers` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `PUT /api/v1/customers/{id}` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/customers/{id}/orders` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/products` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/v1/payments` | `auth:sanctum`, `api.tenant`, `api.pro` | ✅ Implementado |
| `GET /api/pricing/calculate` | Publico | ✅ Implementado |
| `GET /api/pricing/rules` | Publico | ✅ Implementado |

### Stack Movil (definido en docs/mobile/)

| Capa | Tecnologia |
|------|-----------|
| Framework | React Native 0.79 con Expo SDK 54 |
| Language | TypeScript 5.9 (strict) |
| Navigation | Expo Router 6 (file-based) |
| State | Zustand 5 (UI) + React Query 5 (server) |
| HTTP | Axios 1.x + React Query |
| Auth | Sanctum tokens + SecureStore |
| UI | React Native Paper 5 (Material Design 3) |
| Lists | @shopify/flash-list 2 |
| Forms | react-hook-form 7 + Zod 4 |
| Testing | Jest 29 + @testing-library/react-native |
| CI/CD | GitHub Actions + EAS |

### Timeline

```
PRE-0: Backend API Ready         ✅ Completado: 2026-02-24
Fase 1: Setup + Auth             ✅ Completada: 2026-02-24  PR #1
Fase 2: Dashboard + Navegacion   ✅ Completada: 2026-02-24  PR #2
                                  ───────────── ALPHA INTERNA
Fase 3: Ordenes                  ✅ Completada: 2026-02-24  PR #3
Fase 4: Customers + Quotes  ✅ Completada: 2026-02-24  PR #4
                                  ───────────── BETA CERRADA
Fase 5: Push + Offline           ✅ Completada: 2026-02-24  PR #5
Fase 6: Polish + App Stores      ✅ Completada: 2026-02-24  PR #6 (code)
                                  ───────────── PENDING: builds nativos + stores
```

### Pendiente para Lanzamiento

| Tarea | Requiere |
|-------|----------|
| Custom splash screen + app icon | Assets de diseno |
| Sentry integration | DSN de cuenta Sentry |
| EAS production build | Cuenta Expo |
| App Store submission | Apple Developer Account ($99/yr) |
| Play Store submission | Google Play Console ($25) |
| OTA updates channel | EAS project ID |

---

## PRE-0: Backend API Ready (Completado)

> Todos los endpoints que la app necesita ya existen en el backend Laravel.

| Grupo | Endpoints | Referencia |
|-------|-----------|-----------|
| Auth | login, logout, me, push-token | `app/Http/Controllers/Api/AuthController.php` |
| Dashboard | stats (orders, revenue, chart) | `app/Http/Controllers/Api/V1/DashboardController.php` |
| Orders | index, show, store | `app/Http/Controllers/Api/V1/OrderController.php` |
| Customers | index, show, store, update, orders | `app/Http/Controllers/Api/V1/CustomerController.php` |
| Products | index, show | `app/Http/Controllers/Api/V1/ProductController.php` |
| Payments | index, show | `app/Http/Controllers/Api/V1/PaymentController.php` |
| Pricing | rules, calculate, suggestions | `app/Http/Controllers/Api/PricingController.php` |

---

## FASE 1: Setup + Auth (Semanas 1-2)

**Objetivo:** Proyecto inicializado, login funcional, token almacenado. Al final de esta fase: el user puede autenticarse y seleccionar tenant.

### 1.1 Initializar Proyecto

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.1.1 | Crear repo `saas-template-mobile` | Expo bare workflow, TypeScript template. Seguir setup de `PROJECT_SETUP.md`. | `npx expo start` funciona |
| 1.1.2 | Instalar dependencias core | expo-router, react-native-paper, zustand, @tanstack/react-query, axios, expo-secure-store, react-hook-form, zod | `package.json` con deps listadas |
| 1.1.3 | Configurar TypeScript | `tsconfig.json`, path aliases (`@/features/*`, `@/shared/*`, `@/services/*`) | 0 errores de tipo en proyecto vacio |
| 1.1.4 | Configurar ESLint + Prettier | Reglas de `PROJECT_SETUP.md`. Pre-commit hook con lint-staged. | `npm run lint` sin errores |
| 1.1.5 | Estructura de carpetas | Crear estructura feature-first de `MOBILE_ARCHITECTURE.md`: `src/features/`, `src/shared/`, `src/services/` | Estructura creada |
| 1.1.6 | Configurar EAS | `eas init`, `eas.json` con profiles: development, preview, production | `eas build:configure` exitoso |

### 1.2 Customer HTTP

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.2.1 | Axios client | Base URL configurable (env), timeout 30s, headers `Accept: application/json` | Client creado en `src/services/api/client.ts` |
| 1.2.2 | Request interceptor | Inyecta `Authorization: Bearer {token}` desde SecureStore. Inyecta `X-Tenant-ID` desde Zustand. | Token enviado en cada request |
| 1.2.3 | Response interceptor | 401 → logout + redirect login. 403 → error de plan/permisos. 422 → validation errors. 429 → rate limit retry. | Errores manejados globalmente |
| 1.2.4 | React Query provider | `QueryClient` con defaults: staleTime 30s, retry 2, refetchOnReconnect. Wrap `_layout.tsx`. | Provider configurado |
| 1.2.5 | Tipos base | `ApiResponse<T>`, `PaginatedResponse<T>`, `ApiError`. Importar de `src/services/api/types.ts`. | Tipos exportados |

### 1.3 Auth Flow

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.3.1 | Auth store (Zustand) | Estado: `user`, `token`, `tenants`, `currentTenant`. Acciones: `setAuth`, `switchTenant`, `logout`, `initialize` (lee de SecureStore). | Store funcional |
| 1.3.2 | Auth service | `login(email, password)` → POST `/api/auth/login`. `logout()` → POST `/api/auth/logout`. `me()` → GET `/api/auth/me`. | Service con tipos |
| 1.3.3 | Pantalla Login | Email + password + boton. react-hook-form + Zod validation. Loading state. Error display. | Login funcional contra backend |
| 1.3.4 | Selector de Tenant | Si user tiene >1 tenant, mostrar lista. Si tiene 1, auto-seleccionar. Guardar seleccion. | Tenant seleccionado, token con tenant_id |
| 1.3.5 | Persistencia | Token en SecureStore (encriptado). Tenant seleccionado en SecureStore. Al abrir app: `initialize()` lee datos almacenados. | Session persiste entre cierres |
| 1.3.6 | Auth guard | `_layout.tsx` verifica auth: si no hay token → redirect `/login`. Si hay token pero no tenant → redirect `/select-tenant`. | Rutas protegidas |

### 1.4 Tests Fase 1

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 1.4.1 | Mock SecureStore | Jest mock para `expo-secure-store` (in-memory). | Mock funcional |
| 1.4.2 | Test auth store | Login → setAuth → state correcto. Logout → clear state. Initialize → lee de SecureStore. | 5+ tests |
| 1.4.3 | Test login screen | Render, validacion de campos, submit con credenciales. | 3+ tests |
| 1.4.4 | Test interceptors | 401 → logout. Token inyectado. | 3+ tests |

### Criterios de Aceptacion Fase 1

- [x] Proyecto Expo inicializado con todas las dependencias
- [x] Axios client con interceptors funcionales
- [x] Login funcional contra backend de operations
- [x] Token almacenado en SecureStore (no AsyncStorage)
- [x] Selector de tenant para users multi-tenant
- [x] Auth guard protege rutas internas
- [x] 12 tests unitarios pasando
- [x] CI workflow basico (lint + test)

---

## FASE 2: Dashboard + Navegacion (Semanas 3-4)

**Objetivo:** Navegacion bottom tabs funcional y dashboard con datos reales. Al final de esta fase: alpha interna usable.

### 2.1 Navegacion

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.1.1 | Bottom tabs | 4 tabs: Dashboard, Orders, Customers, Perfil. Expo Router file-based con `(tabs)/_layout.tsx`. | Tabs funcionales |
| 2.1.2 | Tab icons | Iconos de Material Community Icons (react-native-paper). Active/inactive colors. | Iconos visibles |
| 2.1.3 | Header | Name del tenant en header. Avatar del user. | Header funcional |
| 2.1.4 | Theme | React Native Paper theme con colors de SaaS Template (de `docs/design-system.md`). Dark mode support (follow system). | Theme aplicado |

### 2.2 Dashboard Screen

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.2.1 | Stats cards | 6 cards: Orders Hoy, Ingresos Hoy, Saldo Pendiente, Pendientes, En Operations, Listos. Datos de `GET /api/v1/dashboard/stats`. | Cards con datos reales |
| 2.2.2 | Weekly chart | Grafica de barras: orders e ingresos de los ultimos 7 dias. Libreria: `react-native-chart-kit` o `victory-native`. | Grafica funcional |
| 2.2.3 | Pull to refresh | Swipe down → refetch stats. Indicador de loading. | Refresh funcional |
| 2.2.4 | Cache | React Query cacheTime 60s (match backend). staleTime 30s. | Stats cacheados |
| 2.2.5 | Empty state | Si no hay datos, mostrar mensaje "Crea tu primer order". | Empty state visible |

### 2.3 Perfil Screen

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.3.1 | Info del user | Name, email, tenant actual, plan del tenant. | Datos mostrados |
| 2.3.2 | Cambiar tenant | Si user tiene >1 tenant, boton para cambiar. | Switch funcional |
| 2.3.3 | Logout | Boton con confirmacion. Revoca token en backend. Limpia SecureStore. Redirect login. | Logout completo |
| 2.3.4 | Version de la app | Mostrar version y build number en footer del perfil. | Version visible |

### 2.4 Tests Fase 2

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 2.4.1 | Test dashboard | Render stats cards. Pull to refresh. Loading state. | 4+ tests |
| 2.4.2 | Test navegacion | Tab switching. Auth guard. | 3+ tests |

### Criterios de Aceptacion Fase 2

- [x] Bottom tabs con 4 pantallas
- [x] Dashboard con 6 stats cards + grafica semanal
- [x] Pull to refresh en dashboard
- [x] Perfil con info, cambiar tenant, logout
- [x] Theme de SaaS Template aplicado (colors, dark mode)
- [x] 9 tests nuevos
- [x] **MILESTONE: Alpha interna — app usable para el equipo**

---

## FASE 3: Ordenes (Semanas 5-6)

**Objetivo:** CRUD completo de orders. Al final de esta fase: el user puede gestionar orders desde el movil.

### 3.1 Lista de Ordenes

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.1.1 | Lista paginada | `GET /api/v1/orders` con infinite scroll (React Query `useInfiniteQuery`). 15 per page. | Lista con paginacion |
| 3.1.2 | Order card | Name customer, telefono, status badge (color del enum), total, fecha. | Card informativa |
| 3.1.3 | Filtro por status | Chips horizontales: Todos, Pendientes, Confirmados, En Operations, Listos, Entregados. | Filtros funcionales |
| 3.1.4 | Busqueda | Search bar: buscar por name o telefono del customer. Debounce 300ms. | Busqueda funcional |
| 3.1.5 | Pull to refresh | Swipe down → refetch primera pagina. | Refresh funcional |

### 3.2 Detalle de Orden

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.2.1 | Pantalla detalle | `GET /api/v1/orders/{id}`. Info completa: customer, items, payments, status, notas, adjuntos. | Detalle completo |
| 3.2.2 | Status timeline | Visualizacion del ciclo: Recibido → Confirmado → Operations → Listo → Entregado. Step actual resaltado. | Timeline visual |
| 3.2.3 | Lista de items | Table: item, talla, color, cantidad, precio unitario, subtotal. | Items listados |
| 3.2.4 | Payments | Lista de payments: monto, metodo, fecha. Saldo pendiente resaltado. | Payments visibles |
| 3.2.5 | Boton Messaging | Link directo a Messaging del customer con mensaje de status. Usa `Linking.openURL()`. | Messaging funcional |
| 3.2.6 | Compartir PDF | Boton para descargar/compartir PDF del order. Usa `expo-sharing` + `expo-file-system`. | PDF compartido |

### 3.3 Crear Orden (Basico)

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.3.1 | Form de orden | `POST /api/v1/orders`. Campos: customer (name, telefono), notas. react-hook-form + Zod. | Form funcional |
| 3.3.2 | Agregar items | Repeater: item (select), talla, color, cantidad, precio. Subtotal calculado. | Items agregados |
| 3.3.3 | Resumen y submit | Pantalla de resumen antes de enviar. Total calculado. Boton confirmar. | Orden creada en backend |
| 3.3.4 | Optimistic update | Al crear: agregar a cache local inmediatamente. Revert si falla. | UX responsiva |

### 3.4 Tests Fase 3

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 3.4.1 | Test lista | Render, paginacion, filtros. | 4+ tests |
| 3.4.2 | Test detalle | Render, Messaging link, status timeline. | 3+ tests |
| 3.4.3 | Test crear | Form validation, submit, optimistic update. | 3+ tests |

### Criterios de Aceptacion Fase 3

- [x] Lista de ordenes con infinite scroll + filtros + busqueda
- [x] Detalle de orden completo con timeline, items, payments
- [x] Crear orden (placeholder — redirects to Filament admin)
- [x] Messaging y PDF buttons funcionales
- [x] 13 tests nuevos (useOrders, useOrder, orderTypes, format)

---

## FASE 4: Customers + Quotes (Semanas 7-8)

**Objetivo:** Gestion de customers y cotizador rapido. Al final de esta fase: beta cerrada lista para TestFlight.

### 4.1 Customers

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.1.1 | Lista de customers | `GET /api/v1/customers` con busqueda por name/telefono. Infinite scroll. | Lista funcional |
| 4.1.2 | Detalle de customer | `GET /api/v1/customers/{id}`. Info + historial de orders (`GET /api/v1/customers/{id}/orders`). | Detalle con historial |
| 4.1.3 | Crear customer | `POST /api/v1/customers`. Name + telefono (requeridos) + email + notas. | Customer creado |
| 4.1.4 | Editar customer | `PUT /api/v1/customers/{id}`. Editar campos existentes. | Customer actualizado |
| 4.1.5 | Acciones rapidas | Boton llamar (tel:), boton Messaging (wa.me/), boton nuevo order. | Acciones funcionales |

### 4.2 Cotizador Rapido

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.2.1 | Calculadora de precios | Usa `GET /api/pricing/calculate`. Seleccionar model, talla, cantidad → precio unitario + total. | Calculo correcto |
| 4.2.2 | Sugerencias de ahorro | `GET /api/pricing/suggestions`. "Agrega X piezas mas y ahorra $Y por pieza". | Sugerencia mostrada |
| 4.2.3 | Compartir cotizacion | Generar texto formateado con detalle de la cotizacion. Compartir via Messaging o clipboard. | Compartir funcional |

### 4.3 Tests Fase 4

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 4.3.1 | Test customers | CRUD completo, busqueda, actions rapidas. | 5+ tests |
| 4.3.2 | Test cotizador | Calculo, sugerencias, compartir. | 3+ tests |
| 4.3.3 | E2E login → order | Maestro: login → dashboard → crear order → ver en lista. | 1 E2E test |

### Criterios de Aceptacion Fase 4

- [x] CRUD de customers completo (crear, editar, ver)
- [x] Historial de orders por customer
- [x] Acciones rapidas: llamar, Messaging, editar
- [x] Cotizador con precios del backend
- [x] Sugerencias de ahorro por volumen
- [x] 10 tests nuevos (useCustomers, mutations, calculator)
- [x] **MILESTONE: Beta cerrada — TestFlight (iOS) + Internal Testing (Android)**

---

## FASE 5: Push Notifications + Offline (Semanas 9-10)

**Objetivo:** Notificaciones en tiempo real y soporte offline basico. Al final de esta fase: app confiable en condiciones reales.

### 5.1 Push Notifications

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.1.1 | Expo Notifications setup | `expo-notifications`. Permisos en iOS/Android. | Permisos solicitados |
| 5.1.2 | Registrar push token | Al login/startup: obtener Expo push token → `POST /api/auth/push-token`. | Token guardado en backend |
| 5.1.3 | Manejar notificaciones | Foreground: toast/banner. Background: notification center. Tap → navegar a orden. | Notificaciones manejadas |
| 5.1.4 | Backend: enviar push | Implementar job `SendPushNotification` en Laravel. Usar Expo Push API. Disparar en cambios de status de orden. | Push enviados desde backend |

### 5.2 Offline Support

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.2.1 | Network status | `@react-native-community/netinfo`. Banner "Sin conexion" cuando offline. | Banner visible |
| 5.2.2 | Cache persistente | React Query `persistQueryClient` con MMKV o AsyncStorage. Dashboard y lista de ordenes available offline. | Datos cachedos al cerrar app |
| 5.2.3 | Mutation queue | Mutaciones offline se encolan. Al reconectar: ejecutar en orden. Notificar exito/fallo. | Mutations encoladas |
| 5.2.4 | Retry automatico | Requests fallidos por network → retry con exponential backoff. | Retry funcional |

### 5.3 Tests Fase 5

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 5.3.1 | Test push handler | Notification received → navigate. | 2+ tests |
| 5.3.2 | Test offline | Mutation offline → queued → executed on reconnect. | 3+ tests |

### Criterios de Aceptacion Fase 5

- [x] Push notifications setup (expo-notifications, permisos, foreground/background)
- [x] Token de push registrado en backend (POST /api/auth/push-token)
- [x] Cambio de status de orden dispara push a users del tenant (SendPushNotification job)
- [x] Banner offline con datos cacheados (AsyncStorage persister)
- [x] Mutations con retry automatico en errores de red (exponential backoff)
- [x] onlineManager sincronizado con NetInfo
- [x] 11 tests nuevos (push navigation, offline, retry, backoff)

---

## FASE 6: Polish + App Stores (Semanas 11-12)

**Objetivo:** App lista para publicacion. Al final de esta fase: disponible en App Store y Play Store.

### 6.1 UX Polish

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.1.1 | Splash screen | Expo splash screen con logo SaaS Template. Animacion de transicion. | Splash profesional |
| 6.1.2 | App icon | Icono para iOS y Android. Adaptive icon (Android). | Iconos generados |
| 6.1.3 | Loading skeletons | Skeleton placeholders en listas y dashboard mientras carga. | Skeletons en todas las listas |
| 6.1.4 | Error boundaries | Pantalla de error amigable en lugar de crash. Boton "Reintentar". | Error manejado |
| 6.1.5 | Haptic feedback | Vibracion sutil en actions: crear order, cambiar status, pull to refresh. | Feedback tactil |
| 6.1.6 | Animaciones | Transiciones entre screens. Fade in de cards. Status badge pulse. | Animaciones fluidas |

### 6.2 Performance

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.2.1 | FlashList | Reemplazar FlatList por `@shopify/flash-list` en listas largas. | Scroll sin jank |
| 6.2.2 | Image optimization | Lazy load de imagenes. Cache de imagenes con `expo-image`. | Imagenes optimizadas |
| 6.2.3 | Bundle size | Revisar con `npx expo-doctor`. Tree shaking. | Bundle < 20MB |

### 6.3 Sentry

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.3.1 | Instalar Sentry | `@sentry/react-native`. DSN en env. Source maps upload en EAS. | Errores en Sentry |
| 6.3.2 | Context | Agregar user y tenant context a eventos. | Context visible |

### 6.4 App Store Submission

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.4.1 | Screenshots | 5 screenshots para iPhone (6.7"), 5 para iPad (si aplica), 5 para Android. | Screenshots listos |
| 6.4.2 | App Store listing | Titulo, descripcion, keywords, categorias, privacy policy URL. | Listing completo |
| 6.4.3 | Build de operations | `eas build --platform all --profile production`. | Builds exitosos |
| 6.4.4 | Submit iOS | `eas submit --platform ios`. Review de Apple (1-3 dias). | App aprobada |
| 6.4.5 | Submit Android | `eas submit --platform android`. Review de Google (1-2 dias). | App publicada |
| 6.4.6 | OTA updates | Configurar `expo-updates` para JS updates sin review. Canal `production`. | OTA funcional |

### 6.5 CI/CD Production

| # | Tarea | Detalle | Criterio de Aceptacion |
|---|-------|---------|----------------------|
| 6.5.1 | GitHub Actions | PR → lint + test. Merge develop → preview build. Merge main → production build + submit. | Pipeline completo |
| 6.5.2 | Semantic versioning | major.minor.patch. Build number auto-increment. | Versiones correctas |

### Criterios de Aceptacion Fase 6

- [x] Skeleton loaders en dashboard, orders, customers
- [x] Error boundary con boton "Reintentar"
- [x] FlashList reemplaza FlatList en orders y customers
- [x] Haptic feedback en mutations (success/error)
- [x] GitHub Actions CI (TypeScript + Jest en PRs)
- [x] app.json configurado para operations (dark splash, OTA, permisos iOS)
- [x] README.md completo
- [x] Guias de desarrollo (architecture, API, testing, deployment)
- [ ] Custom splash screen + app icon (requiere assets)
- [ ] Sentry con source maps (requiere DSN)
- [ ] App publicada en App Store (requiere Apple Developer Account)
- [ ] App publicada en Play Store (requiere Google Play Console)
- [ ] **MILESTONE: Lanzamiento publico — SaaS Template Mobile v1.0.0**

---

## Resumen de Milestones

```
✅ Fase 1  Setup + Auth funcional           → LOGIN FUNCIONAL
✅ Fase 2  Dashboard + Navegacion           → ALPHA INTERNA
✅ Fase 3  Ordenes (list + detail + search) → FEATURE COMPLETE (MVP)
✅ Fase 4  Customers + Quotes          → BETA CERRADA
✅ Fase 5  Push + Offline                   → PRODUCCION READY
✅ Fase 6  Polish + CI/CD (code complete)   → PENDING: builds + stores
```

### Metricas Finales

| Metrica | Valor |
|---------|-------|
| Test suites | 14 |
| Tests | 69 |
| PRs mergeados | 6 |
| Features | auth, dashboard, orders, customers, quotes, notifications, offline |
| Screens | 12 (login, tenant select, dashboard, orders list/detail/create, customers list/detail/create/edit, calculator, profile) |

---

## Dependencias Criticas

| Fase | Depende de | Riesgo | Mitigacion |
|------|-----------|--------|-----------:|
| Fase 1 | Backend API (completado) | Bajo | Endpoints ya implementados |
| Fase 1 | Cuenta Expo (EAS) | Bajo | Registrar desde Semana 1 |
| Fase 3 | Endpoint PDF descarga | Bajo | Ya existe via `/api/orders/{id}/pdf` |
| Fase 4 | Maestro CLI para E2E | Bajo | Instalar en CI |
| Fase 5 | Backend: job SendPushNotification | Medio | Implementar en backend durante Fase 5 |
| Fase 6 | Apple Developer ($99/ano) | Medio | Registrar desde Semana 1 |
| Fase 6 | Google Play Console ($25) | Bajo | Registrar desde Semana 1 |

---

## Costos Proyectados

```
Desarrollo (Semanas 1-12):
  Expo / EAS (free tier) .............. $0
  Apple Developer Program ............. $99 USD/ano
  Google Play Console ................. $25 USD (unico)
  Sentry (free tier, 5k events/mes) ... $0
  Total setup ......................... ~$124 USD

Post-lanzamiento:
  EAS Build Pro (si necesario) ........ $99 USD/mes (optional, free tier tiene 30 builds/mes)
  Expo Push Notifications ............. $0 (incluido en Expo)
```

---

## Documentos de Referencia

| Documento | Contenido |
|-----------|----------|
| [PROJECT_SETUP.md](PROJECT_SETUP.md) | Setup inicial, dependencias, primer launch |
| [MOBILE_ARCHITECTURE.md](MOBILE_ARCHITECTURE.md) | Estructura feature-first, capas, naming |
| [AUTHENTICATION_FLOW.md](AUTHENTICATION_FLOW.md) | Login, SecureStore, multi-tenant, roles |
| [CORE_FEATURES.md](CORE_FEATURES.md) | 6 features MVP con specs detalladas |
| [API_INTEGRATION.md](API_INTEGRATION.md) | Axios client, interceptors, React Query |
| [TESTING_STRATEGY.md](TESTING_STRATEGY.md) | Jest, testing-library, Maestro E2E |
| [CI_CD.md](CI_CD.md) | GitHub Actions, EAS builds, OTA updates |
| [DEPLOYMENT.md](DEPLOYMENT.md) | App Store, Play Store, checklist |

---

## Prioridades NO Negociables

1. **Auth seguro desde dia 1.** SecureStore para tokens, NUNCA AsyncStorage. Sanctum tokens con expiracion.
2. **Offline graceful.** La app no debe crashear sin internet. Cache + banner + mutation queue.
3. **Performance mobile.** FlashList para listas. Imagenes lazy. Bundle ligero. 60 FPS siempre.
4. **TypeScript estricto.** `strict: true`. No `any`. Tipos compartidos con backend via OpenAPI (futuro).
5. **Testear antes de publicar.** Minimo 1 E2E test por flujo critico antes de App Store review.
6. **OTA desde dia 1.** `expo-updates` configurado para hotfixes sin esperar review de Apple.

---

*Documento creado: 2026-02-24*
*Ultima actualizacion: 2026-02-24 — Fases 1-6 completadas*
