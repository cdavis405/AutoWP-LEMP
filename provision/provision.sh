#!/bin/bash

###############################################################################
# RetaGuide Server Provisioning Script
# For Ubuntu 22.04 LTS on Azure VM
# Installs: NGINX, PHP 8.2, MariaDB, Certbot, WordPress
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging
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

log_info "Starting RetaGuide server provisioning..."

# Load environment variables
if [ -f .env ]; then
    source .env
else
    log_warn "No .env file found. Using defaults."
fi

# Default values
DOMAIN="${DOMAIN:-retaguide.com}"
DB_NAME="${DB_NAME:-retaguide_wp}"
DB_USER="${DB_USER:-retaguide_user}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32)}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-$(openssl rand -base64 16)}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@${DOMAIN}}"

log_info "Configuration:"
log_info "  Domain: $DOMAIN"
log_info "  DB Name: $DB_NAME"
log_info "  DB User: $DB_USER"

# Update system
log_info "Updating system packages..."
apt-get update
apt-get upgrade -y

# Install essential packages
log_info "Installing essential packages..."
apt-get install -y \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    ufw \
    fail2ban \
    certbot \
    python3-certbot-nginx

# Add PHP 8.2 repository
log_info "Adding PHP 8.2 repository..."
add-apt-repository -y ppa:ondrej/php
apt-get update

# Install NGINX
log_info "Installing NGINX..."
apt-get install -y nginx

# Install PHP 8.2 and extensions
log_info "Installing PHP 8.2 and extensions..."
apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-xmlrpc \
    php8.2-soap \
    php8.2-intl \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-imagick

# Configure PHP
log_info "Configuring PHP..."
PHP_INI="/etc/php/8.2/fpm/php.ini"
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' $PHP_INI
sed -i 's/post_max_size = .*/post_max_size = 64M/' $PHP_INI
sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI
sed -i 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI
sed -i 's/max_input_time = .*/max_input_time = 300/' $PHP_INI

# Install MariaDB
log_info "Installing MariaDB..."
apt-get install -y mariadb-server mariadb-client

# Secure MariaDB installation
log_info "Securing MariaDB..."
# Use modern, compatible commands to set root password and remove anonymous/test DBs.
# Some MariaDB installs use unix_socket for root; handle that safely.
set +e
MYSQL_OK=0
mysql --version >/dev/null 2>&1 || MYSQL_OK=1
if [ "$MYSQL_OK" -eq 1 ]; then
    log_warn "mysql client not available; skipping DB secure steps"
else
    # Attempt to set root password using ALTER USER if available
    log_info "Configuring root user authentication..."
    mysql -e "SELECT 1" >/dev/null 2>&1 || true

    # Try to detect if unix_socket auth is in use for root
    HAS_UNIX_SOCKET=$(mysql -N -s -e "SELECT plugin FROM mysql.user WHERE User='root' LIMIT 1;" 2>/dev/null || echo "")
    if echo "$HAS_UNIX_SOCKET" | grep -qi "socket\|unix"; then
        log_info "Root uses unix_socket authentication; creating a passworded 'root'@'localhost' user may be restricted."
        # Create a separate admin user with full privileges instead
        ADMIN_USER="admin_${DB_USER}"
        log_info "Creating local admin user: $ADMIN_USER"
        mysql -e "CREATE USER IF NOT EXISTS '${ADMIN_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO '${ADMIN_USER}'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null || \
            log_warn "Could not create local admin user; you may need to configure root authentication manually."
    else
        # Try ALTER USER first (modern MySQL/MariaDB)
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null
        if [ $? -ne 0 ]; then
            # Fallback to UPDATE for older systems, wrapped in a safe conditional
            log_info "ALTER USER failed; attempting compatible fallback to set root password..."
            mysql -e "UPDATE mysql.user SET authentication_string=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null || \
                mysql -e "UPDATE mysql.user SET Password=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null || \
                log_warn "Failed to set root password via fallback methods."
        fi
        # Remove anonymous users and test DB
        mysql -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
        mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');" 2>/dev/null || true
        mysql -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
        mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db LIKE 'test\\_%';" 2>/dev/null || true
        mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true
    fi
fi
set -e

# Create WordPress database and user
log_info "Creating WordPress database..."
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'"
mysql -e "FLUSH PRIVILEGES"

# Install WP-CLI
log_info "Installing WP-CLI..."
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# Create web directory
WEB_ROOT="/var/www/${DOMAIN}"
log_info "Creating web directory: $WEB_ROOT"
mkdir -p $WEB_ROOT
cd $WEB_ROOT

# Download WordPress
log_info "Downloading WordPress..."
wp core download --allow-root

# Configure wp-config.php
log_info "Configuring WordPress..."
wp config create \
    --dbname=$DB_NAME \
    --dbuser=$DB_USER \
    --dbpass=$DB_PASSWORD \
    --dbhost=localhost \
    --dbcharset=utf8mb4 \
    --allow-root

# Add security keys
wp config shuffle-salts --allow-root

# Add security constants
cat >> wp-config.php <<'EOF'

// Security configurations
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', false);
define('FORCE_SSL_ADMIN', true);
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);
define('WP_AUTO_UPDATE_CORE', 'minor');
EOF

# Set file permissions
log_info "Setting file permissions..."
chown -R www-data:www-data $WEB_ROOT
find $WEB_ROOT -type d -exec chmod 755 {} \;
find $WEB_ROOT -type f -exec chmod 644 {} \;
chmod 600 $WEB_ROOT/wp-config.php

# Copy NGINX configuration
log_info "Configuring NGINX..."
cat > /etc/nginx/sites-available/${DOMAIN} <<EOF
# HTTP - redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    
    # Redirect to HTTPS
    return 301 https://\$server_name\$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN} www.${DOMAIN};
    
    root ${WEB_ROOT};
    index index.php index.html index.htm;
    
    # SSL certificates (will be configured by Certbot)
    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
    
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
        
        # FastCGI cache
        fastcgi_cache_bypass \$skip_cache;
        fastcgi_no_cache \$skip_cache;
        fastcgi_cache_valid 200 60m;
    }
    
    # Deny access to PHP files in uploads
    location ~* /(?:uploads|files)/.*\.php$ {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test NGINX configuration
nginx -t

# Restart services
log_info "Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx

# Enable services on boot
systemctl enable nginx
systemctl enable php8.2-fpm
systemctl enable mariadb

# Configure UFW firewall
log_info "Configuring firewall..."
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# Configure fail2ban
log_info "Configuring fail2ban..."
cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-noscript]
enabled = true

[nginx-badbots]
enabled = true

[nginx-noproxy]
enabled = true
EOF

systemctl restart fail2ban
systemctl enable fail2ban

# Save credentials
CREDENTIALS_FILE="/root/retaguide-credentials.txt"
cat > $CREDENTIALS_FILE <<EOF
RetaGuide Server Credentials
=============================
Domain: ${DOMAIN}
Web Root: ${WEB_ROOT}

Database:
  Name: ${DB_NAME}
  User: ${DB_USER}
  Password: ${DB_PASSWORD}

WordPress Admin:
  User: ${WP_ADMIN_USER}
  Password: ${WP_ADMIN_PASSWORD}
  Email: ${WP_ADMIN_EMAIL}

Generated: $(date)

IMPORTANT: Store these credentials securely and delete this file!
EOF

chmod 600 $CREDENTIALS_FILE

log_info "========================================="
log_info "Provisioning complete!"
log_info "========================================="
log_info ""
log_info "Next steps:"
log_info "1. Configure DNS to point to this server's IP"
log_info "2. Run: sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
log_info "3. Complete WordPress installation at: https://${DOMAIN}"
log_info "4. Credentials saved to: $CREDENTIALS_FILE"
log_info ""
log_info "To install WordPress:"
log_info "  cd $WEB_ROOT"
log_info "  sudo wp core install --url=https://${DOMAIN} --title='RetaGuide' --admin_user=${WP_ADMIN_USER} --admin_password='${WP_ADMIN_PASSWORD}' --admin_email=${WP_ADMIN_EMAIL} --allow-root"
log_info ""
log_warn "Remember to delete $CREDENTIALS_FILE after saving credentials!"
