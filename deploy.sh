#!/usr/bin/env bash
set -euo pipefail

# =============================================================
# MailForge production deploy script
# Run from the app root on the production server:
#   bash deploy.sh
# =============================================================

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP="${PHP_BIN:-/usr/bin/php}"
SUPERVISOR_PROGRAM="${SUPERVISOR_PROGRAM:-mailforge-worker}"

cd "$APP_DIR"

# Ensure only one deploy runs at a time
if [ -f deploy.lock ]; then
    echo "Deploy already in progress (deploy.lock exists). Aborting."
    exit 1
fi
trap 'rm -f deploy.lock; ${PHP} artisan up --no-interaction >/dev/null 2>&1 || true' EXIT
touch deploy.lock

echo "==> Putting app into maintenance mode"
${PHP} artisan down --no-interaction

rollback() {
    echo "!! Deploy failed, attempting rollback"
    trap - EXIT
    rm -f deploy.lock
    ${PHP} artisan up --no-interaction
    exit 1
}

echo "==> Pulling latest code"
git pull --ff-only || rollback

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || rollback

echo "==> Clearing stale route cache (required before Wayfinder generation)"
${PHP} artisan route:clear --no-interaction || true

echo "==> Building frontend assets (Wayfinder routes are regenerated here)"
npm ci || rollback
npm run build || rollback

echo "==> Running database migrations"
${PHP} artisan migrate --force --no-interaction || rollback

echo "==> Caching config, routes and views"
${PHP} artisan optimize --no-interaction || rollback

echo "==> Linking storage"
[ -L public/storage ] || ${PHP} artisan storage:link --no-interaction || true

echo "==> Fixing permissions"
chmod -R ug+rwX storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

echo "==> Restarting queue workers"
if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart "${SUPERVISOR_PROGRAM}:*" || true
else
    ${PHP} artisan queue:restart --no-interaction || true
fi

echo "==> Reloading PHP-FPM"
if command -v service >/dev/null 2>&1 && service php-fpm status >/dev/null 2>&1; then
    service php-fpm reload || true
fi

echo "==> Bringing app back up"
${PHP} artisan up --no-interaction

rm -f deploy.lock

echo "==> Deploy complete"
