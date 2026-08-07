#!/bin/bash
set -e

echo "=== Render startup script ==="

# Generate APP_KEY kalau belum ada
if [ -z "$APP_KEY" ]; then
  echo "WARNING: APP_KEY not set, generating temporary key"
  export APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
fi

# Clear caches dulu
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan route:clear || true

# Cache config untuk performa
php artisan config:cache || echo "config:cache skipped"
php artisan route:cache || echo "route:cache skipped"

# Run migrations
php artisan migrate --force --no-interaction || echo "Migration skipped/failed (non-fatal)"

# Seed akun super_admin awal (idempotent, skip kalau sudah ada)
php artisan db:seed --force --no-interaction || echo "Seed skipped/failed (non-fatal)"

# Storage symlink (untuk fallback local storage)
php artisan storage:link 2>/dev/null || true

# Start server di port yang diset Render
PORT=${PORT:-8000}
echo "=== Starting Laravel on port $PORT ==="
exec php artisan serve --host=0.0.0.0 --port=$PORT
