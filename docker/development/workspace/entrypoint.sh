#!/bin/bash
set -e

echo "🚀 Workspace entrypoint starting..."
cd /var/www

# ------------------------------------------------
# Wait for MySQL (REAL readiness)
# ------------------------------------------------
echo "⏳ Waiting for MySQL..."

until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=mysql;dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [PDO::ATTR_TIMEOUT => 2]
    );
    \$pdo->query('SELECT 1');
} catch (Throwable \$e) {
    exit(1);
}
"; do
  sleep 2
done

echo "✅ MySQL ready"

# ------------------------------------------------
# Ensure .env exists
# ------------------------------------------------
if [ ! -f .env ]; then
  echo "📄 Creating .env from .env.example"
  cp .env.example .env
fi

# ------------------------------------------------
# Composer install (ONLY if vendor missing)
# ------------------------------------------------
if [ ! -d vendor ]; then
  echo "📦 Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
else
  echo "📦 Composer dependencies already installed"
fi

# ------------------------------------------------
# App key (ONLY if missing)
# ------------------------------------------------
if ! grep -q "^APP_KEY=base64" .env; then
  echo "🔑 Generating APP_KEY..."
  php artisan key:generate --force
fi

# ------------------------------------------------
# Migrations (SAFE & IDEMPOTENT)
# ------------------------------------------------
echo "🗄️ Running migrations (safe)..."
php artisan migrate || echo "⚠️ Migrations skipped"

# ------------------------------------------------
# Clear caches (safe)
# ------------------------------------------------
php artisan optimize:clear || true

echo "✅ Workspace ready!"

exec "$@"
