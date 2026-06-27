# Conexion a PostgreSQL de Railway con DBeaver

Guia para conectar DBeaver a la base de datos PostgreSQL del proyecto en Railway.

> **Requirements:** DBeaver Community (gratuito) + Railway CLI instalado

---

## Arquitectura de Conexion

```
┌──────────────┐         ┌─────────────────────────────────────────┐
│  Tu equipo   │         │          Railway (Production)            │
│              │         │                                         │
│  DBeaver     │─────────│──▶ viaduct.proxy.rlwy.net:XXXXX        │
│  (customer)   │  TCP    │     (proxy publico)                     │
│              │         │         │                                │
└──────────────┘         │         ▼                                │
                         │  postgres.railway.internal:5432          │
                         │     (PostgreSQL interno)                 │
                         │                                         │
                         │  La app Laravel usa la URL interna.     │
                         │  DBeaver usa la URL publica (proxy).    │
                         └─────────────────────────────────────────┘
```

**Important:** La URL interna (`postgres.railway.internal:5432`) **no es accesible** desde fuera de Railway. DBeaver debe conectarse por el proxy publico que Railway expone en un puerto aleatorio.

---

## Step 1: Obtener Credenciales

### Opcion A: Desde Railway Dashboard (recommended)

1. Ir a [railway.app](https://railway.app) > tu proyecto
2. Click en el service **Postgres**
3. Tab **Variables**
4. Copiar estos valores:

| Variable | Para que |
|---|---|
| `PGHOST` | Host publico (ej: `viaduct.proxy.rlwy.net`) |
| `RAILWAY_TCP_PROXY_PORT` | Puerto publico (ej: `24816`) |
| `PGUSER` | User (normalmente `postgres`) |
| `PGPASSWORD` | Password |
| `PGDATABASE` | Name de BD (normalmente `railway`) |

O copiar directamente `DATABASE_PUBLIC_URL` que tiene todo junto:

```
postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:24816/railway
```

### Opcion B: Desde Railway CLI

```bash
# 1. Vincular al service Postgres
railway link
# Seleccionar: Postgres (NO saas-template-laravel)

# 2. Ver variables
railway variables

# 3. Buscar en la salida:
# PGHOST=viaduct.proxy.rlwy.net
# RAILWAY_TCP_PROXY_PORT=24816
# PGUSER=postgres
# PGPASSWORD=xxxxxxxx
# PGDATABASE=railway

# 4. IMPORTANTE: Re-vincular al service de la app
railway link
# Seleccionar: saas-template-laravel
```

> Si no re-vinculas, el proximo `railway up` desplegaria sobre el service de Postgres en vez de la app.

---

## Step 2: Configurar DBeaver

### 2.1 Crear nueva conexion

1. Menu: **Database** > **New Database Connection** (o el icono de enchufe con `+`)
2. Seleccionar **PostgreSQL**
3. Click **Next**

### 2.2 Llenar campos

| Campo | Valor | Notas |
|---|---|---|
| **Host** | `viaduct.proxy.rlwy.net` | El host publico de Railway |
| **Port** | `24816` | El puerto publico, **NO** `5432` |
| **Database** | `railway` | Name por defecto de Railway |
| **Username** | `postgres` | User por defecto |
| **Password** | (el de PGPASSWORD) | Marcar "Save password locally" |

```
┌─────────────────────────────────────────────────┐
│  Connection Settings                            │
│                                                 │
│  Host:     [viaduct.proxy.rlwy.net]            │
│  Port:     [24816        ]                      │
│  Database: [railway      ]                      │
│                                                 │
│  Authentication                                 │
│  Username: [postgres     ]                      │
│  Password: [************ ] ☑ Save password      │
│                                                 │
│  [Test Connection]  [Cancel]  [Finish]          │
└─────────────────────────────────────────────────┘
```

### 2.3 Test Connection

1. Click **Test Connection**
2. Si pide instalar driver PostgreSQL, aceptar (DBeaver lo descarga automaticamente)
3. Debe mostrar: **Connected** con version de PostgreSQL
4. Click **Finish**

---

## Step 3: Explorar la Base de Datos

### Tables del proyecto

Una vez conectado, navegar a: **railway** > **Schemas** > **public** > **Tables**

| Table | Descripcion |
|---|---|
| `users` | Users y admin |
| `items` | Products (basicas, premium) |
| `sizes` | Sizes (CH, MD, GD, EG, XX) |
| `colors` | Available colors |
| `product_sizes` | Relacion item-talla (pivot) |
| `orders` | Orders |
| `order_lines` | Items de cada order |
| `quotes` | Quotes |
| `pricing_rules` | Reglas de precios por volumen |
| removed | Limites de dimension por talla |
| `migrations` | Control de migrations de Laravel |
| `sessions` | Sesiones activas |
| `cache` | Cache de la app |
| `jobs` / `failed_jobs` | Cola de trabajos |

### Ver datos de una table

1. Doble click en la table
2. Tab **Data** para ver registrations
3. Tab **Properties** para ver columns y tipos
4. Tab **ER Diagram** para ver relationships

---

## Operaciones Comunes

### Consultar items

```sql
SELECT id, title, slug, technique, model, is_active
FROM products
ORDER BY id;
```

### Consultar orders con items

```sql
SELECT o.id, o.customer_name, o.status, o.total,
       oi.quantity, p.title AS product, s.name AS size, c.name AS color
FROM orders o
JOIN order_lines oi ON oi.order_id = o.id
JOIN products p ON p.id = oi.product_id
JOIN sizes s ON s.id = oi.size_id
JOIN colors c ON c.id = oi.color_id
ORDER BY o.created_at DESC;
```

### Consultar precios por model

```sql
SELECT model, size_type, min_qty, max_qty, price, label
FROM pricing_rules
ORDER BY model, size_type, min_qty;
```

### Check user admin

```sql
SELECT id, name, email, created_at
FROM users
WHERE email = 'admin@example.com';
```

---

## Actualizar Password de Admin

Si necesitas cambiar el password del admin directamente en la BD:

### 1. Generar hash bcrypt localmente

```bash
/opt/homebrew/bin/php -r "echo password_hash('tu_nuevo_password', PASSWORD_BCRYPT) . PHP_EOL;"
```

Example de salida:
```
$2y$12$b7tDs5c7FetOTOQgzfVD0OeR/AN1jkHODU7LL/YaV5.pwbFAZwpE2
```

### 2. Ejecutar UPDATE en DBeaver

```sql
UPDATE users
SET password = '$2y$12$b7tDs5c7FetOTOQgzfVD0OeR/AN1jkHODU7LL/YaV5.pwbFAZwpE2'
WHERE email = 'admin@example.com';
```

> **NUNCA** poner el password en texto plano. Laravel espera un hash bcrypt que empieza con `$2y$`. Un password en texto plano causa el error: *"This password does not use the Bcrypt algorithm"*.

---

## Troubleshooting

### "Connection refused" o timeout

**Causa:** Estas usando el host/puerto interno en vez del publico.

| Incorrecto | Correcto |
|---|---|
| `postgres.railway.internal` | `viaduct.proxy.rlwy.net` |
| Puerto `5432` | Puerto publico (ej: `24816`) |

Check que estas usando `DATABASE_PUBLIC_URL`, no `DATABASE_URL`.

### "Password authentication failed"

**Causa:** Password incorrecto o copiado con espacios extra.

1. Ir al dashboard de Railway > Postgres > Variables
2. Copiar `PGPASSWORD` de nuevo (sin espacios)
3. En DBeaver: click derecho en la conexion > **Edit Connection** > re-ingresar password

### "Driver not found"

DBeaver necesita el driver JDBC de PostgreSQL.

1. Al crear la conexion, DBeaver ofrece descargarlo automaticamente
2. Si no: **Database** > **Driver Manager** > **PostgreSQL** > **Download**

### La conexion se cae despues de un rato

Railway puede reciclar el proxy TCP si no hay actividad.

1. En DBeaver: click derecho en conexion > **Edit Connection**
2. Tab **Connection Details** > **Keep-Alive**
3. Activar **Keep-Alive** con intervalo de 60 segundos

### No veo las tables

1. Check que navegas a: **railway** > **Schemas** > **public** > **Tables**
2. Si el schema `public` esta vacio, los seeders no corrieron. Revisar logs del ultimo deploy en Railway.

---

## Tips

1. **Guardar la conexion con name descriptivo** — Ej: `SaaS Template Railway (Production)` para identificarla facilmente.
2. **No dejar DBeaver conectado permanentemente** — El proxy publico de Railway tiene limites de conexiones.
3. **Usar transactions para cambios manuales** — En DBeaver: **Database** > **Transaction** > **Manual Commit**. Asi puedes revertir si algo sale mal.
4. **Exportar datos** — Click derecho en table > **Export Data** para respaldos en CSV, SQL o JSON.
5. **ER Diagram completo** — Click derecho en schema `public` > **View Diagram** para ver todas las relationships.

---

*Creado: 2026-02-09*
