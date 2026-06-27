# Deploy en Railway - SaaS Template Laravel

Guia completa para desplegar la aplicacion en Railway con PostgreSQL.

> **Ultima actualizacion:** 2026-02-09
> **Status:** Deployed y funcionando
> **URL:** https://your-app.example.com

---

## Arquitectura del Deploy

```
┌──────────────────────────────────────────────────────────┐
│                  Railway (Production)                      │
│                                                          │
│  ┌────────────────────────┐  ┌────────────────────────┐ │
│  │  saas-template-laravel │  │       Postgres          │ │
│  │                        │  │                         │ │
│  │  Dockerfile            │  │  postgresql://postgres   │ │
│  │  (php:8.4-cli-alpine)  │──│  @postgres.railway.     │ │
│  │                        │  │  internal:5432/railway   │ │
│  │  Puerto: $PORT (8080)  │  │                         │ │
│  │                        │  │  postgres-volume         │ │
│  └────────────────────────┘  └────────────────────────┘ │
│          │                                               │
│  URL publica:                                            │
│  your-app.up.railway.app         │
│                                                          │
│  Conexion entre services:                               │
│  DATABASE_URL = ${{Postgres.DATABASE_URL}}               │
└──────────────────────────────────────────────────────────┘
```

## Files de Configuracion

| File | Funcion |
|---------|---------|
| `Dockerfile` | Build multi-stage: node (assets) + php (runtime) |
| `railway.json` | Config de Railway: builder, healthcheck, restart |
| `scripts/docker-entrypoint.sh` | Entrypoint: clear cache, migrate, cache, serve |
| `.dockerignore` | Excluir files innecesarios del build |
| `config/database.php` | Parsea `DATABASE_URL` de Railway |
| `config/app.php` | `admin_emails` para control de acceso Filament |
| `bootstrap/app.php` | Trusted proxies para HTTPS |

### Dockerfile (Multi-stage)

```
Stage 1: node:20-alpine (frontend-build)
  - npm ci
  - npm run build (Vite + Tailwind CSS)

Stage 2: php:8.4-cli-alpine (runtime)
  - Extensiones: pdo_pgsql, gd, intl, zip, bcmath, pcntl
  - composer install --no-dev
  - COPY assets compilados de Stage 1
  - ENTRYPOINT: docker-entrypoint.sh
```

Imagen resultante: ~301MB, build time: ~2.5 min

### docker-entrypoint.sh

El entrypoint ejecuta en cada deploy:

1. `php artisan config:clear` - Limpia cache viejo para que `env()` funcione
2. `php artisan migrate --force` - Migrations pendientes
3. `php artisan storage:link` - Symlink de storage (si no existe)
4. `php artisan config:cache` - Cachea configuracion con env vars actuales
5. `php artisan route:cache` - Cachea rutas
6. `php artisan view:cache` - Cachea vistas Blade
7. `php artisan serve --host=0.0.0.0 --port=$PORT` - Inicia servidor

**IMPORTANTE:** El `config:clear` al inicio es critico. Sin el, un deploy previo deja
la config cacheada y `env()` retorna `null` en migrations y seeders.

### Trusted Proxies (HTTPS)

Railway usa un proxy reverso. Sin trusted proxies, Laravel genera URLs con `http://`
en vez de `https://`, causando que los assets CSS/JS no carguen (mixed content bloqueado
por el navegador).

Configuracion en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
})
```

### Conexion a Base de Datos

En `config/database.php`, la conexion pgsql usa `DATABASE_URL`:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL', env('DATABASE_URL')),
    // ...
],
```

Railway inyecta `DATABASE_URL` automaticamente al vincular el plugin PostgreSQL.
Requiere `DB_CONNECTION=pgsql` como variable de environment (el default es `sqlite`).

### Control de Acceso al Admin (FilamentPHP)

El model `User` implementa `FilamentUser` con control basado en variable de environment:

```php
// app/Models/User.php
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        $admins = explode(',', (string) config('app.admin_emails'));
        return in_array($this->email, $admins);
    }
}
```

```php
// config/app.php
'admin_emails' => env('ADMIN_EMAILS', ''),
```

**IMPORTANTE sobre `env()` vs `config()`:** Cuando `config:cache` esta activo (operations),
`env()` retorna `null` fuera de files `config/*.php`. Siempre usar:
- En `config/*.php` → `env()`
- En models, controllers, seeders → `config()`

---

## Prerequisitos

- Cuenta en [Railway](https://railway.app)
- [Railway CLI](https://docs.railway.app/guides/cli) instalado: `npm install -g @railway/cli`
- Login: `railway login`

---

## Step 1: Crear Proyecto en Railway

### Desde el Dashboard

1. Ir a [railway.app/new](https://railway.app/new)
2. Crear nuevo proyecto
3. Agregar service desde GitHub repo o "Empty Service"

### Desde CLI

```bash
railway init
# Name: saas-template-laravel
```

## Step 2: Agregar PostgreSQL

### Desde el Dashboard

1. Abrir el proyecto
2. Click "+ New" > "Database" > "PostgreSQL"
3. Railway crea el service y genera `DATABASE_URL` automaticamente

### Desde CLI

```bash
railway add --plugin postgresql
```

**IMPORTANTE:** Despues de agregar PostgreSQL, configurar la variable de referencia
en el service de la app (no en Postgres):

```
DATABASE_URL = ${{Postgres.DATABASE_URL}}
```

## Step 3: Vincular Proyecto Local

```bash
cd /Users/joseignacio/projects/saas-template-laravel

# Vincular al proyecto existente
railway link
# Seleccionar proyecto: saas-template-laravel
# Seleccionar environment: production
# Seleccionar service: saas-template-laravel (NO Postgres)

# Check vinculacion
railway status
```

**IMPORTANTE:** Siempre check que estes vinculado al service correcto.
Si vinculas a Postgres por error, `railway up` desplegaria tu app Laravel
como si fuera una base de datos, causando healthcheck failure.

## Step 4: Configurar Variables de Environment

### Variables Requeridas

```bash
# Generar APP_KEY
/opt/homebrew/bin/php artisan key:generate --show
# Resultado: base64:xxxxxxxxxxx...

# Setear variables (usar comillas simples para valores con caracteres especiales)
railway variables set 'APP_KEY=base64:TU_KEY_AQUI'
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_URL=https://your-app.example.com
railway variables set DB_CONNECTION=pgsql
railway variables set APP_LOCALE=es
railway variables set APP_TIMEZONE=UTC
railway variables set SESSION_DRIVER=database
railway variables set CACHE_STORE=database
railway variables set QUEUE_CONNECTION=database
railway variables set APP_NAME=SaaS Template
```

### Variables para Admin y User

```bash
# Emails con acceso al panel admin (separados por coma)
railway variables set ADMIN_EMAILS=admin@example.com

# Credenciales del user admin inicial (usadas por migracion)
railway variables set ADMIN_EMAIL=admin@example.com
railway variables set ADMIN_PASSWORD=TU_PASSWORD_SEGURO
```

**Note:** `ADMIN_EMAIL` y `ADMIN_PASSWORD` son usadas por la migracion
`create_admin_user` para insertar el primer user. Se ejecuta una sola vez.
Despues del primer deploy exitoso puedes eliminar `ADMIN_PASSWORD` de Railway.

### Variables Automaticas de Railway

Railway inyecta estas variables sin configuracion manual:

| Variable | Example |
|----------|---------|
| `DATABASE_URL` | `postgresql://postgres:PASS@postgres.railway.internal:5432/railway` |
| `PORT` | `8080` |
| `RAILWAY_ENVIRONMENT` | `production` |
| `RAILWAY_PUBLIC_DOMAIN` | `your-app.up.railway.app` |

### Check Variables

```bash
railway variables
```

## Step 5: Deploy

### Opcion A: Deploy desde CLI

```bash
railway up
```

### Opcion B: Deploy automatico via GitHub

1. En Railway dashboard: Settings > Source > Connect GitHub repo
2. Seleccionar branch: `main` (o `develop`)
3. Cada push a ese branch dispara un deploy automatico

## Step 6: Crear User Admin

El user admin se crea automaticamente via migracion (`2026_02_09_195058_create_admin_user`)
cuando las variables `ADMIN_EMAIL` y `ADMIN_PASSWORD` estan configuradas en Railway.

La migracion:
- Usa `DB::table('users')->insert()` directo (sin pasar por el model)
- Hashea con `Hash::make()` una sola vez (sin doble hash)
- Solo se ejecuta una vez (las migrations no se repiten)
- Lee las credenciales de `env()` (funciona porque `config:clear` corre antes)

**Alternativa manual** (si la migracion no se ejecuto o el password quedo mal):

Conectar a la DB desde DBeaver (ver seccion "Conectar DB desde Local") y ejecutar SQL:

```bash
# 1. Generar hash bcrypt localmente
/opt/homebrew/bin/php -r "echo password_hash('TU_PASSWORD', PASSWORD_BCRYPT) . PHP_EOL;"
# Resultado: $2y$12$xxxx...
```

```sql
-- 2. Insertar user (si no existe)
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@example.com', '$2y$12$HASH_AQUI', NOW(), NOW());

-- O actualizar password de user existente
UPDATE users SET password = '$2y$12$HASH_AQUI' WHERE email = 'admin@example.com';
```

**IMPORTANTE sobre passwords:**
- El password en la DB DEBE ser un hash bcrypt (empieza con `$2y$12$`)
- NO guardar texto plano - causa error "This password does not use the Bcrypt algorithm"
- NO usar `bcrypt()` con el model User (tiene cast `hashed` que hashea automaticamente = doble hash)
- Usar `Hash::make()` o `password_hash()` de PHP una sola vez

**Note:** `railway exec` no existe en todas las versiones del CLI. Usar DBeaver
para operaciones directas en la DB.

## Step 7: Seed de Datos Initiales

Los seeders se ejecutan automaticamente en cada deploy via el entrypoint (`db:seed --force`).
Todos los seeders son idempotentes (usan `firstOrCreate` o `updateOrCreate`),
asi que no duplican datos al re-ejecutarse.

| Seeder | Datos | Metodo |
|--------|-------|--------|
| ItemCategorySeeder | Basicas, Premium | `updateOrCreate` (idempotente) |
| SizeSeeder | CH, MD, GD, EG, XX | `updateOrCreate` (idempotente) |
| ColorSeeder | Blanco, Negro, etc | `updateOrCreate` (idempotente) |
| PricingRuleSeeder | 12 reglas de precio | `firstOrCreate` (idempotente) |
| removedSeeder | 10 limites | `firstOrCreate` (idempotente) |
| ProductSeeder | 5 products | `firstOrCreate` (idempotente) |
| NormalizationSeeder | Relationships N:M | Verificacion de existencia |

Los datos persisten en PostgreSQL entre redeploys.

## Step 8: Check Deploy

```bash
# Health check (debe retornar HTML con "Application up")
curl https://your-app.example.com/up

# Pagina principal (debe retornar 200)
curl -sI https://your-app.example.com/

# Admin panel (debe retornar 200 con login form)
curl -sI https://your-app.example.com/admin/login

# Ver logs
railway logs --tail 50
```

### URLs de la Aplicacion

| URL | Descripcion |
|-----|-------------|
| `/` | Pagina principal (items, calculadora) |
| `/quote` | Calculadora de cotizacion |
| `/items` | Catalogo de items |
| `/item/{id}` | Detalle de item |
| `/carrito` | Carrito de compras |
| `/contact` | Pagina de contacto |
| `/faq` | Preguntas frecuentes |
| `/policies` | Politicas |
| `/admin/login` | Login del panel admin |
| `/admin` | Dashboard FilamentPHP |
| `/up` | Health check |

---

## Conectar DB desde Local (DBeaver)

La URL interna (`postgres.railway.internal`) solo funciona dentro de Railway.
Para conectar desde tu equipo necesitas la URL **publica**.

### Obtener Credenciales Publicas

```bash
# Vincular al service Postgres temporalmente
railway link
# Seleccionar service: Postgres

# Ver variables
railway variables
# Buscar: PGHOST, RAILWAY_TCP_PROXY_PORT, PGUSER, PGPASSWORD
# O buscar: DATABASE_PUBLIC_URL

# IMPORTANTE: Re-vincular al service de la app despues
railway link
# Seleccionar service: saas-template-laravel
```

### Configurar DBeaver

| Campo | Valor |
|-------|-------|
| Host | Host publico (ej: `viaduct.proxy.rlwy.net`) |
| Port | Puerto publico (ej: `12345`, NO 5432) |
| Database | `railway` |
| Username | `postgres` |
| Password | El password de las variables |

### Conectar via CLI (psql)

```bash
# Vincular a Postgres y conectar directamente
railway link  # seleccionar Postgres
railway connect
# Abre shell psql
```

---

## Commands Utiles

### Railway CLI

```bash
# Estado del service
railway status

# Variables de environment
railway variables

# Setear variable
railway variables set NOMBRE=valor

# Logs en tiempo real
railway logs --tail 100

# Deploy manual
railway up

# Ejecutar command en el contenedor
railway exec <command>

# Ejecutar command local con env vars de Railway
railway run <command>

# Conectar a DB via psql
railway connect  # (vinculado a Postgres)

# Vincular a otro service
railway link
```

### Diferencia entre `railway run`, `railway exec` y `railway connect`

| Command | Donde Ejecuta | Acceso a DB Interna | PHP |
|---------|---------------|---------------------|-----|
| `railway run` | Tu maquina local | NO | Local (8.5) |
| `railway exec` | Contenedor en Railway | SI | Contenedor (8.4) |
| `railway connect` | Shell psql en Railway | SI (directo) | N/A |

- Usar `railway exec` para commands artisan que necesitan DB
- Usar `railway run` para commands que no necesitan DB
- Usar `railway connect` para SQL directo (requiere vincular a Postgres)

---

## Lecciones Aprendidas

### 1. `config:cache` y `env()`

Cuando `config:cache` esta activo, `env()` retorna `null` fuera de `config/*.php`.
Solucion: siempre pasar por `config()` y definir la variable en un file de config.

```php
// MAL - retorna null en operations
env('ADMIN_EMAILS')

// BIEN - funciona siempre
config('app.admin_emails')
```

### 2. Doble hash de passwords

El cast `'password' => 'hashed'` en el model User hashea automaticamente.
Si ademas usas `bcrypt()` al crear el user, el password queda hasheado dos veces
y nunca coincide al hacer login.

```php
// MAL - doble hash con cast 'hashed'
User::create(['password' => bcrypt('secret')]);

// BIEN - el cast hashea automaticamente
User::create(['password' => 'secret']);

// BIEN - bypass del model, hash manual una vez
DB::table('users')->insert(['password' => Hash::make('secret')]);
```

### 3. Vincular al service correcto

`railway link` puede vincular a cualquier service del proyecto (app o Postgres).
Si se vincula a Postgres, `railway up` despliega tu app como si fuera la DB,
causando healthcheck failure.

Siempre check con `railway status` antes de `railway up`.

### 4. Trusted Proxies required

Railway usa proxy reverso. Sin `trustProxies(at: '*')`, los assets de Vite
se generan con `http://` causando mixed content bloqueado por el navegador.
La pagina carga pero sin estilos CSS ni JavaScript.

### 5. Red interna vs publica

`postgres.railway.internal` solo funciona dentro de Railway. Desde tu maquina
local usa la URL publica (puerto diferente a 5432) o `railway connect`.

### 6. `railway exec` no existe en todas las versiones

El CLI de Railway no siempre tiene `railway exec`. Para ejecutar commands
en la DB de operations, usar DBeaver con las credenciales publicas,
o agregar el command al entrypoint temporalmente.

### 7. ADMIN_EMAILS vs ADMIN_EMAIL

Son variables **diferentes** con propositos distintos:
- `ADMIN_EMAIL` (sin S) → Usada por la migracion `create_admin_user` para crear el user
- `ADMIN_EMAILS` (con S) → Usada por `canAccessPanel()` para controlar acceso al panel admin

Ambas son necesarias. Si falta `ADMIN_EMAILS`, el user puede existir pero no puede
entrar al panel (403 Forbidden).

### 8. Password en DBeaver debe ser hash bcrypt

Al actualizar passwords directamente en la DB, SIEMPRE usar un hash bcrypt
(empieza con `$2y$12$`). Un password en texto plano causa:
"This password does not use the Bcrypt algorithm"

```bash
# Generar hash correcto
/opt/homebrew/bin/php -r "echo password_hash('tu_password', PASSWORD_BCRYPT) . PHP_EOL;"
```

---

## Troubleshooting

### Error: MissingAppKeyException

**Causa:** `APP_KEY` no configurada en las variables de Railway.

```bash
# Check
railway variables | grep APP_KEY

# Si falta, generar y setear
/opt/homebrew/bin/php artisan key:generate --show
railway variables set 'APP_KEY=base64:TU_KEY'

# Redesplegar
railway up
```

### Error: Assets sin estilos (Mixed Content)

**Causa:** Falta trusted proxies. Laravel genera URLs `http://` en vez de `https://`.

**Solucion:** Configurar en `bootstrap/app.php`:

```php
$middleware->trustProxies(at: '*');
```

### Error: 403 Forbidden en /admin

**Causa:** El model User no implementa `FilamentUser` o `ADMIN_EMAILS` no esta configurada.

**Solucion:**
1. Check que `User` implementa `FilamentUser` con `canAccessPanel()`
2. Check variable: `railway variables | grep ADMIN_EMAILS`
3. Check que usa `config('app.admin_emails')`, no `env()` directo

### Error: Credenciales no coinciden en /admin/login

**Causa:** Password hasheado dos veces (bcrypt + cast hashed).

**Solucion:** Recrear user sin doble hash:

```bash
railway exec php artisan tinker --execute="DB::table('users')->where('email','admin@example.com')->update(['password'=>Hash::make('TU_PASSWORD')])"
```

### Error: Healthcheck Failure

**Causas posibles:**
1. La app no arranca (ver logs: `railway logs`)
2. El endpoint `/up` no responde dentro del timeout (30s)
3. Deploy fue al service equivocado (check `railway status`)
4. `APP_KEY` faltante (la app no boota)

### Error: SQLSTATE connection refused

**Causa:** Intentar conectar a `postgres.railway.internal` desde fuera de Railway.

**Solucion:** Usar `railway exec` en vez de `railway run` para commands que necesitan DB.

### Error: PHP version mismatch con `railway run`

**Causa:** `railway run` usa el PHP del sistema (puede ser 8.3) en vez del de Homebrew (8.5).

**Solucion:**

```bash
# Especificar ruta completa
railway run /opt/homebrew/bin/php artisan <command>

# O mejor, usar railway exec (usa PHP 8.4 del contenedor)
railway exec php artisan <command>
```

### Build falla en Railway

```bash
# Check build local primero
docker build -t saas-template-laravel .

# Si falla por extensiones PHP: revisar Dockerfile Stage 2
# Si falla por npm: revisar package.json y vite.config.js
```

### Migrations fallan en deploy

```bash
# Ver logs del deploy
railway logs

# Causas comunes:
# 1. DATABASE_URL no disponible -> agregar plugin PostgreSQL y referencia
# 2. DB_CONNECTION no es pgsql -> check variables
# 3. Migrations conflictivas -> fix local y redesplegar
```

### Seeder crea datos duplicados

Los seeders `ProductSeeder`, `PricingRuleSeeder` y `removedSeeder`
usan `::create()` y NO son idempotentes. Solo ejecutar una vez:

```bash
# SOLO ejecutar una vez despues del primer deploy
railway exec php artisan db:seed --force
```

Si ya hay duplicados, resetear desde psql o DBeaver.

---

## Checklist de Deploy

### Configuracion (una vez)
- [x] Dockerfile multi-stage en la raiz
- [x] railway.json configurado (builder, healthcheck `/up`)
- [x] docker-entrypoint.sh con config:clear, migrate, cache, serve
- [x] .dockerignore (excluye .env, node_modules, vendor, tests, docs)
- [x] Trusted proxies en `bootstrap/app.php`
- [x] `FilamentUser` implementado en model User
- [x] `admin_emails` en `config/app.php`
- [x] Migracion `create_admin_user` con env vars

### Railway (una vez)
- [x] PostgreSQL agregado como service
- [x] `DATABASE_URL = ${{Postgres.DATABASE_URL}}` como referencia
- [x] `APP_KEY` generada y configurada
- [x] `DB_CONNECTION=pgsql`
- [x] `APP_ENV=production`
- [x] `APP_URL` con HTTPS
- [x] `ADMIN_EMAILS` configurada
- [x] `ADMIN_EMAIL` y `ADMIN_PASSWORD` para migracion

### Post-deploy (una vez)
- [x] Deploy exitoso con healthcheck verde
- [x] `railway exec php artisan db:seed --force` ejecutado
- [x] User admin creado (via migracion o manual)
- [x] Login en `/admin` funciona
- [x] Assets CSS/JS cargando (HTTPS, sin mixed content)
- [ ] `APP_DEBUG=false` (desactivar despues de check)
- [ ] Eliminar `ADMIN_PASSWORD` de Railway (ya no se necesita)

---

*Ultima actualizacion: 2026-02-09*
