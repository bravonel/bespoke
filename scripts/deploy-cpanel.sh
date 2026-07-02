#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${SOURCE_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
TARGET_DIR="${TARGET_DIR:-/home/bespokea/apps/bespoke-os}"
BACKUP_ROOT="${BACKUP_ROOT:-/home/bespokea/backups/bespoke-os}"
STAMP="$(date +%Y%m%d%H%M%S)"

echo "Deploying Bespoke OS"
echo "Source: ${SOURCE_DIR}"
echo "Target: ${TARGET_DIR}"

if [ ! -f "${SOURCE_DIR}/artisan" ]; then
    echo "Source does not look like a Laravel app: ${SOURCE_DIR}" >&2
    exit 1
fi

mkdir -p "${TARGET_DIR}" "${BACKUP_ROOT}"

if [ -f "${TARGET_DIR}/.env" ]; then
    cp "${TARGET_DIR}/.env" "${BACKUP_ROOT}/.env.${STAMP}"
fi

if [ -f "${TARGET_DIR}/database/database.sqlite" ]; then
    mkdir -p "${BACKUP_ROOT}/database"
    cp "${TARGET_DIR}/database/database.sqlite" "${BACKUP_ROOT}/database/database.sqlite.${STAMP}"
fi

if [ -d "${TARGET_DIR}/storage" ]; then
    tar -C "${TARGET_DIR}" -czf "${BACKUP_ROOT}/storage.${STAMP}.tar.gz" storage
fi

rsync -a --delete \
    --exclude='.git/' \
    --exclude='.env' \
    --exclude='storage/' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='database/database.sqlite' \
    --exclude='.phpunit.result.cache' \
    --exclude='.DS_Store' \
    "${SOURCE_DIR}/" "${TARGET_DIR}/"

cd "${TARGET_DIR}"

composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
    if [ -f package-lock.json ]; then
        npm ci --no-audit --no-fund
    else
        npm install --no-audit --no-fund
    fi

    npm run build
else
    echo "npm not found; skipping Vite build" >&2
fi

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy complete: ${STAMP}"
