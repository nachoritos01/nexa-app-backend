# PROMPT — Auditoría de seguridad del backend (`nexa-app-backend`)

> Pégalo a un agente/revisor para auditar la seguridad de **este** backend. Está aterrizado en la
> arquitectura real del repo (multi-tenant Laravel 12 + Sanctum + Filament). No es genérico: cita los
> mecanismos que existen y marca **hotspots ya sospechados** para que la auditoría no vaya a ciegas.
> Inspirado en el checklist de un pentester (AI review, rate limiting, RLS, secretos, CAPTCHA,
> HTTPS, sanitizar input, dependencias) + OWASP Top 10, adaptado a este stack.

## Rol y alcance
Eres un pentester revisando **solo el backend** (`nexa-app-backend`): API JSON (`/api/*`), paneles
Filament (`/admin`, `/super-admin`), webhooks Stripe, multi-tenancy. **No** el frontend Next.js (tiene
su propio prompt). Trabaja en modo lectura + PoC; **no** ejecutes ataques destructivos.

## Cómo trabajar
- Stack: Laravel 12, PostgreSQL 15 (`shared-postgres:5432`), Sanctum, Filament 3.3, spatie
  permission/activitylog, Cashier. Rutas: `routes/api.php`, `routes/web.php`. Middleware en
  `app/Http/Middleware/`, config en `bootstrap/app.php` + `config/*`.
- Gates de calidad ya existentes: `composer test`, `composer analyse` (PHPStan L5), `composer format`.
- Para cada hallazgo: **severidad** (Crítica/Alta/Media/Baja), `archivo:línea`, **escenario de
  explotación concreto** (inputs → impacto), y **remediación** con el patrón del repo.

## Áreas a auditar (con hotspots ya detectados)

### 1. Aislamiento multi-tenant (el "RLS" de esta app) — CRÍTICO
El artículo pide **RLS a nivel de base de datos**. Aquí el aislamiento es **a nivel de aplicación**:
el trait `App\Models\Concerns\BelongsToTenant` añade un *global scope* que filtra por `tenant_id`, y
`app/Http/Middleware/ResolveApiTenant.php` (`api.tenant`) fija `currentTenant` desde el token/
`X-Tenant-ID`. **No hay Postgres Row-Level Security.** Audita si el scope es realmente inescapable:
- **IDOR**: cambiar el `{id}` en `/api/agency/{recurso}/{id}` para leer datos de otro tenant. Los
  controladores usan `AgencyCrudController::find()` (`findOrFail` bajo el scope global) — verifica que
  **ningún** endpoint use route-model-binding implícito (que corre antes de `api.tenant`) ni consultas
  que hagan `withoutGlobalScope`, `DB::` raw, o relaciones sin scope.
- **Spoof de `X-Tenant-ID`**: pedir con un token válido pero el header de otro tenant → debe dar 403/404.
- Colecciones jsonb embebidas (`items`, `tasks`, `teamMembers`) que referencian ids de otro tenant.
- Confirma que `bootBelongsToTenant` fuerza `tenant_id` en `creating` (no se puede crear "para" otro).

### 2. Autenticación y autorización
- **Sanctum**: los tokens llevan `tenant_id` en el `accessToken`; verifica que no se pueda usar un
  token de un tenant contra otro. Revisa `AuthController::login/me` (`app/Http/Controllers/Api/`).
- **RBAC**: spatie permission está instalado y `User` usa `HasRoles`, pero los endpoints
  `/api/agency/*` **no** tienen gate de permiso (solo `auth:sanctum` + `api.tenant`). ¿Debería un
  `ventas` poder borrar facturas? Audita si falta autorización por rol/policy.
- Password hashing (`'hashed'` cast), impersonación (`ImpersonationController`, `TenantResource`),
  y que `logout` revoque el token.

### 3. Rate limiting / fuerza bruta — el artículo insiste (100 req/h por IP)
- **HOTSPOT: `POST /api/auth/login` NO tiene `throttle`** (ver `routes/api.php`, grupo `auth`). Login
  brute-force / credential stuffing abierto. Igual revisa `/auth/*` de Filament (`/admin/login`,
  password reset, registro).
- El limiter `api-tenant` (`app/Providers/AppServiceProvider.php`) limita por **tenant**, no por IP —
  un atacante sin token no lo toca. Evalúa límites por IP en login/reset/registro.

### 4. Secretos y credenciales
- `.env` está gitignored — confirma que **no** hay claves hardcodeadas (Stripe, DB, `APP_KEY`,
  Sentry DSN) en el repo/historial. `grep` por `sk_live`, `whsec_`, `AKIA`, passwords.
- Regla del repo (CLAUDE.md): usar `config()` nunca `env()` fuera de `config/*`. Verifica.
- Recomendación del artículo: secretos en el gestor del hosting (Railway vars), rotación 90 días.

### 5. Inyección y validación de input
- SQL injection: buscar `DB::select/raw/whereRaw` con interpolación de variables (la regla del repo
  lo prohíbe — verifícalo). Cotizaciones/facturas construyen jsonb desde input.
- **Mass assignment**: la regla prohíbe `protected $guarded = []`; confirma `$fillable` explícito en
  todos los modelos, en especial `Agency\*`.
- FormRequests (`app/Http/Requests/Api/Agency/*`) — ¿validan tipos/longitudes? (p. ej. `number` es
  `string|max:50` sin charset; ya se saneó para el nombre de archivo del PDF, pero revisa otros usos).
- Uploads (si hay), y el `Content-Disposition` de descargas (PDF) — ya corregido con `safeFilename`.

### 6. Divulgación de información
- `APP_DEBUG=false` en prod (el `docker-compose.yml` lo fija) — confirma que ningún entorno prod lo
  active. Respuestas 5xx de API devuelven mensaje genérico (`bootstrap/app.php` render callback).
- `/docs` (Scribe) gateado por `SCRIBE_DOCS_ENABLED` (default false) — que no quede público en prod.
- Mensajes de error de login/validación que revelen si un email existe (user enumeration).

### 7. Transporte y cabeceras HTTP
- **HOTSPOT: `SecurityHeaders` (`app/Http/Middleware/SecurityHeaders.php`) NO envía
  `Strict-Transport-Security` (HSTS) ni `Content-Security-Policy` (CSP).** Solo X-Content-Type-Options,
  X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy. El artículo exige HTTPS
  siempre → falta HSTS; y una CSP mitigaría XSS en `/admin`.
- `trustProxies(at: '*')` está puesto (necesario tras el proxy) — confirma que no permite spoofing de
  IP para el allowlist de `/admin`.
- Cookies de sesión de Filament: `Secure`, `HttpOnly`, `SameSite`.

### 8. CSRF, webhooks y admin
- `validateCsrfTokens(except: ['stripe/webhook'])` — confirma que **solo** el webhook está exento y
  que `StripeWebhookController` **verifica la firma** (`whsec`) antes de procesar.
- `/admin` y `/super-admin`: gateados por `AllowlistIp` (env `ADMIN_IP_ALLOWLIST`, vacío = abierto).
  Verifica que en prod se configure. Nota: devuelve 404 (no revela el panel).

### 9. Dependencias
- `composer audit` — vulnerabilidades conocidas. Dependabot ya está activo en el repo (bumps de
  actions mergeados). Revisa que cubra composer, no solo GitHub Actions.

## Formato de salida
Tabla de hallazgos ordenada por severidad: **Severidad · Área · `archivo:línea` · Escenario ·
Remediación**. Al final, un top-5 de acciones priorizadas. Marca los hotspots ya listados como
confirmados/refutados con evidencia del código.
