#!/bin/bash
set -e

# Generate .env file from Railway env vars (semua value dalam quote)
cat > .env <<EOF
APP_NAME="${APP_NAME}"
APP_ENV="${APP_ENV}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG}"
APP_URL="${APP_URL}"

LOG_CHANNEL="${LOG_CHANNEL}"
LOG_LEVEL="${LOG_LEVEL}"

DB_CONNECTION="${DB_CONNECTION}"
DB_HOST="${DB_HOST}"
DB_PORT="${DB_PORT}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"

MIDTRANS_SERVER_KEY="${MIDTRANS_SERVER_KEY}"
MIDTRANS_CLIENT_KEY="${MIDTRANS_CLIENT_KEY}"
MIDTRANS_IS_PRODUCTION="${MIDTRANS_IS_PRODUCTION}"
MIDTRANS_IS_SANITIZED="${MIDTRANS_IS_SANITIZED}"
MIDTRANS_IS_3DS="${MIDTRANS_IS_3DS}"

IPL_MONTHLY_AMOUNT="${IPL_MONTHLY_AMOUNT}"
IPL_KEDUKAAN_AMOUNT="${IPL_KEDUKAAN_AMOUNT}"
ADMIN_PHONE="${ADMIN_PHONE}"
FRONTEND_URL="${FRONTEND_URL}"

FILESYSTEM_DISK="${FILESYSTEM_DISK}"
CACHE_DRIVER="${CACHE_DRIVER}"
SESSION_DRIVER="${SESSION_DRIVER}"
QUEUE_CONNECTION="${QUEUE_CONNECTION}"
EOF

echo "=== .env file created ==="

# Clear all caches
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan route:clear || true

# Ensure storage dirs exist (penting setelah mount volume)
mkdir -p storage/app/public/pengaduan storage/app/public/avatars storage/app/public/news storage/app/public/logos
chmod -R 775 storage/app/public || true

# Create storage symlink (untuk gambar di /storage/*)
# Force recreate karena volume mount bisa membuat symlink stale
rm -f public/storage 2>/dev/null || true
php artisan storage:link 2>/dev/null || echo "storage:link already exists or failed (non-fatal)"

# Run migrations (don't fail if migrations have issue)
php artisan migrate --force --no-interaction || echo "Migration step skipped/failed (non-fatal)"

# Start server
echo "=== Starting server on port $PORT ==="
exec php artisan serve --host=0.0.0.0 --port=$PORT
