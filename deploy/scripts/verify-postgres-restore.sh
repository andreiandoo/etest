#!/usr/bin/env bash
#
# Verifica faptul ca ultimul backup chiar poate fi restaurat.
# Un backup netestat nu este un backup.
#
# Restaureaza INTOTDEAUNA intr-o baza temporara, niciodata peste productie,
# si face curatenie inclusiv daca restore-ul esueaza.
#
# Se ruleaza cu sudo, fiindca are nevoie de rolul postgres si de acces la
# directorul de backup al user-ului aplicatiei:
#
#   sudo bash deploy/scripts/verify-postgres-restore.sh
#
# Variabile optionale:
#   ETEST_BACKUP_DIR   directorul cu dump-uri (implicit ~e-test-boaqw/backups/postgres)
#   ETEST_DUMP         un dump anume, in locul celui mai recent

set -euo pipefail
umask 077

APP_USER="${ETEST_APP_USER:-e-test-boaqw}"
BACKUP_DIR="${ETEST_BACKUP_DIR:-/home/${APP_USER}/backups/postgres}"
VERIFY_DB="etest_restore_verify_$$"
WORK_DIR=""

fatal() {
    echo "FATAL: $*" >&2
    exit 1
}

[ "$(id -u)" -eq 0 ] || fatal "ruleaza cu sudo: are nevoie de rolul postgres"

cleanup() {
    # Ruleaza si pe calea de eroare. Nu lasa in urma baze sau fisiere temporare.
    if [ -n "$WORK_DIR" ] && [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi
    sudo -u postgres dropdb --if-exists "$VERIFY_DB" >/dev/null 2>&1 || true
}
trap cleanup EXIT

if [ -n "${ETEST_DUMP:-}" ]; then
    DUMP="$ETEST_DUMP"
else
    [ -d "$BACKUP_DIR" ] || fatal "directorul de backup nu exista: ${BACKUP_DIR}"
    DUMP="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name '*.dump' -printf '%T@ %p\n' \
        | sort -rn | head -n 1 | cut -d' ' -f2-)"
fi

[ -n "$DUMP" ] || fatal "niciun dump gasit in ${BACKUP_DIR}"
[ -s "$DUMP" ] || fatal "dump gol sau inexistent: ${DUMP}"

echo "Verific: ${DUMP}"

# Dump-ul e in home-ul user-ului aplicatiei (chmod 700), unde postgres nu are
# acces. Il copiem intr-un director temporar pe care il poate citi.
WORK_DIR="$(mktemp -d)"
cp "$DUMP" "${WORK_DIR}/verify.dump"
chown postgres:postgres "${WORK_DIR}/verify.dump"
chmod 700 "$WORK_DIR"
chown postgres:postgres "$WORK_DIR"

sudo -u postgres createdb "$VERIFY_DB"

sudo -u postgres pg_restore \
    --no-owner \
    --no-acl \
    --exit-on-error \
    --dbname="$VERIFY_DB" \
    "${WORK_DIR}/verify.dump"

TABLE_COUNT="$(sudo -u postgres psql -tAd "$VERIFY_DB" -c \
    "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public';")"

MIGRATION_COUNT="$(sudo -u postgres psql -tAd "$VERIFY_DB" -c \
    "SELECT count(*) FROM migrations;" 2>/dev/null || echo 0)"

echo "Tabele in public: ${TABLE_COUNT}"
echo "Migrari inregistrate: ${MIGRATION_COUNT}"

# O baza restaurata fara tabele inseamna backup inutil, chiar daca pg_restore a iesit 0.
[ "$TABLE_COUNT" -gt 0 ] || fatal "baza restaurata nu contine niciun tabel"

echo "RESTORE VERIFICATION: PASS"
