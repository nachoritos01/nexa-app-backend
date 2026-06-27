# 📚 Guides de Desarrollo - SaaS Template Laravel

Bienvenido al centro de documentation para desarrolladores. Estas guides te will help a entender, contribuir y extender el proyecto.

## 🗂️ Index de Guides

| Guide | Description | Nivel |
|------|-------------|-------|
| [01-getting-started.md](./01-getting-started.md) | Configuration inicial y primeros pasos | Principiante |
| [02-architecture.md](./02-architecture.md) | Estructura del proyecto y patrones de design | Intermedio |
| [03-models-database.md](./03-models-database.md) | Models Eloquent, relationships y migrations | Intermedio |
| [04-api-development.md](./04-api-development.md) | Desarrollo de API REST, controladores y rutas | Intermedio |
| [05-livewire-components.md](./05-livewire-components.md) | Componentes interactivos con Livewire | Intermedio |
| [06-services-logic.md](./06-services-logic.md) | Capa de services y logic de negocio | Avanzado |
| [07-testing.md](./07-testing.md) | Tests unitarias y de integration | Intermedio |
| [08-best-practices.md](./08-best-practices.md) | Convenciones, aredares y buenas practices | Todos |
| [09-commands-reference.md](./09-commands-reference.md) | Referencia de commands Artisan y scripts | Todos |
| [10-filament-admin.md](./10-filament-admin.md) | Panel admin con FilamentPHP: installation, recursos, customization | Intermedio |
| [11-git-troubleshooting.md](./11-git-troubleshooting.md) | Resolution de errores de ramas y commits en Git | Todos |
| [12-railway-deployment.md](./12-railway-deployment.md) | Deploy en Railway: Docker, PostgreSQL, variables, troubleshooting | Intermedio |
| [13-dbeaver-railway-postgres.md](./13-dbeaver-railway-postgres.md) | Conexion a PostgreSQL de Railway con DBeaver | Principiante |
| [14-database-backup-restore.md](./14-database-backup-restore.md) | Backup y restauracion de BD (pg_dump, DBeaver, Railway CLI) | Intermedio |
| [15-zorin-os-setup.md](./15-zorin-os-setup.md) | Configuracion del environment en Zorin OS | Principiante |
| [16-payment_gateway-setup.md](./16-payment_gateway-setup.md) | Configurar Payment Gateway Payment Links | Intermedio |
| [17-stripe-cli-setup.md](./17-stripe-cli-setup.md) | Instalar Stripe CLI y recibir webhooks locales | Intermedio |
| [18-stripe-products-setup.md](./18-stripe-products-setup.md) | Crear items y precios en Stripe Dashboard | Principiante |

## 🎯 Ruta de Aprendizaje Sugerida

### Para Principiantes (Day 1-2)
1. Lee [01-getting-started.md](./01-getting-started.md) - Configura tu environment
2. Lee [02-architecture.md](./02-architecture.md) - Entiende la estructura
3. Revisa [09-commands-reference.md](./09-commands-reference.md) - Commands esenciales

### Para Nivel Intermedio (Day 3-5)
4. Estudia [03-models-database.md](./03-models-database.md) - Models y BD
5. Practica con [04-api-development.md](./04-api-development.md) - APIs
6. Experimenta con [05-livewire-components.md](./05-livewire-components.md) - UI

### Para Nivel Avanzado (Day 6+)
7. Profundiza en [06-services-logic.md](./06-services-logic.md) - Services
8. Implementa [07-testing.md](./07-testing.md) - Testing
9. Aplica [08-best-practices.md](./08-best-practices.md) - Mejores practices
10. Domina [10-filament-admin.md](./10-filament-admin.md) - Panel administrativo

### Para Deploy (Cuando are listo)
11. Sigue [12-railway-deployment.md](./12-railway-deployment.md) - Deploy en Railway
12. Configura [13-dbeaver-railway-postgres.md](./13-dbeaver-railway-postgres.md) - Acceso a DB de operations

## 📖 Documentation Adicional

- [../design-system.md](../design-system.md) - Guia de Color y Sistema de Diseno (paleta, gradientes, componentes UI/UX)
- [../quick-start.md](../quick-start.md) - Inicio fast (resumen)
- [./10-filament-admin.md](./10-filament-admin.md) - Panel administrativo FilamentPHP (incluye Wizard y Actions con Modal)
- [../reports/fase-testing-report.md](../reports/fase-testing-report.md) - Reporte FASE Testing (67 tests, 165 assertions)
- [../reports/filament-capabilities-report.md](../reports/filament-capabilities-report.md) - Capacidades avanzadas de Filament
- [../features/swagger-implementation-report.md](../features/swagger-implementation-report.md) - Implementation de Swagger/OpenAPI
- [../features/order-wizard.md](../features/order-wizard.md) - Order Wizard: 5 pasos, Envia.com, fases A-D
- [../features/production-tracking-messaging.md](../features/production-tracking-messaging.md) - Plan Fase D: operations, tracking, Messaging
- [../api-reference.md](../api-reference.md) - Referencia completa de API
- [../backoffice-roadmap.md](../backoffice-roadmap.md) - Roadmap del backoffice

## 🔧 Stack Technological

```
┌─────────────────────────────────────────────────────┐
│                    FRONTEND                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │  Blade   │  │ Livewire │  │   Tailwind CSS   │  │
│  │Templates │  │   3.x    │  │                  │  │
│  └──────────┘  └──────────┘  └──────────────────┘  │
├─────────────────────────────────────────────────────┤
│                    BACKEND                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ Laravel  │  │ Filament │  │     Eloquent     │  │
│  │   12.x   │  │   3.3    │  │       ORM        │  │
│  └──────────┘  └──────────┘  └──────────────────┘  │
├─────────────────────────────────────────────────────┤
│                   DATABASE                           │
│  ┌──────────────────────────────────────────────┐  │
│  │              PostgreSQL 15                    │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

## 💡 Tips Quick

```bash
# Servidor de desarrollo
./scripts/serve.sh

# Consola interactiva
php artisan tinker

# Ver rutas API
php artisan route:list --path=api

# Resetear base de datos
php artisan migrate:fresh --seed

# Static analysis
composer analyse

# Format code
composer format
```

## 🆘 Soporte

Si tienes dudas:
1. Revisa las guides correspondientes
2. Busca en el code existente examples similares
3. Consulta la [documentation oficial de Laravel](https://laravel.com/docs)
4. Abre un issue en el repositorio
