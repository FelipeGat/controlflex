#!/bin/bash
set -e

echo "=== AlfaHome Deploy Script ==="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
APP_DIR="/root/AlfaHome"
DOMAIN="home.alfasolucoes.cloud"
EMAIL="admin@alfasolucoes.cloud"

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root"
    exit 1
fi

# Step 1: Clone repository
log_info "Step 1: Cloning repository..."
if [ -d "$APP_DIR" ]; then
    log_warn "Directory $APP_DIR already exists. Pulling latest changes..."
    cd "$APP_DIR"
    git pull
else
    cd /root
    git clone https://github.com/FelipeGat/AlfaHome.git
    cd "$APP_DIR"
fi

# Step 2: Setup environment
log_info "Step 2: Setting up environment..."
if [ ! -f ".env" ]; then
    cp .env.production .env
    log_info "Created .env from .env.production"
else
    log_warn ".env already exists, skipping..."
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    log_info "Generating application key..."
    # We'll generate this after containers are up
fi

# Step 3: Create necessary directories
log_info "Step 3: Creating directories..."
mkdir -p storage/logs/nginx
mkdir -p ssl
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Step 4: Generate SSL certificate
log_info "Step 4: Setting up SSL certificate..."
if [ ! -f "ssl/live/$DOMAIN/fullchain.pem" ]; then
    log_info "Starting nginx for Let's Encrypt challenge..."
    docker compose -f docker-compose.prod.yml up -d nginx

    # Wait for nginx to start
    sleep 5

    log_info "Requesting SSL certificate..."
    docker run --rm \
        -v $(pwd)/ssl:/etc/letsencrypt \
        -v $(pwd)/public:/var/www/html \
        certbot/certbot certonly \
        --webroot \
        --webroot-path=/var/www/html \
        -d "$DOMAIN" \
        --email "$EMAIL" \
        --agree-tos \
        --non-interactive \
        --force-renewal

    if [ $? -eq 0 ]; then
        log_info "SSL certificate obtained successfully!"
    else
        log_error "Failed to obtain SSL certificate. You may need to configure DNS first."
        log_info "Continuing with HTTP only..."
    fi

    # Stop nginx
    docker compose -f docker-compose.prod.yml down
else
    log_info "SSL certificate already exists"
fi

# Step 5: Build assets with Node
log_info "Step 5: Building frontend assets..."
docker compose -f docker-compose.prod.yml run --rm node

# Step 6: Start all services
log_info "Step 6: Starting all services..."
docker compose -f docker-compose.prod.yml up -d

# Wait for services to be ready
log_info "Waiting for services to start..."
sleep 15

# Step 7: Configure Laravel
log_info "Step 7: Configuring Laravel..."

# Generate app key
docker exec alfa-app php artisan key:generate --force
log_info "Application key generated"

# Create storage link
docker exec alfa-app php artisan storage:link
log_info "Storage link created"

# Run migrations
docker exec alfa-app php artisan migrate --force
log_info "Migrations completed"

# Run seeders
docker exec alfa-app php artisan db:seed --force
log_info "Database seeded"

# Clear and cache
docker exec alfa-app php artisan config:cache
docker exec alfa-app php artisan route:cache
docker exec alfa-app php artisan view:cache
log_info "Cache optimized"

# Set permissions
docker exec alfa-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Step 8: Show status
log_info "Step 8: Checking service status..."
docker compose -f docker-compose.prod.yml ps

echo ""
echo "=== Deploy Complete! ==="
echo ""
echo "URL: https://$DOMAIN"
echo ""
echo "Credenciais de acesso:"
echo "  Email: felipe@controleflex.com"
echo "  Senha: password"
echo ""
echo "Para ver logs: docker compose -f docker-compose.prod.yml logs -f"
echo "Para parar: docker compose -f docker-compose.prod.yml down"
echo ""
