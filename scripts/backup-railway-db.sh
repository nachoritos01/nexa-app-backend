#!/bin/sh
#
# Backup Railway PostgreSQL database
#
# Usage:
#   ./scripts/backup-railway-db.sh "postgresql://user:pass@host:port/db"
#   DATABASE_PUBLIC_URL="postgresql://..." ./scripts/backup-railway-db.sh
#

set -e

DB_URL="${1:-$DATABASE_PUBLIC_URL}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
FILENAME="${BACKUP_DIR}/saas_template_${TIMESTAMP}.sql"

if [ -z "$DB_URL" ]; then
    echo "Error: Provide the DATABASE_PUBLIC_URL"
    echo ""
    echo "Usage:"
    echo "  $0 postgresql://user:pass@host:port/db"
    echo "  DATABASE_PUBLIC_URL=... $0"
    exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "==> Starting backup..."
pg_dump "$DB_URL" \
    --no-owner \
    --no-privileges \
    -f "$FILENAME"

SIZE=$(du -h "$FILENAME" | cut -f1)
echo "==> Backup complete: $FILENAME ($SIZE)"
