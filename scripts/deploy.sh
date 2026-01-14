#!/bin/bash
# ===========================================
# Deploy Script for Rumahweb
# ===========================================
# Jalankan script ini di server setelah git pull

set -e

echo "🚀 Starting deployment..."

# Go to app directory
cd "$(dirname "$0")/.."

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main

# Install dependencies (production)
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Cache configuration
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate Swagger docs
echo "📚 Generating API documentation..."
php artisan l5-swagger:generate

# Clear old cache
echo "🧹 Clearing old cache..."
php artisan cache:clear

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo "✅ Deployment complete!"
