#!/bin/bash

###############################################################################
# Post-Provisioning Script for Local Testing
# Modifies NGINX configuration to use port 8080 for localhost testing
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

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
    log_error "Please run as root (use sudo)"
    exit 1
fi

# Load environment variables
if [ -f .env ]; then
    source .env
else
    log_error "No .env file found!"
    exit 1
fi

DOMAIN="${DOMAIN:-localhost.local}"
NGINX_CONFIG="/etc/nginx/sites-available/${DOMAIN}"

log_info "Configuring NGINX for local testing on port 8080..."

if [ ! -f "$NGINX_CONFIG" ]; then
    log_error "NGINX config not found at: $NGINX_CONFIG"
    log_error "Please run provision.sh first"
    exit 1
fi

# Backup original config
cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup"
log_info "✓ Backed up original config to ${NGINX_CONFIG}.backup"

# Modify NGINX config to use port 8080
sed -i 's/listen 80;/listen 8080;/g' "$NGINX_CONFIG"
sed -i 's/listen \[::\]:80;/listen [::]:8080;/g' "$NGINX_CONFIG"

# Update redirect URLs to use port 8080
sed -i "s|return 301 http://www.${DOMAIN}|return 301 http://www.${DOMAIN}:8080|g" "$NGINX_CONFIG"

log_info "✓ Modified NGINX config to use port 8080"

# Test NGINX configuration
log_info "Testing NGINX configuration..."
nginx -t

if [ $? -eq 0 ]; then
    log_info "✓ NGINX configuration is valid"
    
    # Reload NGINX
    log_info "Reloading NGINX..."
    systemctl reload nginx
    
    log_info "========================================="
    log_info "Local configuration complete!"
    log_info "========================================="
    log_info ""
    log_info "Your WordPress site should be accessible at:"
    log_info "  http://www.${DOMAIN}:8080"
    log_info "  http://${DOMAIN}:8080 (redirects to www)"
    log_info ""
    log_info "Note: You may need to add '127.0.0.1 ${DOMAIN} www.${DOMAIN}' to /etc/hosts"
    log_info ""
else
    log_error "NGINX configuration test failed!"
    log_info "Restoring backup..."
    mv "${NGINX_CONFIG}.backup" "$NGINX_CONFIG"
    exit 1
fi
