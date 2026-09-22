#!/usr/bin/env bash
#
# Backup PostgreSQL pentru e-test.ro.
#
# Ruleaza ca user-ul aplicatiei (pe Ploi: user-ul izolat al site-ului).
# Nu are nevoie de sudo si nu foloseste rolul postgres: se conecteaza pe TCP
# la 127.0.0.1 cu exact credentialele aplicatiei din .env.
#
# Variabile optionale:
#   ETEST_BACKUP_DIR              destinatia (implicit ~/backups/postgres)
#   ETEST_BACKUP_RETENTION_DAYS   zile de pastrare (implicit 14)
#
# Iese non-zero la orice eroare, inclusiv daca dump-ul rezulta gol.

set -euo pipefail
umask 077

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV_FILE="${APP_DIR}/.env"
BACKUP_DIR="${ETEST_BACKUP_DIR:-${HOME}/backups/postgres}"
RETENTION_DAYS="${ETEST_BACKUP_RETENTION_DAYS:-14}"

fatal() {
    echo "FATAL: $*" >&2
    exit 1
}

[ -f "$ENV_FILE" ] || fatal ".env lipseste la ${ENV_FILE}"

# Citeste o singura cheie din .env fara a interpreta fisierul ca script.
# Un `source .env` ar executa orice s-ar afla acolo.
env_get() {
    local key="$1" line value
    line="$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 || true)"
    [ -n "$line" ] || return 0
    value="${line#*=}"
    value="${value%\"}"; value="${value#\"}"
    value="${value%\'}"; value="${value#\'}"
    printf '%s' "$value"
}

DB_HOST="$(env_get DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)"; DB_PORT="${DB_PORT:-5432}"
DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"
DB_PASS="$(env_get DB_PASSWORD)"

[ -n "$DB_NAME" ] || fatal "DB_DATABASE nu este setat in .env"
[ -n "$DB_USER" ] || fatal "DB_USERNAME nu este setat in .env"

command -v pg_dump >/dev/null 2>&1 || fatal "pg_dump nu este instalat"

# Parola ajunge intr-un PGPASSFILE temporar, nu in linia de comanda:
# altfel ar fi vizibila in `ps` pentru alti utilizatori.
PGPASS_FILE="$(mktemp)"
trap 'rm -f "$PGPASS_FILE"' EXIT
printf '%s:%s:%s:%s:%s\n' "$DB_HOST" "$DB_PORT" "$DB_NAME" "$DB_USER" "$DB_PASS" > "$PGPASS_FILE"
chmod 600 "$PGPASS_FILE"
export PGPASSFILE="$PGPASS_FILE"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

TIMESTAMP="$(date -u +%Y%m%d_%H%M%SZ)"
DEST="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.dump"

pg_dump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --username="$DB_USER" \
    --dbname="$DB_NAME" \
    --format=custom \
    --no-owner \
    --no-acl \
    --file="$DEST"

# pg_dump poate iesi cu 0 si totusi lasa un fisier gol; verificam explicit.
if [ ! -s "$DEST" ]; then
    rm -f "$DEST"
    fatal "dump gol, backup-ul a fost sters: ${DEST}"
fi

# Retentie: sterge doar dump-urile acestei baze, nimic altceva din director.
find "$BACKUP_DIR" -maxdepth 1 -type f -name "${DB_NAME}_*.dump" \
    -mtime "+${RETENTION_DAYS}" -delete

SIZE="$(du -h "$DEST" | cut -f1)"
echo "OK backup: ${DEST} (${SIZE})"
