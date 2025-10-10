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

# Default mysql command (will be adjusted after secure step)
# On Ubuntu, root may require sudo for unix_socket authentication
MYSQL_CMD="mysql"
MYSQL_ROOT_CMD="mysql"

# Check if we're running as root
if [ "$EUID" -eq 0 ]; then
    # Running as root, can use mysql directly (unix_socket will work)
    MYSQL_ROOT_CMD="mysql"
else
    # Not root, need sudo
    MYSQL_ROOT_CMD="sudo mysql"
fi

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
    # First, check if MariaDB is already configured with authentication
    log_info "Checking existing MariaDB authentication..."
    
    # Try connecting with password from .env
    MYSQL_WITH_PASSWORD="mysql -uroot -p'${DB_PASSWORD}'"
    eval "$MYSQL_WITH_PASSWORD -e 'SELECT 1'" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_info "✓ MariaDB already configured with password authentication"
        MYSQL_CMD="$MYSQL_WITH_PASSWORD"
        ALREADY_CONFIGURED=1
    else
        # Check if admin user exists and works
        ADMIN_USER="admin_${DB_USER}"
        MYSQL_WITH_ADMIN="mysql -u${ADMIN_USER} -p'${DB_PASSWORD}'"
        eval "$MYSQL_WITH_ADMIN -e 'SELECT 1'" >/dev/null 2>&1
        if [ $? -eq 0 ]; then
            log_info "✓ MariaDB already configured with admin user authentication"
            MYSQL_CMD="$MYSQL_WITH_ADMIN"
            ALREADY_CONFIGURED=1
        else
            ALREADY_CONFIGURED=0
        fi
    fi
    
    if [ "$ALREADY_CONFIGURED" -eq 1 ]; then
        log_info "✓ Skipping authentication setup - already configured"
    else
        # Attempt to set root password using ALTER USER if available
        log_info "Configuring root user authentication..."
        $MYSQL_ROOT_CMD -e "SELECT 1" >/dev/null 2>&1 || true

        # Try to detect if unix_socket auth is in use for root
        HAS_UNIX_SOCKET=$($MYSQL_ROOT_CMD -N -s -e "SELECT plugin FROM mysql.user WHERE User='root' LIMIT 1;" 2>/dev/null || echo "")
        if echo "$HAS_UNIX_SOCKET" | grep -qi "socket\|unix"; then
            log_info "Root uses unix_socket authentication; creating a passworded admin user."
            # Create a separate admin user with full privileges instead
            ADMIN_USER="admin_${DB_USER}"
            log_info "Creating local admin user: $ADMIN_USER"
            $MYSQL_ROOT_CMD -e "CREATE USER IF NOT EXISTS '${ADMIN_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO '${ADMIN_USER}'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null
            if [ $? -eq 0 ]; then
                log_info "✓ Created admin user successfully"
                MYSQL_CMD="mysql -u${ADMIN_USER} -p'${DB_PASSWORD}'"
            else
                log_warn "Could not create admin user; you may need to configure authentication manually."
                # Fall back to using root with unix_socket
                MYSQL_CMD="$MYSQL_ROOT_CMD"
            fi
        else
        # Try ALTER USER first (modern MySQL/MariaDB)
        $MYSQL_ROOT_CMD -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null
        ALTER_STATUS=$?
        
        if [ $ALTER_STATUS -ne 0 ]; then
            # Fallback to UPDATE for older systems, wrapped in a safe conditional
            log_info "ALTER USER failed; attempting compatible fallback to set root password..."
            $MYSQL_ROOT_CMD -e "UPDATE mysql.user SET authentication_string=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null
            UPDATE1_STATUS=$?
            
            if [ $UPDATE1_STATUS -ne 0 ]; then
                $MYSQL_ROOT_CMD -e "UPDATE mysql.user SET Password=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null
                UPDATE2_STATUS=$?
                
                if [ $UPDATE2_STATUS -ne 0 ]; then
                    log_warn "Failed to set root password via fallback methods."
                    log_info "Creating admin user as alternative..."
                    # Create admin user using root connection
                    $MYSQL_ROOT_CMD -e "CREATE USER IF NOT EXISTS 'admin_${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO 'admin_${DB_USER}'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null
                    if [ $? -eq 0 ]; then
                        log_info "✓ Created admin user successfully"
                        MYSQL_CMD="mysql -uadmin_${DB_USER} -p'${DB_PASSWORD}'"
                    else
                        log_warn "Could not create admin user - will use root connection"
                        MYSQL_CMD="$MYSQL_ROOT_CMD"
                    fi
                else
                    log_info "✓ Password set via UPDATE method"
                    MYSQL_CMD="mysql -uroot -p'${DB_PASSWORD}'"
                fi
            else
                log_info "✓ Password set via UPDATE method"
                MYSQL_CMD="mysql -uroot -p'${DB_PASSWORD}'"
            fi
        else
            # If ALTER USER succeeded, use root with password for subsequent DB commands
            log_info "✓ Password set via ALTER USER"
            MYSQL_CMD="mysql -uroot -p'${DB_PASSWORD}'"
        fi
        
            # Flush privileges if we changed password
            if [ "$MYSQL_CMD" != "mysql" ] && [ "$MYSQL_CMD" != "$MYSQL_ROOT_CMD" ]; then
                eval "$MYSQL_CMD -e \"FLUSH PRIVILEGES;\"" 2>/dev/null || true
            fi
            
            # Remove anonymous users and test DB
            eval "$MYSQL_CMD -e \"DELETE FROM mysql.user WHERE User='';\"" 2>/dev/null || true
            eval "$MYSQL_CMD -e \"DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');\"" 2>/dev/null || true
            eval "$MYSQL_CMD -e \"DROP DATABASE IF EXISTS test;\"" 2>/dev/null || true
            eval "$MYSQL_CMD -e \"DELETE FROM mysql.db WHERE Db='test' OR Db LIKE 'test\\\\_%';\"" 2>/dev/null || true
        fi  # End of unix_socket check
    fi  # End of ALREADY_CONFIGURED check
    
    # Clean up anonymous users and test DB (run even if already configured)
    if [ "$ALREADY_CONFIGURED" -eq 1 ]; then
        eval "$MYSQL_CMD -e \"DELETE FROM mysql.user WHERE User='';\"" 2>/dev/null || true
        eval "$MYSQL_CMD -e \"DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');\"" 2>/dev/null || true
        eval "$MYSQL_CMD -e \"DROP DATABASE IF EXISTS test;\"" 2>/dev/null || true
        eval "$MYSQL_CMD -e \"DELETE FROM mysql.db WHERE Db='test' OR Db LIKE 'test\\\\_%';\"" 2>/dev/null || true
    fi
fi
set -e

# Create WordPress database and user
log_info "Checking WordPress database..."

# Check if database already exists
set +e
DB_EXISTS=$(eval "$MYSQL_CMD -N -s -e \"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';\"" 2>/dev/null)
set -e

if [ -n "$DB_EXISTS" ]; then
    log_info "✓ Database '${DB_NAME}' already exists, skipping creation"
else
    log_info "Creating WordPress database..."
    eval "$MYSQL_CMD -e \"CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\""
    log_info "✓ Database created"
fi

# Check if user already exists
set +e
USER_EXISTS=$(eval "$MYSQL_CMD -N -s -e \"SELECT User FROM mysql.user WHERE User='${DB_USER}' AND Host='localhost';\"" 2>/dev/null)
set -e

if [ -n "$USER_EXISTS" ]; then
    log_info "✓ User '${DB_USER}' already exists, skipping creation"
else
    log_info "Creating WordPress user..."
    eval "$MYSQL_CMD -e \"CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'\""
    eval "$MYSQL_CMD -e \"GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'\""
    eval "$MYSQL_CMD -e \"FLUSH PRIVILEGES\""
    log_info "✓ User created and granted privileges"
fi

# Install WP-CLI
if ! command -v wp &> /dev/null; then
    log_info "Installing WP-CLI..."
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
    log_info "✓ WP-CLI installed"
else
    log_info "✓ WP-CLI already installed"
fi

# Create web directory
WEB_ROOT="/var/www/${DOMAIN}"
log_info "Checking WordPress installation..."
mkdir -p $WEB_ROOT
cd $WEB_ROOT

# Check if WordPress is already downloaded
if [ -f "wp-load.php" ] && [ -f "wp-config.php" ]; then
    log_info "✓ WordPress already installed at $WEB_ROOT"
    log_info "Skipping WordPress download and configuration"
elif [ -f "wp-load.php" ] && [ ! -f "wp-config.php" ]; then
    log_info "✓ WordPress files exist but not configured"
    log_info "Creating wp-config.php..."
    
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
    log_info "✓ WordPress configured"
else
    log_info "Downloading WordPress..."
    wp core download --allow-root
    log_info "✓ WordPress downloaded"
    
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
    log_info "✓ WordPress configured"
fi

# Set file permissions
log_info "Setting file permissions..."
chown -R www-data:www-data $WEB_ROOT
find $WEB_ROOT -type d -exec chmod 755 {} \;
find $WEB_ROOT -type f -exec chmod 644 {} \;
if [ -f "$WEB_ROOT/wp-config.php" ]; then
    chmod 600 $WEB_ROOT/wp-config.php
fi

# Copy NGINX configuration
log_info "Configuring NGINX..."

# Check if NGINX config already exists and has SSL configured
if [ -f "/etc/nginx/sites-available/${DOMAIN}" ]; then
    if grep -q "ssl_certificate" "/etc/nginx/sites-available/${DOMAIN}"; then
        log_info "✓ NGINX config exists with SSL - skipping HTTP-only config"
        log_warn "To reconfigure NGINX, manually edit: /etc/nginx/sites-available/${DOMAIN}"
    else
        log_warn "NGINX config exists but no SSL detected - will overwrite with HTTP config"
        cat > /etc/nginx/sites-available/${DOMAIN} <<EOF
# HTTP - Redirect non-www to www
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 http://www.${DOMAIN}\$request_uri;
}

# HTTP - Main site (www)
server {
    listen 80;
    listen [::]:80;
    server_name www.${DOMAIN};
    
    root ${WEB_ROOT};
    index index.php index.html index.htm;
    
    # Security headers (non-SSL)
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
        log_info "✓ HTTP config created"
    fi
else
    log_info "Creating new NGINX configuration..."
    cat > /etc/nginx/sites-available/${DOMAIN} <<EOF
# HTTP - Redirect non-www to www
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 http://www.${DOMAIN}\$request_uri;
}

# HTTP - Main site (www)
server {
    listen 80;
    listen [::]:80;
    server_name www.${DOMAIN};
    
    root ${WEB_ROOT};
    index index.php index.html index.htm;
    
    # Security headers (non-SSL)
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
    log_info "✓ NGINX config created"
fi

# Enable site
ln -sf /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test NGINX configuration
log_info "Testing NGINX configuration..."
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

# Ensure IPv6 is enabled in UFW
if grep -q "^IPV6=no" /etc/default/ufw 2>/dev/null; then
    log_info "Enabling IPv6 in UFW..."
    sed -i 's/^IPV6=no/IPV6=yes/' /etc/default/ufw
fi

ufw --force enable
ufw allow 22/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'

# Verify IPv6 is enabled
if grep -q "^IPV6=yes" /etc/default/ufw; then
    log_info "✓ UFW configured for both IPv4 and IPv6"
else
    log_warn "IPv6 may not be enabled in UFW - check /etc/default/ufw"
fi

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
log_info "IMPORTANT: Site is currently running on HTTP only."
log_info ""
log_info "Next steps:"
log_info "1. Ensure DNS is configured:"
log_info "   - ${DOMAIN} points to this server's IP"
log_info "   - www.${DOMAIN} points to this server's IP"
log_info ""
log_info "2. Set up SSL with Certbot:"
log_info "   sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
log_info "   (Certbot will automatically update NGINX config for HTTPS)"
log_info ""
log_info "3. Access your site:"
log_info "   - Before SSL: http://www.${DOMAIN}"
log_info "   - After SSL: https://www.${DOMAIN}"
log_info "   - Non-www URLs will redirect to www"
log_info ""
log_info "4. Install WordPress:"
log_info "   cd $WEB_ROOT"
log_info "   # Use http://www if SSL not configured yet, https://www after SSL"
log_info "   sudo -u www-data wp core install --url=http://www.${DOMAIN} --title='RetaGuide' --admin_user=${WP_ADMIN_USER} --admin_password='${WP_ADMIN_PASSWORD}' --admin_email=${WP_ADMIN_EMAIL}"
log_info ""
log_info "   Alternative (if you get permission errors):"
log_info "   sudo wp core install --url=http://www.${DOMAIN} --title='RetaGuide' --admin_user=${WP_ADMIN_USER} --admin_password='${WP_ADMIN_PASSWORD}' --admin_email=${WP_ADMIN_EMAIL} --allow-root"
log_info ""
log_info "5. Credentials saved to: $CREDENTIALS_FILE"
log_warn "   Remember to delete this file after saving credentials!"
log_info ""
log_info "Site is now accessible at: http://www.${DOMAIN}"
log_info "(Non-www URLs will automatically redirect to www)"
