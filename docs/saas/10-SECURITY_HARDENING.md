# Security Hardening: SaaS Template SaaS

> Guide de endurecimiento de security para el SaaS multi-tenant.
> Basada en perspectiva de pentester + contexto del proyecto.

---

## Decision Previa: Sanctum vs JWT para la App Mobile

Antes del prompt de implementation, es fundamental resolver esta pregunta architectural.

### Analysis comparativo en contexto SaaS Template

| Criterio | Sanctum (Opaque Tokens) | JWT (Stateless) |
|---------|------------------------|-----------------|
| **Implementation** | Ya instalado (v4.1 en composer.lock) | Requiere `tymon/jwt-auth` o similar |
| **Revocation inmediata** | **Si** — DELETE en `personal_access_tokens` | No — token valid hasta expirar |
| **Suspender tenant** | **Si** — borra todos los tokens del tenant al poner `is_active=false` | No — tokens siguen valids X horas |
| **Lookup en DB por request** | Si (1 query SELECT) | No (verifica firma) |
| **Embed tenant_id** | Via header `X-Tenant-ID` + validation middleware | En el payload del JWT |
| **Refresh token logic** | No necesario (tokens de larga duration) | Necesario si usa short expiry |
| **Rotation** | `createToken()` en cada login, revocar el anterior | Necesita blacklist o short expiry |
| **Escenario de brecha** | Revocar token individual en segundos | Sin blacklist: el atacante tiene acceso X horas |
| **Multi-tenant suspension** | **Critical: Sanctum gana** | Punto weak fatal |

### Veredicto: **Sanctum + Opaque Tokens**

**Razones specifics para SaaS Template:**

1. **Suspension de tenants morosos**: Cuando un tenant no paga y se pone `is_active = false`, necesitas invalidar el acceso inmediatamente. Con Sanctum: `$tenant->tokens()->delete()`. Con JWT: el token sigue valid hasta que expire.

2. **Revocation en incidentes de security**: Si se detecta uso malicioso desde la app mobile de un tenant, puedes revocar su token en segundos desde el Super Admin.

3. **Ya is instalado**: No hay reason para agregar complejidad extra.

4. **Complejidad de refresh tokens**: JWT seguro requiere access token (15 min) + refresh token (7 days) + rotation + blacklist. Eso es 4 veces more code para el mismo resultado.

5. **Expo SecureStore** ya almacena el opaque token de forma segura (Keychain/Keystore). No hay beneficio de tener el token en el customer.

**La unique ventaja real de JWT (sin DB lookup) no justifica los trade-offs para este proyecto.**

---

## Estado Actual del Proyecto — Security Laravel

Diagnosis real del code antes de ejecutar el prompt de implementation.

### Cookies encriptadas — ✅ Activo por defecto

Laravel 12 encripta **todas** las cookies automaticmente via `EncryptCookies`
middleware, incluido en el grupo `web`. Usa `APP_KEY`. No requiere ninguna action.

---

### Sesiones — ⚠️ Configuration incompleta en operations

Estado actual en `.env`:

```bash
SESSION_DRIVER=database        # ✅ guarda en DB, no en files
SESSION_ENCRYPT=false          # ⚠️ datos de session NO encriptados en la table
SESSION_SECURE_COOKIE=         # ⚠️ no definido — cookie puede viajar por HTTP
SESSION_HTTP_ONLY=true         # ✅ JavaScript no puede leer la cookie
SESSION_SAME_SITE=lax          # ✅ protection basic contra CSRF cross-site
```

Valores correctos para operations en Railway:

```bash
SESSION_ENCRYPT=true           # encripta el payload en la table sessions
SESSION_SECURE_COOKIE=true     # cookie solo viaja por HTTPS
```

> **Nota Cloudflare + Railway:** Cloudflare termina SSL antes de Railway, por lo que
> el request interno llega en HTTP. El `trustProxies(at: '*')` ya configurado en
> `bootstrap/app.php` resuelve esto — Laravel detecta correctamente que el request
> original fue HTTPS y la cookie se marca como secure sin problemas.

---

### Crypt::encrypt() / decrypt() en models — ❌ No implementado

No existe en no model ni controller actualmente. **No es un problema hoy**
porque el caso de uso principal (credenciales Payment Gateway por tenant en `tenants.settings`)
pertenece a la Fase 1 SaaS que still no is implementada.

Cuando se cree el model `Tenant`, usar el cast nativo de Laravel:

```php
// app/Models/Tenant.php
protected $casts = [
    'settings' => 'encrypted:array',  // encripta/desencripta automaticmente con APP_KEY
];

protected $hidden = ['settings'];     // nunca exponer en JSON responses ni logs
```

`Crypt::encrypt()` manual solo es necesario para encriptar campos fuera de
models Eloquent (ej: un valor en cache, un parameter en una URL interna).

---

### Signed URLs — ✅ Implementado correctamente en `feature/order-pdf`

La firma la valida el **middleware de Laravel**, no el controller.
El controller solo contiene logic de negocio:

```php
// routes/web.php — feature/order-pdf
Route::get('/orders/{order}/pdf', [OrderPdfController::class, 'download'])
    ->name('orders.pdf')
    ->middleware('signed');     // ← Laravel verifica la firma y expiry antes del controller
```

```php
// OrderPdfController.php — limpio, sin validation de firma manual
public function download(Order $order): Response
{
    abort_if($order->status === OrderStatus::Cancelled, 404);

    return $this->pdfGenerator
        ->generateOrderInline($order)
        ->stream("order_{$order->id}.pdf");
}
```

`->middleware('signed')` rechaza con 403 cualquier URL con firma invalid o expirada
antes de que el request llegue al method. Implementation correcta.

Los PDFs de quotes del portal del customer (`/my-account/orders/{order}/pdf`)
are protegidos por `auth:customer` — no necesitan firma porque requieren session activa.

---

### CSRF — ✅ Activo en todos los formularios web

```php
// app/Providers/Filament/AdminPanelProvider.php
VerifyCsrfToken::class,  // ← explicit en Filament
```

Laravel 12 incluye `VerifyCsrfToken` en el grupo `web` por defecto, cubriendo:

| Ruta | Protection |
|------|-----------|
| Panel Filament `/admin` | ✅ CSRF activo |
| Portal del customer `/my-account` | ✅ CSRF activo |
| Formularios publics (`/quote`, `/contact`) | ✅ CSRF activo |
| API `/api/*` | No aplica — Sanctum usa token en `Authorization` header (stateless) |

---

### Resumen de estado

| Feature | Estado | Action requerida |
|---------|:------:|-----------------|
| Cookies encriptadas | ✅ | Nada |
| `SESSION_ENCRYPT` | ⚠️ | `SESSION_ENCRYPT=true` en Railway |
| `SESSION_SECURE_COOKIE` | ⚠️ | `SESSION_SECURE_COOKIE=true` en Railway |
| `Crypt::encrypt()` en models | ⏳ | Al crear model `Tenant` (Fase 1 SaaS) |
| Signed URLs (PDFs) | ✅ | Merge `feature/order-pdf` |
| CSRF en formularios web | ✅ | Nada |

---

## PROMPT DE IMPLEMENTATION

> Copia y pega este prompt completo a Claude Code (o cualquier LLM) para implementar el hardening de security de SaaS Template.

---

```
Acts como Senior Security Engineer + Laravel Expert.

Tu mission es implementar el hardening de security completo del SaaS SaaS Template,
un CRM multi-tenant para businesss (Laravel 12, PHP 8.5, PostgreSQL 15, Redis).

El item tiene:
- Panel admin Filament 3.3 en /admin
- Portal del customer en /my-account
- REST API en /api/* (actualmente TODO PUBLIC, sin auth)
- App mobile React Native connecting via API con Sanctum tokens
- Multi-tenancy via column isolation (tenant_id en todas las tables de negocio)
- Deploy en Railway + Cloudflare CDN/WAF

---

## CONTEXTO DE SEGURIDAD ACTUAL (ESTADO = RIESGO)

PROBLEMAS IDENTIFICADOS:
1. TODAS las rutas /api/* son publics — cualquiera puede crear/eliminar orders
2. No existe authentication API (Sanctum is instalado pero no configurado)
3. No hay rate limiting en no endpoint
4. No hay Form Requests — validation inline en controllers (no sanitizada)
5. No hay Policies — authorization inexistente
6. Tenant isolation NO implementado — no existe tenant_id ni Global Scopes still
7. API keys de Payment Gateway, Stripe, Envia.com — check que are en .env, NO en code
8. Sin CAPTCHA en registration/login
9. Sin audit trail por tenant
10. credentials de Payment Gateway por tenant se will save en tenants.settings (JSON) — necesitan encryption

ARQUITECTURA RELEVANTE:
- `app/Models/User.php` — sin HasApiTokens
- `routes/api.php` — todas las rutas sin middleware
- `bootstrap/app.php` — sin rate limiting definido
- `config/auth.php` — guard api no configurado
- `app/Http/Controllers/Api/` — 5 controllers existentes (Auth inexistente, Pricing, Products, Orders, Quotes, Content)
- NO existen Form Requests en app/Http/Requests/
- NO existen Policies en app/Policies/

---

## IMPLEMENTATION REQUERIDA (POR PRIORIDAD)

### PRIORIDAD 1 — AUTHENTICATION API (Sanctum)

Decision de arquitectura: USAR SANCTUM (NO JWT).
Justification: Multi-tenant SaaS necesita revocation inmediata de tokens
(suspension de tenants morosos, incidentes de security). JWT stateless
no permite revocar hasta que expire. Sanctum permite `$tenant->tokens()->delete()`.

1.1 Configurar Sanctum:
- Publicar config: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- Publicar migration de personal_access_tokens
- Ejecutar migration
- En app/Models/User.php: agregar `use HasApiTokens`

1.2 Crear AuthController (app/Http/Controllers/Api/AuthController.php):
- POST /api/auth/login → credentials → token + user + tenants[]
- POST /api/auth/logout → revocar token actual
- GET  /api/auth/me → user + tenants[] + tenant activo
- POST /api/auth/push-token → registrar Expo push token (para notificaciones)

Response de login debe incluir:
```json
{
  "user": { "id", "name", "email", "role" },
  "tenants": [{ "id", "name", "slug", "plan", "is_active" }],
  "token": "1|AbCdEfGh...",
  "token_type": "Bearer"
}
```

1.3 Middleware de tenant para API:
Crear app/Http/Middleware/ResolveTenantFromToken.php:
- Lee X-Tenant-ID del header
- Verifica que el user autenticado pertenece a ese tenant (via tenant_user pivot)
- Verifica que tenant.is_active = true (si false → 403 con "Cuenta suspendida")
- Setea el tenant activo en el container de la app para que los Global Scopes lo lean

1.4 Reorganizar routes/api.php en 3 niveles:
- Public: GET /pricing/*, GET /products (lectura), GET /content, GET /api
- Autenticadas (auth:sanctum + tenant.resolve): POST/PUT/DELETE orders, quotes, auth/me
- Admin (auth:sanctum + ability:admin + tenant.resolve): CRUD products, PATCH orders/status

---

### PRIORIDAD 2 — RATE LIMITING (proteger contra spam y DDoS basic)

En bootstrap/app.php o AppServiceProvider, definir los siguientes limiters:

2.1 Login — 5 intentos por IP por minuto (prevenir fuerza bruta):
- RateLimiter::for('login', fn($req) => Limit::perMinute(5)->by($req->ip()))

2.2 Registration de nuevo tenant — 3 por IP por hora:
- RateLimiter::for('register', fn($req) => Limit::perHour(3)->by($req->ip()))

2.3 API public (pricing, products) — 60 por minuto por IP:
- RateLimiter::for('api.public', fn($req) => Limit::perMinute(60)->by($req->ip()))

2.4 API autenticada — por user_id according to plan del tenant:
- Starter: 60/min
- Growth: 120/min
- Pro: 300/min
- RateLimiter::for('api.auth', fn($req) => Limit::perMinute($req->user()?->tenantPlanLimit() ?? 60)->by($req->user()?->id ?? $req->ip()))

2.5 Webhook de Payment Gateway — 30 por minuto (prevenir replay attacks):
- RateLimiter::for('webhooks', fn($req) => Limit::perMinute(30)->by($req->ip()))

Aplicar en routes/api.php con ->middleware('throttle:api.public') etc.

---

### PRIORIDAD 3 — TENANT ISOLATION (equivalente a Row Level Security)

RIESGO CRITICAL: Sin esto, un user de tenant A puede ver datos de tenant B
simplemente cambiando un ID en la URL. Esto es el data leak more common en SaaS.

3.1 Crear table tenants (migration):
- id, name, slug, plan, owner_id, settings (json), is_active, trial_ends_at, subscribed_at

3.2 Crear table tenant_user (migration):
- tenant_id, user_id, role (owner|admin|sales|operations|finance)
- unique(tenant_id, user_id)

3.3 Agregar tenant_id a tables de negocio (migration):
- orders, customers, products, pricing_rules, quotes, branches, business_configs,
  item_categories, colors, sizes, dimension_limits
- Nullable primero, luego backfill con tenant default (SaaS Template = tenant_id 1),
  luego NOT NULL

3.4 Crear trait BelongsToTenant (app/Models/Traits/BelongsToTenant.php):
- Registra un GlobalScope que adds WHERE tenant_id = {current_tenant_id}
- En el method boot(): usa la clase TenantContext (singleton en container)
  para obtener el tenant activo
- En el method creating(): auto-asigna tenant_id = TenantContext::current()->id

3.5 Agregar trait a TODOS los models con tenant_id:
  Order, Customer, Product, PricingRule, Quote, Branch, BusinessConfig,
  ItemCategory, Color, Size, removed

3.6 TESTS DE AISLAMIENTO (OBLIGATORIOS — no es optional):
Crear Feature test: TenantIsolationTest.php
- Crear tenant A con datos, crear tenant B con datos
- Autenticar como user del tenant A
- Check que Order::all() NO retorna orders del tenant B
- Check que GET /api/orders/{id_de_tenant_B} retorna 404 o 403
- Check que PATCH /api/orders/{id_de_tenant_B}/status retorna 403
- Un fallo en estos tests es un data leak critical

---

### PRIORIDAD 4 — FORM REQUESTS (sanitization de input)

Crear Form Requests para TODOS los endpoints de escritura.
Validar en el backend SIEMPRE, sin importar lo que verifique el frontend.

Crear los siguientes (en app/Http/Requests/Api/):
- LoginRequest: email (email, max:255), password (string, min:8, max:100)
- StoreOrderRequest: customer_name, customer_phone, items array con reglas de negocio,
  delivery_type in [pickup, shipping], branch_id exists si pickup, etc.
- UpdateOrderStatusRequest: status in [recibido, confirmado, en_operations, listo, entregado, cancelado]
- StoreQuoteRequest: model, size, quantity (1-999), customer_name, customer_phone
- StoreProductRequest: title, technique in [DTF, sublimacion, serigrafia], model_id exists, etc.
- StoreCustomerRequest: name, phone (regex mexicano), email nullable

Reglas de sanitization en TODOS:
- Strings: trim() + strip_tags() via PrependNewRulesToValidation o custom Preparefor validation
- Phone: sanitizar a solo digits + validar formato MX (10 digits)
- Numbers: integer/decimal con min/max explicits
- NUNCA $request->all() en un create/update — SIEMPRE $request->validated()

---

### PRIORIDAD 5 — POLICIES (authorization por recurso)

Crear Policies para los models principales:
- OrderPolicy: view (tenant match), create (tiene rol con orders.create), update, delete, updateStatus (solo admin/sales)
- CustomerPolicy: view, create, update, delete
- ProductPolicy: view (public para app), create/update/delete (solo admin)
- QuotePolicy: view, create, delete

En los controllers: `$this->authorize('view', $order)` — OBLIGATORIO en show() y update().

---

### PRIORIDAD 6 — MANAGEMENT DE SECRETS

6.1 Check que NINGUNA key is hardcodeada en el code:
- Buscar: grep -r "sk_live_\|pk_live_\|key_live_\|key_prod_" --include="*.php" app/ config/
- Buscar: grep -r "key_[a-zA-Z0-9]\{20,\}" --include="*.php" app/
- Si se encuentran: rotar INMEDIATAMENTE las keys comprometidas

6.2 Variables de environment requeridas en Railway (operations):
- CONEKTA_PUBLIC_KEY, CONEKTA_PRIVATE_KEY (default del tenant SaaS Template)
- STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
- ENVIA_API_KEY
- TWILIO_SID, TWILIO_TOKEN
- SENTRY_LARAVEL_DSN
- APP_KEY (ya existe, nunca comitear)

6.3 Credenciales Payment Gateway por tenant (en tenants.settings):
- El campo settings es JSON en PostgreSQL
- Las keys de Payment Gateway de cada business se guardan here
- Usar cast 'encrypted:array' en lugar de 'array' para que Laravel encripte el JSON
  automaticmente con APP_KEY:
  `protected $casts = ['settings' => 'encrypted:array'];`
- NUNCA loggear el campo settings — agregar a $hidden en el model Tenant

6.4 Rotation de tokens Sanctum:
- Al hacer logout: borrar token actual
- Al hacer login desde dispositivo nuevo: crear token nuevo (no borrar otros — un user puede
  tener varios dispositivos)
- Agregar `name` al token con info del dispositivo: createToken("mobile-{$deviceInfo}")
- Endpoint admin para revocar todos los tokens de un user (para incidentes de security)

---

### PRIORIDAD 7 — CAPTCHA EN REGISTRO Y LOGIN

7.1 Instalar hCaptcha (recommended sobre reCAPTCHA — mejor privacidad, gratis):
`composer require arc/hcaptcha`

7.2 Agregar a las siguientes rutas web (NO API — la app mobile tiene sus propias protecciones):
- POST /register (registration de nuevo tenant)
- POST /login (acceso al panel Filament)
- POST /my-account/login (portal del customer)
- POST /forgot-password

7.3 Configurar en modo invisible para no afectar UX.

7.4 Para la API mobile: en lugar de CAPTCHA, usar:
- Rate limiting estricto en /api/auth/login (ya definido en Prioridad 2)
- Device fingerprint en el header X-Device-ID (generado por la app mobile)
- Bloquear IPs con >10 intentos fallidos en 15 minutos (via cache)

---

### PRIORIDAD 8 — HTTPS Y CORS

8.1 Forzar HTTPS en production:
En bootstrap/app.php, si APP_ENV=production:
```php
if (config('app.env') === 'production') {
    URL::forceScheme('https');
}
```

8.2 Configurar CORS (config/cors.php) para la app mobile:
```php
'allowed_origins' => [
    // Web app
    'https://app.saas-template.mx',
    'https://staging.saas-template.mx',
    // Expo/React Native no sends Origin en requests nativos
    // pero yes en web preview — agregar:
    'https://snack.expo.dev',
    // NO agregar '*' nunca en operations
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Tenant-ID', 'X-App-Version', 'X-Platform'],
'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
'max_age' => 3600,
'supports_credentials' => true,
```

8.3 Headers de security HTTP (via middleware):
Crear app/Http/Middleware/SecurityHeaders.php y aplicar globalmente:
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY (prevenir clickjacking)
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy: camera=(), microphone=(), geolocation=()
- Content-Security-Policy: (para el panel web, no la API)

---

### PRIORIDAD 9 — VALIDATION DE WEBHOOKS

9.1 Webhook de Payment Gateway:
Check firma HMAC-SHA256 en cada webhook recibido.
En el controller de webhook: comparar X-Payment Gateway-Signature con hash(secret + body).
Si no coincide → return 401 inmediatamente.
NUNCA procesar un webhook sin check su firma.

9.2 Webhook de Stripe (Laravel Cashier lo maneja automaticmente):
Check que STRIPE_WEBHOOK_SECRET is configurado.
Cashier usa `Webhook::constructEvent()` que verifica la firma.

9.3 Protection contra replay attacks:
Rechazar webhooks con timestamp > 5 minutos de antigüedad.
Guardar los IDs de webhooks procesados en cache (Redis) por 24 horas.
Si el mismo ID llega dos veces → ignorar silenciosamente (log como warning).

---

### PRIORIDAD 10 — AUDIT TRAIL Y LOGGING

10.1 Instalar spatie/laravel-activitylog:
`composer require spatie/laravel-activitylog`

10.2 Publicar y ejecutar migrations:
`php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"`

10.3 Agregar tenant_id a la migration de activity_log antes de ejecutarla.

10.4 Configurar logging en los models critical (Order, Payment, Customer, Product):
```php
use Spatie\Activitylog\Traits\LogsActivity;

protected static $logAttributes = ['status', 'total', 'notes'];
protected static $logOnlyDirty = true;
```

10.5 Loggear eventos de security en el canal 'security':
- Login exitoso: IP, device, tenant
- Login fallido: IP, email intentado
- Token revocado
- Cambio de password
- Acceso denegado (403)
- Tenant suspendido/reactivado

10.6 NUNCA loggear:
- Passwords (ni hasheadas)
- Tokens de acceso
- Keys de Payment Gateway/Stripe
- Datos completos de tarjetas
- Campo settings de Tenant (contiene keys)

---

### PRIORIDAD 11 — DEPENDENCIAS Y AUTOMATION

11.1 Agregar Dependabot (GitHub):
Crear .github/dependabot.yml:
```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    labels:
      - "dependencies"
      - "security"
  - package-ecosystem: "npm"
    directory: "/saas-template-mobile"
    schedule:
      interval: "weekly"
```

11.2 GitHub Action para security audit en cada PR:
- composer audit (busca vulnerabilidades conocidas en dependencias PHP)
- npm audit (para la app mobile)
- Si hay vulnerabilidades CRITICAL → bloquear merge

11.3 Renovar APP_KEY:
- El APP_KEY actual en Railway debe tener al menos 32 chars
- No rotar en operations sin plan de re-encryption (los datos encrypted:array
  se rompen si cambias el key sin re-encriptarlos)
- Documentar fecha de creation del key actual

---

### PRIORIDAD 12 — TESTS DE SEGURIDAD

Crear test suite de security en tests/Feature/Security/:

12.1 AuthenticationTest.php:
- Login con credenciales incorrectas → 422 (no 401, para no revelar si el email existe)
- Login con rate limit → 429 after de 5 intentos
- Request a ruta protegida sin token → 401
- Request con token invalid → 401
- Request con token revocado → 401

12.2 TenantIsolationTest.php (CRITICAL):
- User de tenant A no puede ver recursos de tenant B
- Cambiar X-Tenant-ID a otro tenant al que el user no pertenece → 403
- No se puede leer tenant.settings de otro tenant

12.3 AuthorizationTest.php:
- Rol Sales no puede CRUD products
- Rol Operations no puede ver payments
- Rol Finance no puede crear orders
- Owner puede hacer todo

12.4 RateLimitTest.php:
- 5 intentos de login desde misma IP → 6to intento retorna 429
- 60 requests en 1 min a API public → 61vo retorna 429

12.5 InputValidationTest.php:
- SQL injection en campos de texto → se sanitiza correctamente
- XSS en customer_name → Blade escapa {{ }} correctamente
- Quantity negativa en order_lines → rechazado con 422
- Email malformado → rechazado con 422

Objetivo: estos tests deben correr en CI (GitHub Actions) en cada PR.
Un fallo en TenantIsolationTest es BLOQUEO de merge, sin exception.

---

## NOTAS DE IMPLEMENTATION

### Orden sugerido de implementation

```
Semana 1: Prioridad 1 (Sanctum) + Prioridad 3 parcial (tenant_id en tables)
Semana 2: Prioridad 3 completa (Global Scopes) + Prioridad 12 (tests)
Semana 3: Prioridad 2 (Rate Limiting) + Prioridad 4 (Form Requests)
Semana 4: Prioridad 5 (Policies) + Prioridad 6 (Secrets) + Prioridad 9 (Webhooks)
Semana 5: Prioridades 7, 8, 10, 11 (CAPTCHA, CORS, Audit, Dependabot)
```

### Lo que YA is bien en el proyecto

- Passwords: cast 'hashed' en User → bcrypt automatic. NO usar bcrypt() manual.
- HTTPS: Railway + Cloudflare SSL activo.
- SQL Injection: Eloquent usa queries parametrizadas.
- XSS en Blade: {{ }} escapa automaticmente. Nunca usar {!! $userInput !!}.
- CSRF: Laravel lo maneja en rutas web. API con Sanctum usa tokens en header.
- Secrets: APP_KEY en Railway env vars, nunca en .git.
- config() sobre env(): ya documentado como regla del proyecto.

### Consideration multi-tenant especial para la app mobile

La app React Native sends dos headers en cada request:
1. `Authorization: Bearer {sanctum_token}` — identidad del user
2. `X-Tenant-ID: {tenant_id}` — tenant activo seleccionado

El middleware ResolveTenantFromToken debe:
1. Check que el token es valid (Laravel hace esto)
2. Check que el user pertenece al tenant en X-Tenant-ID
3. Check que tenant.is_active = true
4. Si el tenant is suspendido: responder con JSON { "error": "tenant_suspended", "message": "Tu cuenta is suspendida. Contacta soporte." } con status 403
5. Setear el tenant en el container para que BelongsToTenant GlobalScope lo use

Este doble check (token valid + tenant activo) es lo que permite revocar acceso
inmediatamente cuando un tenant deja de pagar o se detecta actividad maliciosa.
Esto es imposible de lograr con JWT sin una blacklist.
```

---

## Checklist de Security Pre-Launch SaaS

### Sesiones y Cookies
- [x] Cookies encriptadas — activo por defecto en Laravel 12
- [x] `SESSION_HTTP_ONLY=true` — configurado en `.env`
- [x] `SESSION_SAME_SITE=lax` — configurado en `.env`
- [x] `SESSION_DRIVER=database` — sesiones en DB, no en files
- [ ] `SESSION_ENCRYPT=true` — agregar en Railway (operations)
- [ ] `SESSION_SECURE_COOKIE=true` — agregar en Railway (operations)

### Signed URLs y CSRF
- [x] CSRF activo en rutas web — incluido en grupo `web` por defecto
- [x] CSRF explicit en panel Filament — `VerifyCsrfToken` en `AdminPanelProvider`
- [x] Signed URLs en PDFs de orders — `->middleware('signed')` en `feature/order-pdf`
- [ ] `Crypt::encrypt()` en model Tenant — pendiente Fase 1 SaaS (`encrypted:array` en `settings`)

### Authentication y Authorization
- [ ] Sanctum configurado y funcionando
- [ ] Rutas API organizadas en 3 niveles (public, autenticada, admin)
- [ ] Middleware ResolveTenantFromToken implementado y testeado
- [ ] Policies implementadas para Order, Customer, Product, Quote
- [ ] Tests de aislamiento de tenant pasando (TenantIsolationTest)

### Datos y Privacidad
- [ ] tenant_id en todas las tables de negocio
- [ ] Global Scopes en todos los models con tenant_id
- [ ] tenants.settings con cast `encrypted:array`
- [ ] Ninguna key hardcodeada en code (grep verificado)
- [ ] Audit trail con spatie/laravel-activitylog + tenant_id
- [ ] Proceso de deletion de datos (90 days post-cancellation)

### Infraestructura
- [x] HTTPS activo — Railway + Cloudflare SSL
- [x] Trusted proxies configurado — `trustProxies(at: '*')` en `bootstrap/app.php`
- [ ] `SESSION_ENCRYPT=true` + `SESSION_SECURE_COOKIE=true` en Railway
- [ ] Headers de security HTTP configurados
- [ ] CORS configurado con whitelist explicit (NO *)
- [ ] Cloudflare WAF activo (reglas basic habilitadas)
- [ ] Rate limiting: login (5/min), registration (3/hora), API public (60/min), API auth (por plan)

### Secrets y Claves
- [ ] Todas las keys en Railway env vars (Stripe, Payment Gateway, Envia, Twilio)
- [ ] Dependabot configurado para composer + npm
- [ ] composer audit pasa sin vulnerabilidades critical
- [ ] Webhook signatures verificados (Payment Gateway + Stripe)

### Monitoreo
- [ ] Sentry configurado con tenant_id en contexto de errores
- [ ] Logging de eventos de security en canal separado
- [ ] Health check endpoint en /api/health
- [ ] Alertas de Sentry a Slack/email configuradas

### Tests
- [ ] TenantIsolationTest — BLOQUEA CI si falla
- [ ] AuthenticationTest pasando
- [ ] RateLimitTest pasando
- [ ] InputValidationTest pasando

---

*Creado: 2026-02-19*
*Basado en: perspectiva de pentester + arquitectura SaaS Template SaaS*
