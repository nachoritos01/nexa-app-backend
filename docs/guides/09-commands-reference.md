# 09 - Referencia de Commands

Referencia completa de commands Artisan, Composer y otros scripts useful.

## Commands Esenciales Diarios

```bash
# Iniciar servidor de desarrollo
./scripts/serve.sh
# o
php artisan serve

# Consola interactiva (Tinker)
php artisan tinker

# Limpiar todos los caches
php artisan optimize:clear

# Ver rutas available
php artisan route:list
php artisan route:list --path=api

# Ejecutar tests
php artisan test
```

## Artisan - Desarrollo

### Servidor y Debug

```bash
# Servidor de desarrollo
php artisan serve                    # http://localhost:8000
php artisan serve --port=8080        # Puerto personalizado
php artisan serve --host=0.0.0.0     # Accesible en red local

# Information del environment
php artisan about                    # Resumen del proyecto
php artisan env                      # Variables de environment
php artisan --version                # Version de Laravel

# Tinker (REPL)
php artisan tinker
# Dentro de Tinker:
>>> Product::count()
>>> Order::latest()->first()
>>> User::create(['email' => 'test@test.com', 'password' => bcrypt('pass')])
```

### Generadores (make:)

```bash
# Models
php artisan make:model Product                    # Solo model
php artisan make:model Product -m                 # + migration
php artisan make:model Product -mf                # + migration + factory
php artisan make:model Product -mfs               # + migration + factory + seeder
php artisan make:model Product -a                 # Todo (model, migration, factory, seeder, controller, policy)

# Controladores
php artisan make:controller ProductController             # Empty
php artisan make:controller ProductController --resource  # Con methods CRUD
php artisan make:controller Api/ProductController --api   # Para API (sin create/edit)
php artisan make:controller ProductController --invokable # Single action

# Migrations
php artisan make:migration create_products_table
php artisan make:migration add_status_to_orders_table --table=orders
php artisan make:migration create_product_size_table  # Pivot

# Otros generadores
php artisan make:seeder ProductSeeder
php artisan make:factory ProductFactory --model=Product
php artisan make:request StoreProductRequest
php artisan make:resource ProductResource           # API Resource
php artisan make:policy ProductPolicy --model=Product
php artisan make:event OrderCreated
php artisan make:listener SendOrderNotification --event=OrderCreated
php artisan make:job ProcessPayment
php artisan make:mail OrderConfirmation --markdown=emails.order
php artisan make:notification OrderShipped
php artisan make:command SendDailyReport
php artisan make:rule ValidPhone
php artisan make:middleware CheckAge
php artisan make:exception InsufficientStockException
php artisan make:cast Json
php artisan make:observer ProductObserver --model=Product
```

### Livewire

```bash
# Crear componente
php artisan make:livewire QuoteCalculator
php artisan make:livewire Layout/Header              # Con subdirectorio
php artisan make:livewire Cart --inline              # Vista inline

# Listar componentes
php artisan livewire:list

# Stubs
php artisan livewire:stubs                           # Publicar stubs personalizables
```

### Filament

```bash
# Crear resource CRUD
php artisan make:filament-resource Product
php artisan make:filament-resource Product --generate  # Con campos auto-generados
php artisan make:filament-resource Product --simple    # Sin pages separadas
php artisan make:filament-resource Product --view      # Solo lectura

# Crear user admin
php artisan make:filament-user

# Otros
php artisan make:filament-page Dashboard
php artisan make:filament-widget StatsOverview
php artisan make:filament-relation-manager ProductResource sizes SizeResource
```

## Artisan - Base de Datos

### Migrations

```bash
# Ejecutar migrations pendientes
php artisan migrate

# Ver estado de migrations
php artisan migrate:status

# Revertir last migration
php artisan migrate:rollback
php artisan migrate:rollback --step=3    # Revertir lasts 3

# Revertir todas
php artisan migrate:reset

# Revertir y re-ejecutar
php artisan migrate:refresh              # refresh = reset + migrate
php artisan migrate:refresh --seed       # + ejecutar seeders

# Borrar todo y recrear (¡DESTRUCTIVO!)
php artisan migrate:fresh                # fresh = drop all + migrate
php artisan migrate:fresh --seed

# En operations (con confirmation)
php artisan migrate --force
```

### Seeders

```bash
# Ejecutar todos los seeders
php artisan db:seed

# Seeder specific
php artisan db:seed --class=ProductSeeder

# Migrar y sembrar
php artisan migrate:fresh --seed
```

### Otros Database

```bash
# Ver connection actual
php artisan db:show

# Consola SQL directa
php artisan db                          # Abre customer de BD
php artisan db:table products           # Describe table

# Monitorear consultas
php artisan db:monitor

# Wipe (borrar todo sin migrations)
php artisan db:wipe
```

## Artisan - Cache y Optimization

### Limpiar Caches

```bash
# Limpiar todo
php artisan optimize:clear

# Caches individuales
php artisan cache:clear        # Application cache
php artisan config:clear       # Config cache
php artisan route:clear        # Route cache
php artisan view:clear         # Compiled views
php artisan event:clear        # Event cache
```

### Generar Caches (Operations)

```bash
# Optimizar para operations
php artisan optimize

# Caches individuales
php artisan config:cache       # Cachear configuration
php artisan route:cache        # Cachear rutas
php artisan view:cache         # Pre-compilar vistas
php artisan event:cache        # Cachear eventos
```

### Autoload

```bash
# Regenerar autoload de Composer
composer dump-autoload
composer dump-autoload -o      # Optimizado para operations
```

## Artisan - Colas y Jobs

```bash
# Procesar jobs en cola
php artisan queue:work
php artisan queue:work --queue=high,default  # Prioridad
php artisan queue:work --tries=3             # Reintentos
php artisan queue:work --timeout=60          # Timeout

# Ejecutar un solo job y salir
php artisan queue:work --once

# Listar jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all
php artisan queue:retry 5                    # Por ID

# Limpiar jobs fallidos
php artisan queue:flush

# Ver table de jobs
php artisan queue:table                      # Crear migration
```

## Artisan - Mantenimiento

```bash
# Modo mantenimiento
php artisan down                             # Activar
php artisan down --secret="bypass-key"       # Con bypass
php artisan down --render="errors::503"      # Vista personalizada
php artisan up                               # Desactivar

# Storage link
php artisan storage:link                     # Crear symlink public/storage
```

## Artisan - Information y Debug

```bash
# Listar rutas
php artisan route:list
php artisan route:list --path=api
php artisan route:list --name=products
php artisan route:list --method=GET
php artisan route:list --compact

# Listar eventos
php artisan event:list

# Listar commands personalizados
php artisan list

# Debug de model
php artisan model:show Product
```

## Composer

### Dependencias

```bash
# Instalar todas las dependencias
composer install
composer install --no-dev               # Sin dependencias de desarrollo

# Agregar paquete
composer require laravel/sanctum
composer require barryvdh/laravel-debugbar --dev

# Quitar paquete
composer remove barryvdh/laravel-debugbar

# Actualizar dependencias
composer update
composer update laravel/framework       # Paquete specific

# Ver paquetes instalados
composer show
composer show --outdated                # Con actualizaciones available

# Check problemas
composer diagnose
composer validate
```

### Scripts del Proyecto

```bash
# Definidos en composer.json
composer dev                            # Servidor + Vite
composer test                           # Ejecutar tests
composer analyse                        # PHPStan
composer format                         # PHP CS Fixer
composer ide-helper                     # Generar helpers
```

## NPM

```bash
# Instalar dependencias
npm install
npm ci                                  # Clean install (CI/CD)

# Desarrollo
npm run dev                             # Vite dev server

# Operations
npm run build                           # Build para operations

# Otros
npm outdated                            # Ver actualizaciones
npm update                              # Actualizar
npm audit                               # Check vulnerabilidades
npm audit fix                           # Arreglar vulnerabilidades
```

## Docker

### Contenedor de PostgreSQL

```bash
# Crear contenedor
docker run -d \
  --name saas-template-postgres \
  -e POSTGRES_USER=postgres \
  -e POSTGRES_PASSWORD=postgres123 \
  -e POSTGRES_DB=saas-template_laravel \
  -p 5432:5432 \
  postgres:15

# Gestionar contenedor
docker start saas-template-postgres
docker stop saas-template-postgres
docker restart saas-template-postgres

# Ver logs
docker logs saas-template-postgres
docker logs -f saas-template-postgres   # Follow

# Ejecutar commands
docker exec -it saas-template-postgres psql -U postgres
docker exec saas-template-postgres pg_isready

# Crear base de datos de tests
docker exec saas-template-postgres psql -U postgres -c "CREATE DATABASE saas-template_test;"
```

### Commands Docker Generales

```bash
# Ver contenedores
docker ps                               # Activos
docker ps -a                            # Todos

# Ver images
docker images

# Limpiar
docker system prune                     # Limpiar recursos no usados
docker volume prune                     # Limpiar volumes
```

## Git

### Commands Basics

```bash
# Estado y diferencias
git status
git diff
git diff --staged

# Branches
git branch                              # Listar
git branch feature/nueva-funcionalidad # Crear
git checkout feature/nueva-funcionalidad
git checkout -b feature/otra           # Crear y cambiar
git branch -d feature/antigua          # Eliminar

# Commits
git add .
git add file.php
git commit -m "feat: description"
git commit --amend                      # Modificar last commit

# Push/Pull
git push origin main
git push -u origin feature/nueva       # Configurar upstream
git pull origin main
git fetch origin

# Merge
git merge main                          # Traer cambios de main
git merge --abort                       # Cancelar merge

# Stash
git stash                               # Guardar cambios temporalmente
git stash pop                           # Recuperar cambios
git stash list                          # Ver stashes

# Logs
git log --oneline
git log --graph --oneline --all
git log -p file.php                  # Historial de file
```

### Convention de Commits

```bash
# Formato: tipo(scope): description
git commit -m "feat: add product catalog"
git commit -m "fix: resolve pricing calculation bug"
git commit -m "docs: update API documentation"
git commit -m "refactor: simplify order processing"
git commit -m "test: add unit tests for PricingCalculator"
git commit -m "chore: update dependencies"
git commit -m "style: format code with prettier"
```

## Scripts del Proyecto

### Definidos en composer.json

```json
{
    "scripts": {
        "dev": "./scripts/serve.sh",
        "test": "php artisan test",
        "analyse": "phpstan analyse",
        "format": "php-cs-fixer fix",
        "ide-helper": "php artisan ide-helper:generate && php artisan ide-helper:models -N"
    }
}
```

### Script serve.sh

```bash
#!/bin/bash
# scripts/serve.sh

PHP_BIN="/opt/homebrew/opt/php/bin/php"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 SaaS Template Laravel - Servidor de Desarrollo"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  PHP:     $($PHP_BIN -v | head -n 1)"
echo "  Puerto:  8000"
echo "  URL:     http://localhost:8000"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

$PHP_BIN artisan serve
```

## Commands Useful Combinados

```bash
# Reset completo de desarrollo
php artisan migrate:fresh --seed && php artisan optimize:clear

# Check todo antes de commit
composer format && composer analyse && php artisan test

# Crear feature completa
php artisan make:model Product -mfs && \
php artisan make:controller Api/ProductController --api && \
php artisan make:request StoreProductRequest && \
php artisan make:resource ProductResource

# Ver configuration actual
php artisan config:show database
php artisan config:show app

# Debug de query
php artisan tinker
>>> DB::enableQueryLog();
>>> Product::with('productModel')->get();
>>> DB::getQueryLog();

# Generar key si falta
php artisan key:generate

# Check installation
php artisan about
composer validate
npm audit
```

## Alias Recommendeds

Agregar a `~/.zshrc` o `~/.bashrc`:

```bash
# Laravel
alias pa="php artisan"
alias pat="php artisan test"
alias pam="php artisan migrate"
alias pamf="php artisan migrate:fresh --seed"
alias pac="php artisan optimize:clear"
alias pat="php artisan tinker"

# Composer
alias ci="composer install"
alias cu="composer update"
alias cr="composer require"
alias cda="composer dump-autoload"

# Docker
alias dps="docker ps"
alias dpsa="docker ps -a"

# Git
alias gs="git status"
alias ga="git add"
alias gc="git commit -m"
alias gp="git push"
alias gl="git log --oneline -10"
```

## Solution de Problemas

```bash
# PHP version incorrecta
/opt/homebrew/opt/php/bin/php artisan serve

# Permisos de storage
chmod -R 775 storage bootstrap/cache
chown -R $USER:staff storage bootstrap/cache

# Puerto ocupado
lsof -ti:8000 | xargs kill -9

# Composer memory limit
COMPOSER_MEMORY_LIMIT=-1 composer update

# Regenerar todo
composer dump-autoload && php artisan optimize:clear

# Ver errores de Laravel
tail -f storage/logs/laravel.log
```
