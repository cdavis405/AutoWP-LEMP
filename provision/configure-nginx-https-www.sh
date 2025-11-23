#!/bin/bash

#############################################################################
# NGINX HTTPS + WWW Enforcement Configuration Script
#############################################################################
# Purpose: Configure NGINX to enforce HTTPS and WWW subdomain after Certbot
# Usage: sudo ./configure-nginx-https-www.sh
# 
# This script should be run AFTER:
# 1. provision.sh has completed
# 2. Certbot has been run: sudo certbot --nginx -d domain -d www.domain
#
# What it does:
# - Backs up existing NGINX config
# - Replaces config with proper HTTPS + WWW enforcement
# - Tests NGINX configuration
# - Reloads NGINX if successful
#############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging functions
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
    log_error "This script must be run as root (use sudo)"
    exit 1
fi

# Load environment variables
if [ -f .env ]; then
    source .env
elif [ -f /root/autowp-lemp/provision/.env ]; then
    source /root/autowp-lemp/provision/.env
else
    log_error "Cannot find .env file. Please ensure it exists."
    exit 1
fi

# Verify DOMAIN is set
if [ -z "$DOMAIN" ]; then
    log_error "DOMAIN variable not set in .env file"
    exit 1
fi

NGINX_CONFIG="/etc/nginx/sites-available/${DOMAIN}"
BACKUP_CONFIG="/etc/nginx/sites-available/${DOMAIN}.backup-$(date +%Y%m%d-%H%M%S)"
WEB_ROOT="/var/www/${DOMAIN}"

log_info "Starting NGINX HTTPS + WWW configuration for: ${DOMAIN}"

# Check if NGINX config exists
if [ ! -f "$NGINX_CONFIG" ]; then
    log_error "NGINX config not found: $NGINX_CONFIG"
    log_error "Please run provision.sh first"
    exit 1
fi

# Check if SSL certificates exist
CERT_PATH="/etc/letsencrypt/live/${DOMAIN}"
if [ ! -d "$CERT_PATH" ]; then
    log_error "SSL certificates not found at: $CERT_PATH"
    log_error "Please run Certbot first:"
    log_error "  sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
    exit 1
fi

# Backup existing configuration
log_info "Backing up existing NGINX config..."
cp "$NGINX_CONFIG" "$BACKUP_CONFIG"
log_info "Backup saved to: $BACKUP_CONFIG"

# Create new NGINX configuration with HTTPS + WWW enforcement
log_info "Creating new NGINX configuration..."
cat > "$NGINX_CONFIG" <<EOF
# Redirect HTTP non-www to HTTPS www
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://www.${DOMAIN}\$request_uri;
}

# Redirect HTTP www to HTTPS www
server {
    listen 80;
    listen [::]:80;
    server_name www.${DOMAIN};
    return 301 https://www.${DOMAIN}\$request_uri;
}

# Redirect HTTPS non-www to HTTPS www
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};
    
    ssl_certificate ${CERT_PATH}/fullchain.pem;
    ssl_certificate_key ${CERT_PATH}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    return 301 https://www.${DOMAIN}\$request_uri;
}

# Main HTTPS site (www) - serves WordPress
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.${DOMAIN};
    
    root ${WEB_ROOT};
    index index.php index.html index.htm;
    
    # SSL Configuration
    ssl_certificate ${CERT_PATH}/fullchain.pem;
    ssl_certificate_key ${CERT_PATH}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logging
    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log /var/log/nginx/${DOMAIN}_error.log;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
    
    # Client upload size
    client_max_body_size 64M;
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /wp-config.php {
        deny all;
    }
    
    location ~ /readme.html {
        deny all;
    }
    
    location = /xmlrpc.php {
        deny all;
    }
    
    # WordPress permalinks
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to PHP files in uploads
    location ~* /(?:uploads|files)/.*\.php$ {
        deny all;
    }
}
EOF

log_info "✓ NGINX configuration created"

# Test NGINX configuration
log_info "Testing NGINX configuration..."
if nginx -t 2>&1 | grep -q "successful"; then
    log_info "✓ NGINX configuration test passed"
else
    log_error "NGINX configuration test failed!"
    log_error "Restoring backup..."
    cp "$BACKUP_CONFIG" "$NGINX_CONFIG"
    nginx -t
    exit 1
fi

# Reload NGINX
log_info "Reloading NGINX..."
systemctl reload nginx

if [ $? -eq 0 ]; then
    log_info "✓ NGINX reloaded successfully"
else
    log_error "Failed to reload NGINX"
    log_error "Restoring backup..."
    cp "$BACKUP_CONFIG" "$NGINX_CONFIG"
    systemctl reload nginx
    exit 1
fi

# Test redirects
log_info ""
log_info "============================================"
log_info "Configuration complete!"
log_info "============================================"
log_info ""
log_info "Testing redirects..."
log_info ""

# Function to test URL
test_url() {
    local url=$1
    local expected=$2
    echo -n "Testing: $url ... "
    
    response=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" -L "$url" 2>/dev/null || echo "FAILED")
    
    if echo "$response" | grep -q "$expected"; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}Check manually${NC}"
    fi
}

test_url "http://${DOMAIN}" "https://www.${DOMAIN}"
test_url "http://www.${DOMAIN}" "https://www.${DOMAIN}"
test_url "https://${DOMAIN}" "https://www.${DOMAIN}"

log_info ""
log_info "Final test - accessing site:"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "https://www.${DOMAIN}" 2>/dev/null)
if [ "$response_code" = "200" ]; then
    log_info "✓ https://www.${DOMAIN} returns 200 OK"
else
    log_warn "https://www.${DOMAIN} returned: $response_code"
fi

log_info ""
log_info "============================================"
log_info "Next Steps:"
log_info "============================================"
log_info "1. Test all URL variations in your browser:"
log_info "   - http://${DOMAIN}"
log_info "   - http://www.${DOMAIN}"
log_info "   - https://${DOMAIN}"
log_info "   - https://www.${DOMAIN}"
log_info ""
log_info "2. All should redirect to: https://www.${DOMAIN}"
log_info ""
log_info "3. Update WordPress URLs if needed:"
log_info "   cd ${WEB_ROOT}"
log_info "   sudo -u www-data wp option update siteurl 'https://www.${DOMAIN}'"
log_info "   sudo -u www-data wp option update home 'https://www.${DOMAIN}'"
log_info ""
log_info "4. Backup location: $BACKUP_CONFIG"
log_info ""
log_info "✓ Configuration complete!"

exit 0
