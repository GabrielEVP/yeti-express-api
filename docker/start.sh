#!/bin/sh
set -e

echo "=== Yeti Express API Startup ==="

# Railway injects $PORT — default to 80 for local
PORT=${PORT:-80}
echo "Listening on port: $PORT"

# Generate nginx config with actual port
envsubst '${PORT}' < /etc/nginx/sites-available/default > /etc/nginx/sites-available/default.conf
mv /etc/nginx/sites-available/default.conf /etc/nginx/sites-available/default

# Cache Laravel config, routes and views
echo "[1/4] Caching config..."
php artisan config:cache

echo "[2/4] Caching routes..."
php artisan route:cache

echo "[3/4] Running migrations..."
php artisan migrate --force

echo "[4/4] Starting services via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
