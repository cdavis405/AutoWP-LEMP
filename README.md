# RetaGuide - WordPress Site for Retatrutide Research

A comprehensive, production-ready WordPress site focused on experimental research peptide "Retatrutide," featuring custom News and Guides content types, integrated disclaimer system, and full DevOps deployment pipeline.

## 🎯 Features

### Content Management
- **News Posts**: Standard WordPress posts with Categories (Research, Safety, Regulatory, Market, Reviews) and free-form Tags
- **Guides Custom Post Type**: Structured guides with custom taxonomies (Guide Level, Guide Topic)
- **Disclaimer System**: Centrally managed, auto-prepended to all content with per-post overrides
- **Pinned Navigation**: Dynamic right-aligned nav items manageable via admin UI
- **Block Patterns**: Pre-built content patterns for News articles, Guides (Protocol, FAQ, Overview), and Callouts (Safety, Takeaways, Reading)

### Design & UX
- **Medical Theme**: Clean, professional design with medical color palette (light blues, teals, greens)
- **Fully Responsive**: Mobile-first design with accessible components
- **Block Theme**: Modern WordPress full site editing with theme.json
- **WCAG 2.1 AA**: Accessibility-focused with proper focus states, ARIA labels, and keyboard navigation

### SEO & Performance
- **SEO Optimized**: Open Graph, Twitter Cards, Schema.org markup (Article, HowTo, FAQ)
- **Breadcrumbs**: Structured breadcrumbs with schema.org markup
- **XML Sitemap**: Auto-generated WordPress sitemap
- **Performance**: Lazy loading, WebP support, FastCGI caching, optimized assets
- **Core Web Vitals**: Optimized for LCP, CLS, and FID metrics

### Security
- **Hardened wp-config.php**: Security constants and salts
- **MU Plugin**: Login attempt limiting, security headers, XML-RPC disabled
- **File Permissions**: Proper 755/644 permissions with 600 for wp-config.php
- **Firewall**: UFW configured for SSH, HTTP, HTTPS
- **Fail2ban**: Protection against brute force attacks

### DevOps
- **One-Command Setup**: Automated provisioning script for Ubuntu 22.04
- **NGINX + PHP-FPM**: High-performance LEMP stack with PHP 8.2
- **TLS/SSL**: Automated certificate setup with Certbot
- **GitHub Actions**: CI/CD pipeline with automated deployment and rollback
- **Backups**: Automated theme backups with retention policy

## 📋 Requirements

- Fresh Ubuntu 22.04 LTS (Azure VM or any VPS)
- Domain name pointed to server IP
- Root/sudo access
- Git installed

## 🚀 Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/retasite.git
cd retasite
```

### 2. Configure Environment

```bash
cd provision
cp .env.example .env
nano .env
```

Update the following variables:
- `DOMAIN`: Your domain name (e.g., retaguide.com)
- `DB_PASSWORD`: Secure database password
- `WP_ADMIN_USER`: WordPress admin username
- `WP_ADMIN_PASSWORD`: Secure admin password
- `WP_ADMIN_EMAIL`: Admin email address

### 3. Run Provisioning Script

```bash
sudo chmod +x provision.sh
sudo ./provision.sh
```

This script will:
- Update system packages
- Install NGINX, PHP 8.2, MariaDB
- Create WordPress database
- Configure NGINX with security headers
- Set up firewall (UFW) and fail2ban
- Download and configure WordPress
- Set proper file permissions

**Time**: ~10-15 minutes

### 4. Configure DNS

Point your domain's A record to your server's IP address:

```
Type: A
Name: @
Value: YOUR_SERVER_IP
TTL: 3600

Type: A
Name: www
Value: YOUR_SERVER_IP
TTL: 3600
```

Wait for DNS propagation (usually 5-30 minutes).

### 5. Install SSL Certificate

```bash
sudo certbot --nginx -d retaguide.com -d www.retaguide.com
```

Follow the prompts. Certbot will automatically configure NGINX for HTTPS.

### 6. Complete WordPress Installation

```bash
cd /var/www/retaguide.com
sudo wp core install \
  --url=https://retaguide.com \
  --title='RetaGuide' \
  --admin_user=YOUR_ADMIN_USER \
  --admin_password='YOUR_ADMIN_PASSWORD' \
  --admin_email=YOUR_EMAIL \
  --allow-root
```

### 7. Activate Theme

```bash
# Copy theme to WordPress
sudo cp -r /path/to/retasite/wp-content/themes/retaguide /var/www/retaguide.com/wp-content/themes/

# Copy MU plugins
sudo cp -r /path/to/retasite/wp-content/mu-plugins/* /var/www/retaguide.com/wp-content/mu-plugins/

# Set permissions
sudo ./set-permissions.sh /var/www/retaguide.com

# Activate theme via WP-CLI
cd /var/www/retaguide.com
sudo wp theme activate retaguide --allow-root

# Flush rewrite rules
sudo wp rewrite flush --allow-root
```

### 8. Access Your Site

Visit `https://retaguide.com/wp-admin` and log in with your admin credentials.

## 🎨 Theme Configuration

### Pinned Navigation

1. Go to **Appearance > Pinned Navigation**
2. Search for posts/pages to pin
3. Add to pinned list
4. Drag to reorder
5. Optionally set custom titles
6. Click **Save Changes**

### Global Disclaimer

1. Go to **Appearance > Legal & Disclaimer**
2. Enable global disclaimer
3. Edit disclaimer text (supports rich text)
4. Click **Save Changes**

Per-post override:
- Edit any post/guide
- Find "Disclaimer Settings" meta box (sidebar)
- Check "Hide global disclaimer" OR add custom disclaimer text

### Creating Content

#### News Post
1. **Posts > Add New**
2. Select Category (Research, Safety, etc.)
3. Add Tags
4. Use "Standard Article" pattern (Block Patterns panel)
5. Publish

#### Guide
1. **Guides > Add New**
2. Select Guide Level (Beginner, Protocol, Safety)
3. Select Guide Topic(s) (Mechanism, Dosing, etc.)
4. Fill "Guide Details" meta box (Last Reviewed, Version, Reading Time)
5. Use guide patterns (Protocol, FAQ, or Overview)
6. Publish

### Block Patterns

Access patterns from the Block Inserter (+):
- **News**: Standard Article
- **Guides**: Step-by-step Protocol, FAQ Guide, Overview Guide
- **Callouts**: Safety Notice, Key Takeaways, Further Reading

## 🔧 Deployment

### GitHub Actions Setup

1. Add secrets to your GitHub repository:
   - `SSH_PRIVATE_KEY`: Your SSH private key
   - `AZURE_VM_IP`: Server IP address
   - `AZURE_VM_USER`: SSH username (e.g., azureuser)

2. Push to main branch to trigger deployment:

```bash
git add .
git commit -m "Deploy changes"
git push origin main
```

The workflow will:
- Run PHP syntax checks
- Build theme assets
- Create backup on server
- Deploy files via rsync
- Set permissions
- Clear caches
- Verify deployment

### Manual Deployment

```bash
# From your local machine
rsync -avz --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  wp-content/themes/retaguide/ \
  user@retaguide.com:/var/www/retaguide.com/wp-content/themes/retaguide/

# SSH to server
ssh user@retaguide.com

# Set permissions
cd /var/www/retaguide.com
sudo chown -R www-data:www-data wp-content/themes/retaguide
sudo find wp-content/themes/retaguide -type d -exec chmod 755 {} \;
sudo find wp-content/themes/retaguide -type f -exec chmod 644 {} \;

# Clear cache
sudo wp cache flush --allow-root
sudo systemctl reload php8.2-fpm
```

### Rollback

#### Automated (GitHub Actions)
1. Go to **Actions** tab in GitHub
2. Select **Deploy RetaGuide** workflow
3. Click **Run workflow** > Select **rollback** job

#### Manual
```bash
ssh user@retaguide.com
cd /var/backups/retaguide
ls -lt theme-backup-*.tar.gz  # List backups
sudo tar -xzf theme-backup-TIMESTAMP.tar.gz -C /var/www/retaguide.com/wp-content/themes/
sudo chown -R www-data:www-data /var/www/retaguide.com/wp-content/themes/retaguide
sudo wp cache flush --path=/var/www/retaguide.com --allow-root
```

## 🛠️ Maintenance

### Update WordPress Core

```bash
cd /var/www/retaguide.com
sudo wp core update --allow-root
sudo wp core update-db --allow-root
```

### Update Plugins

```bash
sudo wp plugin update --all --allow-root
```

### Database Backup

Manual backup:
```bash
sudo wp db export /var/backups/retaguide/db-backup-$(date +%Y%m%d).sql --allow-root
gzip /var/backups/retaguide/db-backup-*.sql
```

Automated backups run daily via MU plugin.

### View Logs

```bash
# NGINX access log
sudo tail -f /var/log/nginx/retaguide.com_access.log

# NGINX error log
sudo tail -f /var/log/nginx/retaguide.com_error.log

# PHP-FPM log
sudo tail -f /var/log/php8.2-fpm.log

# WordPress debug log (if WP_DEBUG enabled)
tail -f /var/www/retaguide.com/wp-content/debug.log
```

### Renew SSL Certificate

Certbot auto-renews. Manual renewal:
```bash
sudo certbot renew
sudo systemctl reload nginx
```

## 📁 File Structure

```
retasite/
├── .github/
│   └── workflows/
│       └── deploy.yml              # CI/CD pipeline
├── provision/
│   ├── provision.sh                # Server provisioning script
│   ├── set-permissions.sh          # File permissions script
│   └── .env.example                # Environment configuration template
├── wp-content/
│   ├── mu-plugins/
│   │   └── retaguide-security.php  # Security MU plugin
│   └── themes/
│       └── retaguide/
│           ├── assets/
│           │   ├── css/
│           │   │   ├── custom.css
│           │   │   └── admin.css
│           │   └── js/
│           │       ├── main.js
│           │       └── pinned-nav-admin.js
│           ├── inc/
│           │   ├── block-patterns.php
│           │   ├── breadcrumbs.php
│           │   ├── custom-post-types.php
│           │   ├── disclaimer.php
│           │   ├── performance.php
│           │   ├── pinned-nav.php
│           │   ├── security.php
│           │   ├── seo.php
│           │   ├── taxonomies.php
│           │   └── theme-settings.php
│           ├── parts/
│           │   ├── header.html
│           │   └── footer.html
│           ├── templates/
│           │   ├── archive.html
│           │   ├── home.html
│           │   ├── index.html
│           │   ├── page.html
│           │   ├── single.html
│           │   └── single-guide.html
│           ├── functions.php
│           ├── style.css
│           └── theme.json
└── README.md
```

## 🔐 Security Checklist

- [x] Hardened wp-config.php with security constants
- [x] XML-RPC disabled
- [x] File editing disabled in admin
- [x] Login attempt limiting (5 attempts, 15-minute lockout)
- [x] Security headers (X-Frame-Options, CSP, etc.)
- [x] Fail2ban protection
- [x] UFW firewall (SSH, HTTP, HTTPS only)
- [x] Proper file permissions (755/644)
- [x] wp-config.php permissions (600)
- [x] SSL/TLS encryption
- [x] User enumeration disabled
- [x] Strong password requirements
- [x] Regular automated backups
- [x] Cookie consent notice (GDPR)

## 📊 Performance Optimizations

- [x] Lazy loading images
- [x] WebP image support
- [x] Responsive srcset
- [x] FastCGI caching
- [x] Gzip compression
- [x] Static asset caching (365 days)
- [x] Minimal JavaScript
- [x] Deferred non-critical JS
- [x] Preloaded critical CSS
- [x] Database query optimization
- [x] Emoji scripts removed
- [x] jQuery migrate removed
- [x] Limited post revisions (5)

## 🐛 Troubleshooting

### Site not loading
```bash
# Check NGINX status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Test NGINX configuration
sudo nginx -t

# Check error logs
sudo tail -n 50 /var/log/nginx/retaguide.com_error.log
```

### White screen of death
```bash
# Enable WordPress debug mode
sudo nano /var/www/retaguide.com/wp-config.php

# Add before "That's all":
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check debug log
tail -f /var/www/retaguide.com/wp-content/debug.log
```

### Database connection errors
```bash
# Check MariaDB status
sudo systemctl status mariadb

# Test database connection
mysql -u retaguide_user -p retaguide_wp

# Verify wp-config.php credentials
sudo cat /var/www/retaguide.com/wp-config.php | grep DB_
```

### MariaDB authentication issues during provisioning

**Issue**: `ERROR 1356 (HY000): View 'mysql.user' references invalid table(s)`

**Cause**: Modern MariaDB versions (10.4+) use different authentication methods and don't allow direct modification of the `mysql.user` view. Some installations use `unix_socket` authentication for root by default.

**Solution**: The provisioning script automatically handles this by:
1. Detecting the root authentication method
2. Using `ALTER USER` for modern MariaDB versions
3. Creating a separate admin user if unix_socket is detected
4. Falling back to compatible UPDATE methods if needed

**Manual fix** (if you need to set up authentication manually):
```bash
# Check current root authentication method
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User='root';"

# If root uses unix_socket, create an admin user instead:
sudo mysql -e "CREATE USER IF NOT EXISTS 'admin_retaguide'@'localhost' IDENTIFIED BY 'YourPassword'; GRANT ALL PRIVILEGES ON *.* TO 'admin_retaguide'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# If root uses mysql_native_password, change password with ALTER USER:
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'YourPassword'; FLUSH PRIVILEGES;"
```

**Testing**: You can test the MariaDB setup logic before running provisioning:
```bash
cd provision
./test-mariadb-setup.sh
```
This runs a Docker container with MariaDB and tests all authentication scenarios.

### Permission issues
```bash
cd /workspaces/Retasite/provision
sudo ./set-permissions.sh /var/www/retaguide.com
```

### SSL certificate issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew --dry-run

# Force renewal
sudo certbot renew --force-renewal
```

## 📞 Support

### Documentation
- WordPress Codex: https://codex.wordpress.org/
- Block Editor Handbook: https://developer.wordpress.org/block-editor/
- Theme Development: https://developer.wordpress.org/themes/

### Community
- WordPress Support Forums: https://wordpress.org/support/
- WordPress Stack Exchange: https://wordpress.stackexchange.com/

## 📝 License

This project is licensed under the GNU General Public License v2 or later.

## 👥 Credits

- Built with WordPress
- LEMP stack (Linux, NGINX, MariaDB, PHP)
- Certbot for SSL/TLS
- GitHub Actions for CI/CD

---

**Important**: This site contains information about Retatrutide, an experimental research peptide. All content includes appropriate medical disclaimers and is for educational purposes only.
