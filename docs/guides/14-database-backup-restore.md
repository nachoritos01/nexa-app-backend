# Backup y Restauracion de Base de Datos (Railway PostgreSQL)

Guia para exportar, descargar y restaurar la base de datos de operations.

> **Requirements:** Una o mas de estas herramientas:
> - DBeaver Community (GUI)
> - `pg_dump` / `pg_restore` (CLI)
> - Railway CLI

---

## Credenciales Necesarias

Antes de cualquier metodo, necesitas la URL publica de la BD. Ver [13-dbeaver-railway-postgres.md](./13-dbeaver-railway-postgres.md) para obtenerlas.

```
DATABASE_PUBLIC_URL=postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway
```

Desglose:

| Campo | Example |
|---|---|
| Host | `viaduct.proxy.rlwy.net` |
| Puerto | `24816` (el publico, NO 5432) |
| User | `postgres` |
| Password | (de Railway Variables) |
| Database | `railway` |

---

## Metodo 1: pg_dump (CLI - Recommended)

El metodo mas confiable y completo. Genera un file con toda la estructura y datos.

### Instalar pg_dump (si no lo tienes)

```bash
# macOS con Homebrew
brew install libpq
brew link --force libpq

# Check
pg_dump --version
```

### Backup completo (SQL)

```bash
# Exportar toda la BD a un file .sql
pg_dump "postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway" \
  --no-owner \
  --no-privileges \
  -f backup_saas-template_$(date +%Y%m%d).sql
```

| Flag | Para que |
|---|---|
| `--no-owner` | No incluye `ALTER OWNER`, evita errores al restaurar con otro user |
| `--no-privileges` | No incluye `GRANT/REVOKE`, evita errores de permisos |
| `-f` | File de salida |

### Backup solo datos (sin estructura)

```bash
pg_dump "postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway" \
  --data-only \
  --no-owner \
  -f backup_data_$(date +%Y%m%d).sql
```

### Backup solo estructura (sin datos)

```bash
pg_dump "postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway" \
  --schema-only \
  --no-owner \
  -f backup_schema_$(date +%Y%m%d).sql
```

### Backup de tables especificas

```bash
# Solo items y precios
pg_dump "postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway" \
  --no-owner \
  -t products \
  -t pricing_rules \
  -t dimension_limits \
  -t sizes \
  -t colors \
  -f backup_catalogo_$(date +%Y%m%d).sql
```

### Backup comprimido (formato custom)

```bash
# Formato custom de pg_dump (comprimido, restauracion selectiva)
pg_dump "postgresql://postgres:PASSWORD@viaduct.proxy.rlwy.net:PUERTO/railway" \
  --no-owner \
  --no-privileges \
  -Fc \
  -f backup_saas-template_$(date +%Y%m%d).dump
```

| Formato | Extension | Ventaja |
|---|---|---|
| SQL plano (`-f`) | `.sql` | Legible, editable con cualquier editor |
| Custom (`-Fc`) | `.dump` | Comprimido, restauracion selectiva de tables |
| Directory (`-Fd`) | carpeta | Backup paralelo, mas rapido en BD grandes |

---

## Metodo 2: DBeaver (GUI)

### Exportar tables a CSV/SQL

1. Conectar a la BD (ver [guia 13](./13-dbeaver-railway-postgres.md))
2. En el panel izquierdo, expandir: **railway** > **Schemas** > **public** > **Tables**
3. Seleccionar tables (Ctrl+click para multiples)
4. Click derecho > **Export Data**

#### Exportar a CSV

5. Seleccionar **CSV** como formato
6. Configurar:
   - Delimiter: `,`
   - Encoding: `UTF-8`
   - Include header: Si
7. Elegir directorio de salida
8. Click **Start**

#### Exportar a SQL (INSERT statements)

5. Seleccionar **Database** como formato (Target: SQL)
6. Configurar:
   - Include `CREATE TABLE`: Si (si quieres estructura)
   - Insert method: `INSERT`
   - Rows per statement: 1
7. Elegir directorio de salida
8. Click **Start**

### Backup completo con DBeaver

1. Menu: **Tools** > **Dump Database** (o **Backup**)
2. Seleccionar la conexion Railway
3. Elegir tables (todas o especificas)
4. Formato: **plain** (SQL) o **custom** (comprimido)
5. Output: elegir carpeta y name
6. Click **Start**

> DBeaver ejecuta `pg_dump` internamente. Si no lo encuentra, indicar la ruta en: **Window** > **Preferences** > **Editors** > **PostgreSQL** > **Path to pg_dump**.

---

## Metodo 3: railway connect + pg_dump

Si tienes Railway CLI con soporte para `connect`:

```bash
# Vincular al service Postgres
railway link
# Seleccionar: Postgres

# Conectar y ejecutar dump
railway connect --command "pg_dump -U postgres --no-owner railway" > backup_$(date +%Y%m%d).sql

# Re-vincular a la app
railway link
# Seleccionar: saas-template-laravel
```

> Este metodo puede no funcionar en todas las versiones del CLI.

---

## Restaurar Backup

### En BD local (desarrollo)

```bash
# 1. Crear BD local si no existe
createdb saas-template_backup

# 2. Restaurar desde SQL plano
psql saas-template_backup < backup_saas-template_20260209.sql

# 3. O restaurar desde formato custom
pg_restore -d saas-template_backup --no-owner backup_saas-template_20260209.dump
```

### En BD local con Laravel (migrate + seed)

Si solo necesitas los datos de catalogo y ya tienes las migrations:

```bash
# Resetear BD local y sembrar datos frescos
/opt/homebrew/bin/php artisan migrate:fresh --seed
```

### Restaurar en otra instancia de Railway

```bash
# Restaurar SQL plano en la nueva BD
psql "postgresql://postgres:PASSWORD@nuevo-host:PUERTO/railway" \
  < backup_saas-template_20260209.sql

# O restaurar formato custom
pg_restore \
  -d "postgresql://postgres:PASSWORD@nuevo-host:PUERTO/railway" \
  --no-owner \
  --clean \
  backup_saas-template_20260209.dump
```

| Flag | Para que |
|---|---|
| `--clean` | Elimina objetos antes de crearlos (evita conflictos) |
| `--if-exists` | No falla si el objeto a borrar no existe |
| `--no-owner` | Ignora el owner original |

### Restaurar tables especificas (formato custom)

```bash
# Solo restaurar la table products
pg_restore -d saas-template_backup \
  --no-owner \
  -t products \
  backup_saas-template_20260209.dump
```

---

## Script de Backup Automatizado

Crear un script para backups rapidos:

```bash
#!/bin/sh
# scripts/backup-railway-db.sh
#
# Uso: ./scripts/backup-railway-db.sh
# Requiere: DATABASE_PUBLIC_URL como variable de environment o argumento

set -e

DB_URL="${1:-$DATABASE_PUBLIC_URL}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
FILENAME="${BACKUP_DIR}/saas-template_${TIMESTAMP}.sql"

if [ -z "$DB_URL" ]; then
    echo "Error: Proporciona la DATABASE_PUBLIC_URL"
    echo "Uso: $0 postgresql://user:pass@host:port/db"
    echo "  o: DATABASE_PUBLIC_URL=... $0"
    exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "==> Iniciando backup..."
pg_dump "$DB_URL" \
    --no-owner \
    --no-privileges \
    -f "$FILENAME"

SIZE=$(du -h "$FILENAME" | cut -f1)
echo "==> Backup completado: $FILENAME ($SIZE)"
```

Agregar `backups/` al `.gitignore`:

```
# En .gitignore
backups/
```

---

## Comparar Datos entre Environments

Para check que operations y local tienen los mismos datos de catalogo:

```bash
# Contar registrations en operations
psql "postgresql://postgres:PASSWORD@host:puerto/railway" -c "
  SELECT 'products' AS table, COUNT(*) FROM products
  UNION ALL SELECT 'sizes', COUNT(*) FROM sizes
  UNION ALL SELECT 'colors', COUNT(*) FROM colors
  UNION ALL SELECT 'pricing_rules', COUNT(*) FROM pricing_rules
  UNION ALL SELECT 'dimension_limits', COUNT(*) FROM dimension_limits
  UNION ALL SELECT 'users', COUNT(*) FROM users
  UNION ALL SELECT 'orders', COUNT(*) FROM orders;
"
```

---

## Troubleshooting

### "pg_dump: command not found"

```bash
# macOS
brew install libpq && brew link --force libpq

# O usar la ruta completa
/opt/homebrew/opt/libpq/bin/pg_dump ...
```

### "connection refused" o timeout

Estas usando el host/puerto interno. Usar la URL **publica** (ver [guia 13](./13-dbeaver-railway-postgres.md#troubleshooting)).

### "permission denied" al restaurar

Agregar `--no-owner --no-privileges` al command de restauracion.

### Backup muy grande

```bash
# Comprimir con gzip
pg_dump "postgresql://..." --no-owner | gzip > backup_$(date +%Y%m%d).sql.gz

# Restaurar desde gzip
gunzip -c backup_20260209.sql.gz | psql saas-template_backup
```

### "relation already exists" al restaurar

La table ya existe en la BD destino. Opciones:

```bash
# Opcion A: Limpiar antes de restaurar
pg_restore --clean --if-exists --no-owner -d mi_bd backup.dump

# Opcion B: Eliminar BD y recrear
dropdb mi_bd && createdb mi_bd
pg_restore --no-owner -d mi_bd backup.dump
```

---

## Resumen de Commands

| Accion | Command |
|---|---|
| Backup completo (SQL) | `pg_dump URL --no-owner -f backup.sql` |
| Backup comprimido | `pg_dump URL --no-owner -Fc -f backup.dump` |
| Backup solo datos | `pg_dump URL --data-only -f data.sql` |
| Backup solo estructura | `pg_dump URL --schema-only -f schema.sql` |
| Backup tables especificas | `pg_dump URL -t table1 -t table2 -f parcial.sql` |
| Restaurar SQL | `psql mi_bd < backup.sql` |
| Restaurar custom | `pg_restore -d mi_bd --no-owner backup.dump` |
| Restaurar table especifica | `pg_restore -d mi_bd -t table backup.dump` |

---

*Creado: 2026-02-09*
