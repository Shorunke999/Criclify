#!/bin/bash
set -e

echo "🚀 Laravel entrypoint starting..."

# Go to app directory
cd /var/www

# -------------------------------------------------
# 1. Ensure .env exists
# -------------------------------------------------
if [ ! -f .env ]; then
  echo "📄 .env not found, creating from .env.example"
  cp .env.example .env
fi

# -------------------------------------------------
# 2. Install Composer dependencies (if needed)
# -------------------------------------------------
if [ ! -d vendor ]; then
  echo "📦 Installing composer dependencies..."
  composer install --no-interaction --prefer-dist
else
  echo "📦 Composer dependencies already installed"
fi

# -------------------------------------------------
# 3. Generate APP_KEY if missing
# -------------------------------------------------
if ! grep -q "APP_KEY=base64" .env; then
  echo "🔑 Generating APP_KEY..."
  php artisan key:generate
fi

# -------------------------------------------------
# 4. Wait for MySQL
# -------------------------------------------------
echo "⏳ Waiting for MySQL..."

until php -r "
try {
    new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'MySQL is ready';
} catch (Exception \$e) {
    exit(1);
}
"; do
  sleep 2
done

echo "✅ MySQL is up"

# -------------------------------------------------
# 5. Run migrations (safe)
# -------------------------------------------------
echo "🗄️ Running migrations..."
php artisan migrate --force || true

# -------------------------------------------------
# 6. Cache config (optional but recommended)
# -------------------------------------------------
php artisan config:clear
php artisan config:cache

echo "✅ Laravel ready"

# -------------------------------------------------
# 7. Hand over control to container CMD
# -------------------------------------------------
exec "$@"
