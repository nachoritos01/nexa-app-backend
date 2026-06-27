# Quick Start Guide

How to run SaaS Template Laravel on your local machine.

## Requirements Previos

| Requirement | Version | Check |
|-----------|---------|-----------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Docker | 20+ | `docker --version` |
| Node.js | 20+ | `node -v` (optional) |

### macOS con Homebrew

```bash
# Instalar PHP (si no lo tienes)
brew install php

# Check version
/opt/homebrew/opt/php/bin/php -v
# PHP 8.5.x
```

## Step 1: Base de Datos

PostgreSQL corre en Docker (contenedor compartido con proyecto NestJS):

```bash
# Check si is running
docker ps | grep postgres

# Si no is running, start it
docker start saas-template-postgres

# Check connection
docker exec saas-template-postgres pg_isready
# /var/run/postgresql:5432 - accepting connections
```

## Step 2: Configuration Initial (first time only)

```bash
cd /Users/joseignacio/projects/saas-template-laravel

# Instalar dependencias PHP
composer install

# Copy configuration
cp .env.example .env

# Generate application key
php artisan key:generate

# Ejecutar migrations
php artisan migrate

# Cargar datos de test
php artisan db:seed
```

## Step 3: Iniciar Servidor

### Option A: Script Simple (Recommended)

```bash
./scripts/serve.sh
```

Salida esperada:
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚀 SaaS Template Laravel - Servidor de Desarrollo
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  PHP:     8.5.2
  Puerto:  8000
  URL:     http://localhost:8000
  API:     http://localhost:8000/api
```

### Option B: Command Directo

```bash
# PHP en PATH
php artisan serve

# Homebrew macOS
/opt/homebrew/opt/php/bin/php artisan serve

# Puerto specific
php artisan serve --port=8001
```

### Option C: Desarrollo Completo

```bash
# Instalar dependencias npm (first time only)
npm install

# Ejecutar todo (servidor + vite + queue + logs)
composer run dev
```

## Step 4: Check Funcionamiento

### Navegador

Abrir: http://localhost:8000/api

Response esperada:
```json
{
  "name": "SaaS Template API",
  "version": "1.0.0",
  "status": "ok",
  "timestamp": "2026-02-03T..."
}
```

### Terminal (curl)

```bash
# Health check
curl http://localhost:8000/api

# Lista de items
curl http://localhost:8000/api/products

# Calcular precio
curl "http://localhost:8000/api/pricing/calculate?model=basicas&size=MD&quantity=10"

# Niveles de precio
curl "http://localhost:8000/api/pricing/tiers?model=basicas&size=MD"
```

## Commands Useful

| Command | Description |
|---------|-------------|
| `./scripts/serve.sh` | Iniciar servidor |
| `./scripts/serve.sh 8001` | Iniciar en puerto 8001 |
| `php artisan tinker` | Consola interactiva |
| `php artisan route:list --path=api` | Ver rutas API |
| `composer analyse` | Static analysis (PHPStan) |
| `composer format` | Format code |

## Detener Servidor

Presionar `Ctrl+C` en la terminal donde corre el servidor.

## Troubleshooting

### Error: "PHP version >= 8.4.0 required"

```bash
# Usar PHP de Homebrew directamente
/opt/homebrew/opt/php/bin/php artisan serve
```

### Error: "Connection refused" a PostgreSQL

```bash
# Check que Docker is corriendo
docker ps

# Iniciar contenedor de PostgreSQL
docker start saas-template-postgres
```

### Error: "Table not found"

```bash
# Ejecutar migrations
php artisan migrate

# O resetear todo
php artisan migrate:fresh --seed
```

### Clear cache

```bash
php artisan optimize:clear
```

## URLs de Referencia

| URL | Description |
|-----|-------------|
| http://localhost:8000 | Web application |
| http://localhost:8000/quote | Cotizador interactivo |
| http://localhost:8000/items | Catalog of items |
| http://localhost:8000/carrito | Carrito de compras |
| http://localhost:8000/admin | Panel administrativo |
| http://localhost:8000/api | API REST |
| http://localhost:8000/api/products | Items |
| http://localhost:8000/api/pricing/tiers | Precios |

## Panel Administrativo (FASE 5) ✅

El panel administrativo is implementado con FilamentPHP 3.3.47.

### Acceso

| Campo | Valor |
|-------|-------|
| URL | http://localhost:8000/admin |
| Email | `admin@example.com` |
| Password | `admin123` |

### Modules Available

| Module | Description |
|--------|-------------|
| **Orders** | Full management con estados y actions de workflow |
| **Items** | CRUD con images y activation |
| **Tipos de Product** | Basic, Premium |
| **Colors** | Management with color picker |
| **Sizes** | CH, MD, GD, EG, XX |
| **Precios** | Reglas de precios por volumen |
| **Dimensions** | Service limits por talla |

### Navigation

```
Panel Admin
├── Orders (badge con pendientes)
├── Items
├── Catalogs
│   ├── Tipos de Product
│   ├── Colors
│   └── Sizes
└── Configuration
    ├── Precios
    └── Dimensions
```

---

**Siguiente paso:** Ver [API Reference](./api-reference.md) para documentation completa de endpoints.

**Backoffice:** Ver [backoffice-roadmap.md](./backoffice-roadmap.md) para el plan de implementation del panel admin.
