#!/bin/bash
set -euo pipefail

SCHEMA_FILE="${SCHEMA_FILE:-/tmp/repo/sql/esquema.sql}"
POSTGRES_DB="${POSTGRES_DB:-espocrm}"
POSTGRES_USER="${POSTGRES_USER:-espocrm}"
FORCE="${FORCE:-0}"

if [ ! -f "$SCHEMA_FILE" ]; then
  echo "ERROR: Schema file not found at $SCHEMA_FILE"
  echo "Mount the repository in Dokploy and make sure sql/esquema.sql is available."
  exit 1
fi

existing_tables="$(
  psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Atqc \
    "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public';"
)"

if [ "${existing_tables:-0}" != "0" ] && [ "$FORCE" != "1" ]; then
  echo "ERROR: Database $POSTGRES_DB already has $existing_tables tables in schema public."
  echo "If you really want to apply the schema again, rerun with FORCE=1."
  exit 1
fi

echo "Loading schema from $SCHEMA_FILE into $POSTGRES_DB..."
psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f "$SCHEMA_FILE"
echo "Schema load completed."
