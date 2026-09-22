#!/usr/bin/env bash
#
# Deploy repetabil pentru e-test.ro, din branch-ul main.
#
# Ruleaza ca user-ul aplicatiei (pe Ploi: user-ul izolat al site-ului).
# In Ploi, campul "Deploy script" al site-ului trebuie sa contina doar:
#
#   cd /home/e-test-boaqw/e-test.ro && bash deploy/scripts/deploy.sh
#
# Reguli respectate, conform docs/PRODUCTION_HANDOFF_CLAUDE_CODE.md:
#   - refuza sa ruleze daca nu esti pe branch-ul asteptat;
#   - nu atinge niciodata APP_KEY;
#   - face backup inainte de migrate;
#   - nu foloseste migrate:fresh / refresh / reset;
#   - scoate site-ul din maintenance chiar daca un pas esueaza (trap);
#   - reporneste queue worker-ul la final.
#
# Variabile optionale:
#   ETEST_PHP_BIN          binarul PHP (implicit php8.4)
#   ETEST_DEPLOY_BRANCH    branch-ul asteptat (implicit main)
#   ETEST_SKIP_BACKUP      1 la primul deploy, cand baza inca nu are schema

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP_BIN="${ETEST_PHP_BIN:-php8.4}"
BRANCH="${ETEST_DEPLOY_BRANCH:-main}"
MAINTENANCE=0

cd "$APP_DIR"

fatal() {
    echo "FATAL: $*" >&2
    exit 1
}

step() {
    echo
    echo "==> $*"
}

finish() {
    # Ruleaza si cand un pas de mai jos a esuat. Un deploy picat nu trebuie
    # sa lase site-ul blocat in maintenance mode.
    local code=$?

    if [ "$MAINTENANCE" -eq 1 ]; then
        echo
        echo "==> Scot site-ul din maintenance"
        "$PHP_BIN" artisan up || true
    fi

    if [ "$code" -ne 0 ]; then
        echo
        echo "DEPLOY ESUAT (exit ${code}). Verifica storage/logs/laravel.log." >&2
    fi

    exit "$code"
}
trap finish EXIT

command -v "$PHP_BIN" >/dev/null 2>&1 || fatal "${PHP_BIN} nu exista in PATH"
[ -f .env ] || fatal ".env lipseste in ${APP_DIR}"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
[ "$CURRENT_BRANCH" = "$BRANCH" ] \
    || fatal "esti pe '${CURRENT_BRANCH}', deploy-ul se face doar din '${BRANCH}'"

step "Aduc ${BRANCH} din origin"
git fetch origin "$BRANCH"
git reset --hard "origin/${BRANCH}"
echo "Commit: $(git rev-parse --short HEAD) $(git log -1 --pretty=%s)"

step "Dependinte PHP"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

step "Dependinte frontend si build"
# npm ci ar fi de preferat, dar necesita package-lock.json, care lipseste din repo.
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi
npm run build

step "Maintenance mode"
"$PHP_BIN" artisan down --retry=60
MAINTENANCE=1

if [ "${ETEST_SKIP_BACKUP:-0}" = "1" ]; then
    step "Backup sarit (ETEST_SKIP_BACKUP=1)"
else
    step "Backup PostgreSQL inainte de migrari"
    bash deploy/scripts/backup-postgres.sh
fi

step "Migrari"
"$PHP_BIN" artisan migrate --force

step "Rebuild cache"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

step "Repornesc queue worker-ul"
"$PHP_BIN" artisan queue:restart

step "Scot site-ul din maintenance"
"$PHP_BIN" artisan up
MAINTENANCE=0

step "Reload PHP-FPM (opcache)"
# Ploi permite site-ului sa reincarce FPM fara parola. Daca nu e permis,
# nu oprim deploy-ul: cu validate_timestamps activ codul nou e oricum preluat.
sudo -n service "php8.4-fpm" reload 2>/dev/null \
    || echo "Nu am putut da reload la PHP-FPM (lipsesc drepturi sudo). Continuu."

echo
echo "DEPLOY OK: $(git rev-parse --short HEAD)"
