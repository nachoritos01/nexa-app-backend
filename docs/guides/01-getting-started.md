# 01 - Quick Start Guide

Esta guide te will take desde cero hasta tener el proyecto funcionando on your local machine.

## Requirements del Sistema

| Requirement | Version Minimum | Check Installation |
|-----------|----------------|----------------------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Docker | 20+ | `docker --version` |
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |

### Installation de Requirements (macOS)

```bash
# Homebrew (si no lo tienes)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# PHP 8.4+
brew install php

# Composer
brew install composer

# Docker Desktop
brew install --cask docker

# Node.js
brew install node
```

### Check PHP de Homebrew

```bash
# La version correcta is en:
/opt/homebrew/opt/php/bin/php -v
# PHP 8.5.x

# Si php -v muestra version antigua, usa la ruta completa
# o adds a tu PATH en ~/.zshrc:
export PATH="/opt/homebrew/opt/php/bin:$PATH"
```

## Step 1: Clonar el Repositorio

```bash
# Clonar
git clone https://github.com/nachoritos01/saas-template-laravel.git

# Entrar al directorio
cd saas-template-laravel
```

## Step 2: Configurar Base de Datos

El proyecto usa PostgreSQL en Docker (contenedor compartido con otros proyectos).

```bash
# Check si PostgreSQL is running
docker ps | grep postgres

# Si no is running, start it
docker start saas-template-postgres

# Si no existe el contenedor, crearlo
docker run -d \
  --name saas-template-postgres \
  -e POSTGRES_USER=postgres \
  -e POSTGRES_PASSWORD=postgres123 \
  -e POSTGRES_DB=saas-template_laravel \
  -p 5432:5432 \
  postgres:15

# Check connection
docker exec saas-template-postgres pg_isready
# Debe responder: accepting connections
```

## Step 3: Instalar Dependencias

```bash
# Dependencias PHP
composer install

# Dependencias Node.js (para Tailwind/Vite)
npm install
```

## Step 4: Configurar Environment

```bash
# Copy configuration
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Variables de Environment Importantes

Edita `.env` y verifica estas configuraciones:

```env
# Application
APP_NAME="SaaS Template"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas-template_laravel
DB_USERNAME=postgres
DB_PASSWORD=postgres123

# Information del Negocio (optional)
BRAND_NAME="SaaS Template SaaS"
WHATSAPP_NUMBER="5212345678901"
```

## Step 5: Migrar y Sembrar Base de Datos

```bash
# Ejecutar migrations (crear tables)
php artisan migrate

# Cargar datos de test
php artisan db:seed

# O hacer ambos en un command
php artisan migrate:fresh --seed
```

### What datos se crean?

| Table | Registrations | Description |
|-------|-----------|-------------|
| item_categories | 2 | Basic, Premium |
| sizes | 5 | CH, MD, GD, EG, XX |
| colors | 10+ | Blanco, Negro, etc. |
| pricing_rules | 12 | Reglas de precios por volumen |
| dimension_limits | 10 | Limits de area de service |
| products | 5 | Items de example |
| users | 1 | Admin para Filament |

## Step 6: Iniciar Servidor de Desarrollo

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
```

### Option B: Command Directo

```bash
# Con PHP en PATH
php artisan serve

# Con PHP de Homebrew
/opt/homebrew/opt/php/bin/php artisan serve

# Puerto specific
php artisan serve --port=8001
```

### Option C: Desarrollo Completo (con Vite)

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Vite para assets
npm run dev
```

O usar el script combinado:
```bash
composer run dev
```

## Step 7: Check Funcionamiento

### En el Navegador

| URL | What you will see |
|-----|-----------|
| http://localhost:8000 | Page principal |
| http://localhost:8000/quote | Cotizador interactivo |
| http://localhost:8000/items | Catalog of items |
| http://localhost:8000/admin | Panel administrativo |

### Con curl (API)

```bash
# Health check
curl http://localhost:8000/api

# Response esperada:
{
  "name": "SaaS Template API",
  "version": "1.0.0",
  "status": "ok"
}

# Lista de items
curl http://localhost:8000/api/products

# Calcular precio
curl "http://localhost:8000/api/pricing/calculate?model=basicas&size=MD&quantity=10"
```

## Acceso al Panel Admin

| Campo | Valor |
|-------|-------|
| URL | http://localhost:8000/admin |
| Email | admin@example.com |
| Password | admin123 |

## Commands de Uso Diario

```bash
# Iniciar servidor
./scripts/serve.sh

# Detener servidor
Ctrl+C

# Ver rutas available
php artisan route:list

# Ver solo rutas API
php artisan route:list --path=api

# Consola interactiva (Tinker)
php artisan tinker

# Clear caches
php artisan optimize:clear
```

## Troubleshooting

### Error: "PHP version >= 8.4.0 required"

```bash
# Usar PHP de Homebrew directamente
/opt/homebrew/opt/php/bin/php artisan serve
```

### Error: "Connection refused" a PostgreSQL

```bash
# Check Docker
docker ps

# Iniciar contenedor
docker start saas-template-postgres

# Si no existe, crearlo (ver Step 2)
```

### Error: "Table not found"

```bash
# Ejecutar migrations
php artisan migrate

# O resetear todo
php artisan migrate:fresh --seed
```

### Error: "Class not found"

```bash
# Regenerar autoload
composer dump-autoload

# Clear cache de config
php artisan config:clear
```

### Puerto 8000 ocupado

```bash
# Usar otro puerto
php artisan serve --port=8001

# O matar proceso en 8000
lsof -ti:8000 | xargs kill -9
```

## Estructura de Files Clave

```
saas-template-laravel/
├── app/
│   ├── Http/Controllers/   # Controladores
│   ├── Models/             # Models Eloquent
│   ├── Services/           # Logic de negocio
│   ├── Livewire/           # Componentes interactivos
│   └── Filament/           # Panel admin
├── database/
│   ├── migrations/         # Estructura de BD
│   └── seeders/            # Datos iniciales
├── routes/
│   ├── web.php             # Rutas web
│   └── api.php             # Rutas API
├── resources/views/        # Vistas Blade
├── .env                    # Configuration local
└── scripts/serve.sh        # Script de inicio
```

## Siguiente Step

Continue con [02-architecture.md](./02-architecture.md) para entender la arquitectura del proyecto.
