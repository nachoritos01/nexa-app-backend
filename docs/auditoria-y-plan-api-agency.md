# Auditoría y Plan de Simplificación — `/api/agency/*`

> **Objetivo del proyecto:** dejar la API sirviendo **únicamente** las superficies
> first-party `/api/agency/*` **y `/api/auth/login|me|logout`** (ambas consumidas por el
> panel Next.js), **eliminando** las superficies **Pública** (`/api/quotes`,
> `/api/content`, `/health`) y **Mobile/V1** (`/api/v1/*`, `/api/auth/push-token`)
> **sin corromper el código ni la base de datos**.
>
> **Autor:** Auditoría Senior Backend Architect · **Fecha:** 2026-07-03
> **Revisión:** 2026-07-04 — verificación cruzada contra panel y landing;
> corregida la Oleada 1 (**conservar `/api/auth/*`** — el panel hace login ahí).
> **Alcance revisado:** `routes/api.php`, `bootstrap/app.php`,
> `app/Http/Controllers/Api/**`, `app/Http/Middleware/**`, `app/Models/Agency/**`,
> `app/Http/Requests/Api/Agency/**`, `app/Http/Resources/Agency/**`,
> `app/Services/Agency/**`, `tests/Feature/Api/**`.

---

## ⚠️ HALLAZGO DECISIVO (leer antes que nada)

La instrucción es "eliminar lo que no es necesario". El análisis del código real
demuestra que **lo eliminable es solo la capa HTTP (rutas + controllers + requests +
resources) de las superficies Pública y V1. Los modelos y las tablas NO se pueden
tocar**, porque son compartidos:

| Recurso "de V1" | ¿Lo usa solo la API? | Evidencia real |
|---|---|---|
| `App\Models\Order` | **NO** | referenciado en **24 archivos** fuera de `Api/V1` (Filament `OrderResource`, `OrdersChart`, `PendingActions`, `Onboarding`, billing, loyalty…) |
| `App\Models\Customer` | **NO** | **11 archivos** (Filament `CustomerResource`, portal, loyalty) |
| `App\Models\Item` | **NO** | **6 archivos** (Filament `ItemResource`, órdenes) |
| `App\Models\Payment` | **NO** | **6 archivos** (Filament `PaymentsRelationManager`, billing) |
| `App\Models\Location` | **NO** | Filament `LocationResource` |
| `App\Models\Quote` (público) | **NO** | usado por `OrderPdfController`, `CustomerPortalController` y `App\Services\PdfGenerator` (`AgencyPdfGenerator` importa **su propio** `App\Models\Agency\Quote`; su dependencia real con e-commerce es el service, fila siguiente) |
| `App\Services\PdfGenerator` (público) | **NO** | `AgencyPdfGenerator.php:8` **hace `use App\Services\PdfGenerator;`** → **borrarlo rompe el PDF de la agencia** |
| `/api/auth/login\|me\|logout` (`AuthController`) | **NO** | **es el mecanismo de autenticación del panel** (`panel/lib/api.ts:182,195`); el RBAC del panel depende del `user.role`/`permissions` que devuelven `login`/`me` (backend PR #13). Solo `POST /auth/push-token` es mobile-only y borrable |

**Conclusión:** esto NO es un borrado de dominio, es una **poda de superficie de API**.
Borrar rutas/controllers V1 y públicos es seguro; borrar modelos, servicios de PDF o
correr migraciones `down` **corrompería Filament, el portal de cliente y el propio
`/api/agency/*`**. El plan de la Parte B respeta esta frontera de forma estricta y
**no incluye ninguna migración destructiva** (regla del proyecto: sin migraciones
destructivas sin backup).

---

# PARTE A — AUDITORÍA

## 1. RESUMEN EJECUTIVO

**Score de calidad general: 7 / 10.**
Es un módulo **sólido y bien testeado** para su tamaño, con decisiones deliberadas y
documentadas (los comentarios explican el *por qué*, no el *qué*). Pierde puntos por
inconsistencias transversales (paginación, envelope de respuesta/error) y por ausencia
de una capa de aplicación real: la lógica vive en controllers/base class.

**3 fortalezas actuales**
1. **Aislamiento multi-tenant en profundidad.** `BelongsToTenant` (global scope) +
   `ResolveApiTenant` + `find()` ejecutado *dentro* del ciclo de request
   (`AgencyCrudController::find()`, comentado en el propio código) evitan fugas
   cross-tenant por route-model binding prematuro. Hay test dedicado
   (`Feature/TenantIsolationTest.php`).
2. **Cobertura de tests real y específica.** 10 archivos bajo
   `tests/Feature/Api/Agency/` (RBAC, smoke de todos los recursos, PDF, send, activity,
   settings, client, project). No es un módulo "a ciegas".
3. **Frontera de seguridad clara.** Autorización por permiso Spatie derivada del método
   HTTP (`AuthorizeAgency`), throttling por tenant, y ocultación de trazas 5xx en prod
   (`bootstrap/app.php` `$exceptions->render`).

**3 riesgos críticos a corregir primero**
1. **`index` sin paginación (RIESGO ALTO de performance).** Tanto
   `AgencyCrudController::index()` (`->get()`) como `ClientController::index()`
   (`->orderBy('name')->get()`) devuelven **todas las filas del tenant**. Con un tenant
   grande, `GET /api/agency/clients|projects|invoices` cargará miles de filas en memoria
   y JSON. El `/activity` sí pagina (máx 50) — inconsistencia + bomba de escalado.
2. **Envelope de error inconsistente.** Middlewares devuelven `{error, code}`
   (`ResolveApiTenant`, `AuthorizeAgency`, `EnsureApiAccess`), pero
   `QuoteController::send()` devuelve `{message}` y la validación Laravel devuelve
   `{message, errors}`. El cliente Next.js no tiene un contrato de error único.
3. **Shape de respuesta divergente entre superficies.** Agency emite `{data:…}` sin
   `meta`; V1 emite `{data, meta:{total,page,per_page,last_page}}`. Al quedarnos solo con
   Agency conviene fijar UN contrato antes de que el panel dependa del actual.

---

## 2. ANÁLISIS DE CAPAS (Controllers / Services / Repositories)

| Capa | Veredicto | Detalle |
|---|---|---|
| Controllers | **MEJORAR** | Delgados y correctos, pero **contienen la (poca) lógica que hay** |
| Base `AgencyCrudController` | **MEJORAR** | Buen DRY, pero mezcla CRUD genérico + concerns HTTP |
| Service Layer | **REFACTORIZAR** | No existe para CRUD; solo hay `AgencyPdfGenerator` |
| Repository Layer | **N/A** | No existe (acceso directo a Eloquent) — aceptable a esta escala |

- **¿`AgencyCrudController` viola SRP?** Parcialmente. Combina tres responsabilidades:
  (a) traducción HTTP (status 201/204), (b) orquestación de persistencia
  (`create()->refresh()`, `modify()`), y (c) utilidades (`safeFilename()`). Para un CRUD
  fino es un trade-off **razonable**, no un pecado. El olor real es que `safeFilename()`
  —de dominio "PDF filename"— vive en la base CRUD; pertenece a `AgencyPdfGenerator`.
- **¿Existe Service Layer real?** No para el CRUD. La lógica de negocio que existe está
  en la base class y en los controllers "especiales":
  `SettingsController::update()` (merge JSON + `createOrFirst`) y
  `QuoteController::send()` (generar PDF + resolver cliente + mailear). Eso son **casos
  de uso disfrazados de método de controller**.
- **¿Repositories desacoplados?** No hay; los controllers hablan Eloquent directo
  (`Client::query()->findOrFail()`). A esta escala es **BIEN** (un repo sería
  sobre-ingeniería); el aislamiento ya lo da el global scope.
- **Deuda concreta — `ClientController` no reutiliza la base:** extiende `Controller`
  y **reimplementa** `index/show/store/update/destroy` + un `findScoped()` que es un
  clon de `AgencyCrudController::find()`. Duplicación de ~40 líneas. Justificada hoy por
  el mapeo español, pero es el candidato #1 a unificar.

**Ejemplo de refactor (casos de uso):**
```php
// hoy: lógica en el controller
public function send(string $id, AgencyPdfGenerator $pdf): JsonResponse { … 20 líneas … }

// objetivo: caso de uso testeable en aislamiento
final class SendAgencyDocument {
    public function __construct(private AgencyPdfGenerator $pdf, private Mailer $mail) {}
    public function forQuote(Quote $quote): void { /* … */ }
}
```

---

## 3. REST API QUALITY

| Regla | Estado | Nota |
|---|---|---|
| 201 en create | ✅ | `AgencyCrudController::create()` fija 201 explícito |
| 204 en delete | ✅ | `destroy()` → `response()->noContent()` |
| 422 en validación | ✅ | FormRequests + `send()` (cliente sin email) |
| 409 en conflicto | ❌ | No se usa (p.ej. `SettingsController` resuelve la carrera en vez de 409 — correcto aquí) |
| URLs sin verbos | ⚠️ | `quotes/{id}/pdf` y `quotes/{id}/send` son **acciones**, no recursos. Aceptable como sub-recurso, pero es el único desvío REST |
| Paginación `{total,page,limit,totalPages}` | ❌ | **Agency no pagina** (`->get()`). Solo `/activity` pagina, y con claves distintas (`meta` de Laravel, no `{total,page,limit,totalPages}`) |
| Response shape consistente | ❌ | Agency `{data}` vs V1 `{data, meta}` vs Settings `{data}` manual |
| Versionado | ⚠️ | Agency **no tiene prefijo de versión** (`/api/agency`, no `/api/v1/agency`). Si el contrato cambia no hay `/v2` |
| Orden de rutas `pdf/send` antes de `apiResource` | ✅ pero **frágil** | Está **documentado** (`routes/api.php:92`), pero depende del orden textual. Un `apiResource` movido arriba rompería `/{id}/pdf` silenciosamente |

**Veredicto:** funcional y semántico en su mayoría; los dos agujeros reales son
**paginación ausente** y **falta de versión** en el prefijo agency.

---

## 4. AUTENTICACIÓN Y AUTORIZACIÓN

Cadena: `auth:sanctum → api.tenant (ResolveApiTenant) → agency.access (AuthorizeAgency) → throttle:api-tenant` (`routes/api.php:86`).

| Punto | Risk | Análisis |
|---|---|---|
| Usar `$user->can()` (guard Sanctum) en vez del middleware `permission:` de Spatie | **BAJO** | **Correcto y bien razonado** (`AuthorizeAgency` docblock): el middleware `permission:` de Spatie resuelve contra el guard por defecto y daría 403 a usuarios de token válidos. `$user->can()` resuelve contra el guard activo. Decisión acertada. |
| Granularidad `GET→agency.view` / escritura→`agency.manage` | **MEDIO** | Suficiente para un back-office chico, pero es **binario**: quien puede editar un cliente puede borrar facturas. No hay `agency.clients.manage` vs `agency.invoices.manage`. |
| Usuario con `agency.view` intenta `PATCH` | **BAJO** | `$request->isMethodSafe()` → PATCH no es safe → exige `agency.manage` → 403. Correcto. **No hay doble verificación** (ni policies por modelo), así que la única barrera es el permiso global. |
| `BelongsToTenant` global scope como aislamiento | **BAJO–MEDIO** | Fuerte por defecto. Riesgo residual: cualquier query que use `withoutGlobalScopes()`, `DB::table()` cruda, o relaciones no scopeadas puede filtrar. `ActivityController` **refuerza** filtrando `tenant_id` explícito además del scope (buen patrón defensivo). Recomendado: test que garantice que ningún endpoint agency responde datos de otro tenant. |
| Tenant suspendido/inactivo | **BAJO** | `ResolveApiTenant` corta con 403 + `{error, code}`. Status correcto. |

---

## 5. VALIDACIÓN Y MAPEO DE CAMPOS

- **Patrón `mapped()`** — Hay **dos estrategias**:
  - `AgencyFormRequest::mapped()` = `Str::snake()` automático (proyectos, servicios,
    quotes, invoices, expenses, suppliers, team). **Escala bien**, cero mantenimiento.
  - `StoreClientRequest::mapped()` / `UpdateClientRequest` = **mapa explícito**
    español→columna (`telefono→phone`, `valorPotencial→potential_value`, `rfc→tax_id`).
    **NO escala igual**: cada campo nuevo obliga a tocar 3 sitios (rules, mapa del
    request, y `ClientResource` de vuelta). Es el precio de mantener nombres en español
    de cara al panel.
- **¿Alternativa mejor?** Sí, dos opciones:
  1. Definir el mapa **una sola vez** en el modelo `Client` (p.ej. `const FIELD_MAP`) y
     que request y resource lo consuman → elimina la triple duplicación.
  2. **DTOs tipados** (`spatie/laravel-data`) en vez de `array<string,mixed>`: darían
     autocompletado, `mapped()` gratis y validación tipada. Es la mejora "correcta" a
     mediano plazo; hoy los arrays asociativos **funcionan pero son opacos** (PHPStan no
     ve las claves).
- **¿`mapped()` puede fallar en silencio?** Sí: si `rules()` valida un campo que **no**
  está en el mapa de `StoreClientRequest`, se descarta silenciosamente (el `foreach`
  solo copia claves presentes en `$map`). No hay test que detecte "campo validado pero
  no persistido". **Gap de test real.** El `AgencyFormRequest` genérico no sufre esto
  (mapea todo lo validado).

---

## 6. ERROR HANDLING

- **¿Handler global?** Sí, parcial, en `bootstrap/app.php`
  (`$exceptions->render`): para `api/*` con `debug=false`, oculta 5xx tras
  `{message: "Ocurrió un error…"}` y deja pasar 4xx. **Bien**, pero solo normaliza 5xx.
- **Inconsistencia de formato (el problema real):** conviven **tres envelopes**:
  - `{error, code}` → `ResolveApiTenant`, `AuthorizeAgency`, `EnsureApiAccess`
  - `{message}` → `QuoteController::send()` (422 cliente sin email)
  - `{message, errors}` → validación de Laravel (FormRequests)
- **Tenant suspendido:** 403 `{error, code: TENANT_...}` — status correcto.
- **Formato de error estándar propuesto** (adoptar para toda la API agency):
```json
{
  "error": {
    "code": "AGENCY_CLIENT_NO_EMAIL",
    "message": "El cliente no tiene un email registrado.",
    "details": { "field": "email" }
  }
}
```
Implementar en un único punto (`$exceptions->render`) mapeando `ValidationException`,
`AuthenticationException`, `HttpException` y las excepciones de dominio a este shape.

---

## 7. PERFORMANCE Y CACHING

- **N+1 en Resources:** **BAJO / no aplica.** `AgencyResource` serializa
  `$this->resource->toArray()` sin cargar relaciones; las colecciones embebidas
  (`items`, `tasks`, `teamMembers`) son **columnas jsonb** en la propia fila, no
  relaciones. `ClientResource` idem. No hay lazy-load oculto. **Punto fuerte.**
- **El verdadero cuello de botella es el `index` sin paginación** (ver §1/§3): el riesgo
  no es N+1, es **traer N filas completas**.
- **`/activity`:** paginado, `perPage` máx 50, `->with('causer')` (evita N+1 del causer).
  Suficiente para volumen moderado; para auditoría de alto volumen faltará filtro por
  rango de fechas e índice en `(log_name, tenant_id, created_at)`.
- **`AgencySetting` (blob JSON):** correcto como singleton pequeño. Si el objeto de
  settings crece mucho (imágenes base64, historiales) el `array_merge` + reescritura
  completa en cada `PUT` se vuelve caro y pierde granularidad de auditoría. Límite
  práctico: mantenerlo en configuración, no en datos.
- **Candidatos a cache:** `GET /settings` (TTL 60–300 s, invalidar en `update`) y
  catálogos casi-estáticos (`services`, `suppliers`, TTL 60 s por tenant). El resto es
  transaccional; no cachear.

---

## 8. CLEAN ARCHITECTURE GAPS

- **Modelos Eloquent:** en Agency son **entidades ORM casi puras** (fillable, casts,
  traits) — sin lógica de negocio. Contrasta con el dominio e-commerce
  (`Order::confirm()/complete()/recalculateTotals()`). Para Agency está **bien** dado
  que hay poca lógica; el riesgo es que la lógica que aparezca termine en controllers.
- **¿Casos de uso / application services?** No. Flujo directo `Controller → Model`.
  `send()` y `settings.update()` son los casos de uso escondidos (ver §2).
- **`AgencyPdfGenerator`:** es un **Service real** (inyectable, tipado a modelos Agency),
  no un helper procedural. **Bien.** Pero usa la **facade** `Pdf::` internamente y
  **depende de `App\Services\PdfGenerator`** (`AgencyPdfGenerator.php:8`) — acoplamiento
  a la superficie e-commerce que conviene documentar (impacta el plan de borrado: ese
  service **no** se toca).
- **Dependency injection:** mixta. Los services se inyectan por método
  (`send(string $id, AgencyPdfGenerator $pdf)`) — explícito y testeable. Pero hay
  **statics/facades** escondidos: `Mail::to(...)` en `send()`, `Pdf::` en el generator,
  y el helper global `currentTenant()`. No rompe nada, pero dificulta el mock.
- **Reemplazar Spatie Activitylog:** **acoplamiento MEDIO.** `ActivityController` importa
  `Spatie\Activitylog\Models\Activity` directo y `LogsAgencyActivity` usa el trait de
  Spatie. Cambiar de librería tocaría: el trait, el controller y `ActivityResource`. Una
  interfaz `AuditLog` desacoplaría, pero es sobre-ingeniería salvo migración real.

---

## 9. TESTING GAPS

**Lo que ya está cubierto (bien):** RBAC (`AgencyRbacTest`), smoke de todos los recursos
(`AgencyResourcesSmokeTest`), PDF y send de quote/invoice, activity, settings, client,
project. Base común `AgencyTestCase`.

**Gaps por impacto/riesgo (prioridad descendente):**
1. **Aislamiento cross-tenant por endpoint (ALTO).** Existe `TenantIsolationTest` genérico,
   pero falta el test explícito "tenant A hace `GET/PUT/DELETE` sobre un id de tenant B →
   404". Es la garantía #1 del sistema; el `find()` manual (sin route-model binding)
   **debe** tener este test por cada verbo.
2. **`mapped()` que descarta campos en silencio (MEDIO).** Test que envíe todos los
   campos de `StoreClientRequest` y verifique que **todos** persisten (evita la fuga
   descrita en §5).
3. **Cadena de middleware completa (MEDIO).** Test que recorra
   `sin token → 401`, `token sin tenant → 403 INVALID_TOKEN`,
   `agency.view intentando POST → 403`, `tenant suspendido → 403`. Hoy están sueltos.
4. **Paginación / contrato de respuesta (MEDIO)** — cuando se añada paginación, fijar el
   shape con un test para no romper el panel.

**Partes difíciles de testear sin refactor:** `send()` (mezcla PDF real + Mail);
extraerlo a un caso de uso lo haría testeable con `Mail::fake()` de forma limpia.

---

## 10. ROADMAP DE MEJORAS (calidad — independiente de la simplificación)

| Oleada | Mejora | Esfuerzo | Impacto |
|---|---|---|---|
| **Semana 1** | Paginar todos los `index` de agency (mismo `{data, meta}` que V1) | 3 h | **ALTO** |
| **Semana 1** | Envelope de error único en `$exceptions->render` | 3 h | **ALTO** |
| **Semana 1** | Test aislamiento cross-tenant por verbo + test `mapped()` completo | 4 h | **ALTO** |
| **Semana 1** | Mover `safeFilename()` a `AgencyPdfGenerator` | 1 h | BAJO |
| **Mes 1** | Unificar `ClientController` sobre `AgencyCrudController` (mapa único en el modelo) | 5 h | MEDIO |
| **Mes 1** | Extraer casos de uso `SendAgencyDocument` y `UpdateAgencySettings` (+ `Mail::fake` tests) | 8 h | MEDIO |
| **Mes 1** | Prefijar rutas a `/api/agency/v1/*` para tener camino a `/v2` | 2 h | MEDIO |
| **Trimestre** | DTOs tipados (`spatie/laravel-data`) reemplazando `mapped()`/arrays | 16 h | MEDIO |
| **Trimestre** | Interfaz `AuditLog` desacoplando Spatie Activitylog | 10 h | BAJO |

---

# PARTE B — PLAN DE DESARROLLO: SIMPLIFICAR A SOLO `/api/agency/*`

> Esta es la **tarea final**. El plan poda **solo la capa HTTP** de las superficies
> Pública y V1. **No borra modelos, no borra servicios compartidos, no corre migraciones
> destructivas.** Cada paso indica exactamente qué se toca y por qué es seguro.

## B.0 — Principios de seguridad (invariantes)

1. **CERO migraciones `down` / `drop`.** Las tablas (`orders`, `customers`, `items`,
   `payments`, `locations`, `quotes`, …) siguen vivas: las usan Filament, el portal y
   billing. La BD **no cambia**.
2. **CERO borrado de modelos y servicios compartidos.** Se conservan `App\Models\Order`,
   `Customer`, `Item`, `Payment`, `Location`, `Quote` y `App\Services\PdfGenerator`
   (este último lo importa `AgencyPdfGenerator`).
3. Solo se elimina lo cuyo **único consumidor** es la ruta pública/V1.
4. Cada oleada termina en **verde**: `composer test`, `composer analyse` (PHPStan 5),
   `composer format`, y verificación manual de que Filament y `/api/agency/*` responden.

## B.1 — Inventario de borrado (qué es seguro eliminar)

**Superficie Pública** (`routes/api.php:25-51`):
| Ruta | Controller | ¿Seguro borrar? |
|---|---|---|
| `GET /` | closure inline | Sí (opcional: dejar un ping) |
| `GET /health` | `Api\HealthController` | **Depende** — ver B.4 (Railway/monitor puede usarlo) |
| `POST /quotes`, `GET/POST /quotes/{q}/pdf` | `Api\QuoteController` | Sí (controller); **NO** el modelo `Quote` ni `PdfGenerator` |
| `GET /content`, `GET /content/{type}` | `Api\ContentController` | Sí, si nadie externo lo consume |

**Superficie Mobile/V1** (`routes/api.php:53-80`):
| Ruta | Controller | ¿Seguro borrar? |
|---|---|---|
| `/api/auth/login`, `/api/auth/me`, `/api/auth/logout` | `Api\AuthController` | **NO — CONSERVAR.** Los usa el panel (`panel/lib/api.ts:182,195`). Además hoy viven dentro de `if (hasModule('api'))`: con `MODULE_API=false` el login del panel se apagaría → **sacarlos del bloque** (mismas URLs, cero cambios en el panel) |
| `POST /api/auth/push-token` | `Api\AuthController::pushToken` | Sí (método + `StorePushTokenRequest`) — mobile-only |
| `/api/v1/orders,customers,items,payments,dashboard` | `Api\V1\*` | Sí (controllers + `Requests/Api/V1/*`; no hay Resources V1 — ver B.3.3) |

**Middleware que queda huérfano tras quitar V1:**
- `api.pro` → `EnsureApiAccess` (solo lo usa el grupo `/v1`). Se puede borrar el alias
  (`bootstrap/app.php`) y la clase. **Verificar** que ninguna otra ruta lo referencie.
- `throttle:login` → **sigue en uso**: lo usa `/api/auth/login`, que se conserva. El
  limiter `login` (`AppServiceProvider.php:71`) **se queda**.

**Lo que se CONSERVA aunque parezca de V1:**
- `ResolveApiTenant` (`api.tenant`) → lo usa **también** agency.
- Modelos `Order/Customer/Item/Payment/Location/Quote` → Filament + portal + billing.
- `App\Services\PdfGenerator` → dependencia de `AgencyPdfGenerator.php:8`.
- **`AuthController` (solo pierde `pushToken`) y `LoginRequest`** → login del panel.
- `config/modules.php` `'api'` flag → hoy envuelve el grupo V1 (`hasModule('api')`).

**Dato verificado (2026-07-04):** la **landing no llama al backend** (cero referencias a
`api/quotes`/`api/content`/`API_URL` en su código) → la superficie pública no tiene
consumidor first-party en el workspace; el único riesgo residual es un consumidor
externo/mobile desconocido (lo que B.4 pide confirmar).

## B.2 — Oleada 1 · Quick wins sin riesgo (½ día)

**Meta: apagar las superficies sin borrar archivos todavía (reversible en 1 commit).**

1. En `routes/api.php`:
   - **Mover** `Route::prefix('auth')` (con `login`/`me`/`logout` — mismas URLs, cero
     cambios en el panel) **fuera** del `if (hasModule('api'))`, eliminando solo la
     ruta `push-token`.
   - **Eliminar** el grupo `Route::prefix('v1')` completo y el `if (hasModule('api'))`
     ya vacío.
   - **Eliminar** el bloque Público (líneas 25-51), **conservando** opcionalmente
     `GET /health` (ver B.4). Quitar los `use` de controllers ya no usados.
2. Ejecutar `php artisan route:list --path=api` → debe listar `api/agency/*`
   **+ `api/auth/login|me|logout`** (+ `health` si se conserva).
3. **Los tests de las superficies borradas fallarán** — es esperado. En esta oleada,
   **skipear** (no borrar) los tests `tests/Feature/Api/V1/*` y `HealthCheckTest` (si se
   retira `/health`) con
   `$this->markTestSkipped('API pública/V1 retirada — ver docs/auditoria-y-plan-api-agency.md')`.
   `AuthApiTest` **se mantiene activo** (el auth se conserva) — solo skipear/ajustar su
   test de `push-token`. `ApiErrorHandlingTest` **no requiere skip**: verificado que solo
   prueba rutas sintéticas `api/_test/boom` definidas en el propio test.
4. **Gate de verificación:** `composer test` verde (agency intacto); levantar
   `php artisan serve` y comprobar con MCP Chrome DevTools que **Filament admin carga**,
   `/api/agency/clients` responde 200 con token válido y **`POST /api/auth/login`
   responde 200 con token** (login del panel intacto).

> Resultado: la API ya sirve **solo agency + auth del panel**, sin haber borrado una
> sola clase ni tocado la BD. Punto de rollback limpio.

## B.3 — Oleada 2 · Borrado físico de la capa HTTP muerta (1 día, con tests)

Solo tras validar la Oleada 1. Borrar **archivos**, no modelos:

1. **Controllers:** `app/Http/Controllers/Api/V1/` (carpeta entera),
   `Api/QuoteController.php`, `Api/ContentController.php`.
   `Api/HealthController.php` solo si se retira `/health` (ver B.4).
   **`Api/AuthController.php` se CONSERVA** (login del panel) — solo eliminar su método
   `pushToken`.
2. **Requests:** `app/Http/Requests/Api/V1/` (carpeta) y `Api/StorePushTokenRequest.php`.
   **`Api/LoginRequest.php` se CONSERVA** (lo usa `login`).
3. **Resources:** **no-op verificado (2026-07-04)** — `app/Http/Resources/` solo
   contiene `Agency/` y los controllers V1 no usan Resources. Nada que borrar.
4. **Middleware:** `app/Http/Middleware/EnsureApiAccess.php` + su alias `api.pro` en
   `bootstrap/app.php:` (bloque `$middleware->alias([...])`).
5. **Tests:** eliminar `tests/Feature/Api/V1/` y los skips de la Oleada 1
   que correspondan a rutas ya inexistentes. **`AuthApiTest` se mantiene** (quitándole
   solo el test de push-token). Mantener `Api/Agency/*` intacto.
6. **PHPStan:** correr `composer analyse` — detectará cualquier `use`/referencia colgante
   (p.ej. un import de un controller borrado). Es el mejor detector de "borré algo que
   alguien usaba".
7. **Gate:** `composer test` + `composer analyse` (nivel 5) + `composer format`, los tres
   verdes. Verificación manual Filament + `/api/agency/*`.

> Regla de oro por archivo: antes de borrar, `grep -rn "NombreClase" app tests routes`.
> Si aparece fuera de `Api/V1` o de la ruta pública → **no borrar, investigar**.

## B.4 — Decisiones que requieren tu confirmación

> **Revisión 2026-07-04:** las tres quedaron verificadas contra el código. Solo queda
> pendiente del usuario confirmar si algún monitor externo apunta a `/api/health`.

1. **`GET /health` — RESUELTA (casi):** Railway usa **`/up`** (`railway.json:8`,
   `healthcheckPath`), no `/api/health`; `bootstrap/app.php:12` ya expone
   `health: '/up'`. → **Retirar `/api/health`**, salvo que un monitor externo (no
   visible en el repo) lo apunte — única confirmación que sigue pendiente.
2. **`config/modules.php` flag `'api'` — RESUELTA:** antes gateaba también el auth del
   panel (`MODULE_API=false` apagaba `/api/auth/login`) — por eso en la Oleada 1 se movió
   auth fuera del bloque. Tras borrar V1, el flag ya **no gatea ninguna ruta**, pero
   **NO queda sin uso**: sigue controlando la visibilidad de la página Filament
   `WebhookSettings` (`WebhookSettings.php:34`) — y controlaba `ApiSettings`, retirada en
   este PR junto con la superficie V1 que documentaba. Cuidado: con `MODULE_API=false`
   las rutas `/api/agency` y `/api/auth` siguen vivas y solo desaparece la página de
   webhooks (cuyo feature SÍ está activo). Pendiente menor: renombrar el flag a
   `'agency_api'`/`'webhooks'` o mover esa página a otro gate para que el nombre no
   engañe. No lo toca este PR.
3. **¿El panel consumía `/api/auth/login`? — RESUELTA: SÍ.** El panel obtiene su token
   Sanctum con `POST /api/auth/login` y cierra sesión con `POST /api/auth/logout`
   (`panel/lib/api.ts:182,195`); su RBAC depende del `user.role`/`permissions` de
   `login`/`me`. → **`AuthController` NO se borra** (ver B.1/B.2/B.3).

## B.5 — Oleada 3 · Consolidación (opcional, tras la poda)

Con la superficie ya reducida, aplicar las mejoras de calidad de §10 que ahora son más
baratas: paginación uniforme, envelope de error único, unificar `ClientController`, y
—si se quiere blindar el contrato— **renombrar el prefijo a `/api/agency/v1/*`**
(Oleada donde el panel Next.js debe actualizar su base URL en coordinación).

## B.6 — Checklist de "hecho sin corromper nada"

- [ ] `php artisan route:list` muestra `api/agency/*` + `api/auth/login|me|logout` (+ `/up`, + `/health` si se conserva)
- [ ] **`POST /api/auth/login` → 200 con token; `POST /api/auth/logout` → OK** (login del panel intacto)
- [ ] `composer test` verde (todos los `Api/Agency/*` y `AuthApiTest` pasan)
- [ ] `composer analyse` (PHPStan 5) sin errores nuevos → cero referencias colgantes
- [ ] `composer format` aplicado
- [ ] **Filament admin** carga y opera (Orders/Customers/Items/Payments) — modelos intactos
- [ ] **Portal de cliente** y **PDFs** (`OrderPdfController`, `CustomerPortalController`) funcionan
- [ ] `GET /api/agency/clients` responde 200 con token; 401 sin token; 403 sin permiso
- [ ] **BD sin cambios**: cero migraciones nuevas, `migrate:status` idéntico
- [ ] Un solo commit por oleada, mensaje conventional (`refactor: retire public + v1 API surfaces`), rollback trivial

---

### Resumen de una línea
No es un rediseño de dominio: es **apagar y luego borrar la capa HTTP pública + V1**
(rutas, controllers, requests, `api.pro`), **preservando `/api/auth/login|me|logout`
(el login del panel — solo se retira `push-token`), los modelos, los servicios de
PDF compartidos, Filament, el portal y la base de datos intactos**. Quedan como
superficie de la API `/api/agency/*` + `/api/auth/*`, ambos ya listos y bien testeados.
