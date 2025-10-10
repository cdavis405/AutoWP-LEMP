#!/usr/bin/env bash
set -euo pipefail

# Retaguide LEMP provisioning script for Ubuntu 22.04
# Usage: sudo ./provision.sh example.com admin@example.com

DOMAIN=${1:-retaguide.com}
EMAIL=${2:-admin@${DOMAIN}}
DB_NAME=${DB_NAME:-retaguide}
DB_USER=${DB_USER:-retaguide}
DB_PASS=${DB_PASS:-$(openssl rand -base64 20)}
WEB_ROOT=${WEB_ROOT:-/var/www/retaguide}
PHP_VERSION=${PHP_VERSION:-8.2}

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run this script with sudo or as root." >&2
  exit 1
fi

echo "Updating apt repositories..."
apt-get update
apt-get install -y software-properties-common curl git unzip ufw fail2ban

add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y nginx php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-zip \
  php${PHP_VERSION}-gd php${PHP_VERSION}-intl mariadb-server certbot python3-certbot-nginx

systemctl enable nginx
systemctl enable php${PHP_VERSION}-fpm
systemctl enable mariadb

mysql_secure_installation <<EOF
n
y
y
y
y
EOF

mysql -uroot <<MYSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL

echo "Database user ${DB_USER} with random password: ${DB_PASS}" > /root/retaguide-db.txt
chmod 600 /root/retaguide-db.txt

echo "Configuring UFW..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

echo "Preparing directory structure..."
mkdir -p ${WEB_ROOT}
chown -R www-data:www-data ${WEB_ROOT}
chmod -R 750 ${WEB_ROOT}

cat <<NGINX >/etc/nginx/sites-available/${DOMAIN}
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${WEB_ROOT};
    index index.php index.html index.htm;

    include /etc/nginx/snippets/ssl-${DOMAIN}.conf;
    include /etc/nginx/snippets/ssl-params.conf;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    access_log /var/log/nginx/${DOMAIN}.access.log;
    error_log /var/log/nginx/${DOMAIN}.error.log;

    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|svg|webp|ico)$ {
        expires 30d;
        access_log off;
    }

    location = /robots.txt { allow all; log_not_found off; }
    location = /favicon.ico { log_not_found off; }

    location ~* /wp-json/|/xmlrpc.php { deny all; }
}
NGINX

ln -sf /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/${DOMAIN}

if [[ ! -f /etc/nginx/snippets/ssl-params.conf ]]; then
cat <<'SSL' >/etc/nginx/snippets/ssl-params.conf
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers on;
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:10m;
ssl_session_tickets off;
ssl_stapling on;
ssl_stapling_verify on;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
SSL
fi

systemctl reload nginx

echo "Requesting Let's Encrypt certificate..."
certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} --non-interactive --agree-tos -m ${EMAIL}

cat <<CRON >/etc/cron.d/retaguide-certbot
0 3 * * * root certbot renew --quiet && systemctl reload nginx
CRON

echo "Downloading WordPress core..."
sudo -u www-data curl -o /tmp/latest.tar.gz https://wordpress.org/latest.tar.gz
sudo -u www-data tar -xzf /tmp/latest.tar.gz -C /tmp
sudo -u www-data rsync -a /tmp/wordpress/ ${WEB_ROOT}/
rm -rf /tmp/wordpress /tmp/latest.tar.gz

cat <<WP >${WEB_ROOT}/wp-config.php
<?php
/** WordPress base configuration for Retaguide */
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         '$(openssl rand -base64 64)' );
define( 'SECURE_AUTH_KEY',  '$(openssl rand -base64 64)' );
define( 'LOGGED_IN_KEY',    '$(openssl rand -base64 64)' );
define( 'NONCE_KEY',        '$(openssl rand -base64 64)' );
define( 'AUTH_SALT',        '$(openssl rand -base64 64)' );
define( 'SECURE_AUTH_SALT', '$(openssl rand -base64 64)' );
define( 'LOGGED_IN_SALT',   '$(openssl rand -base64 64)' );
define( 'NONCE_SALT',       '$(openssl rand -base64 64)' );

$table_prefix = 'rtg_';

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_MODS', true );
define( 'FORCE_SSL_ADMIN', true );

define( 'WP_MEMORY_LIMIT', '256M' );

define( 'FS_METHOD', 'direct' );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
WP

chown -R www-data:www-data ${WEB_ROOT}
find ${WEB_ROOT} -type d -exec chmod 755 {} \;
find ${WEB_ROOT} -type f -exec chmod 644 {} \;

systemctl reload php${PHP_VERSION}-fpm
systemctl reload nginx

echo "Provisioning complete. Visit https://${DOMAIN} to finish the WordPress installer."