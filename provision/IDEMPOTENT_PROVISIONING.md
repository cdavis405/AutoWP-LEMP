# Idempotent Provisioning - Safe to Re-run

## What Changed

The `provision.sh` script is now **idempotent**, meaning:

- ✅ Safe to run multiple times
- ✅ Won't break existing installations
- ✅ Skips already-configured components
- ✅ Only updates what needs updating

## Checks Added

### 1. MariaDB Authentication Check (NEW)

```bash
# Before configuring MariaDB authentication
if root password works from .env:
    ✓ Skip authentication setup
    log "MariaDB already configured with password"
    Use existing password
elif admin user password works:
    ✓ Skip authentication setup  
    log "MariaDB already configured with admin user"
[INFO] ✓ NGINX config exists with SSL - skipping HTTP-only config
```

### Updating Configuration

Want to update just NGINX config after SSL is set up:

```bash
# Manually edit the config
sudo nano /etc/nginx/sites-available/yourdomain.com

# Test it
sudo nginx -t

# Reload
sudo systemctl reload nginx
```

The script won't overwrite your SSL config.

### Adding SSL After Initial Setup

1. Initial provision (HTTP only):

   ```bash
   sudo ./provision.sh
   ```

2. Add SSL with Certbot:

   ```bash
   sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
   ```

3. Re-run provision for updates (won't break SSL):

   ```bash
   sudo ./provision.sh
   # Will skip NGINX config since SSL is detected
   ```

## What Gets Updated on Re-run?

### Always Updated

- System packages (`apt-get update/upgrade`)
- PHP configuration files
- File permissions on WordPress directory
- Service restarts (NGINX, PHP-FPM)
- Firewall rules (ufw)
- fail2ban configuration

### Conditionally Updated

- WordPress files (only if missing)
- wp-config.php (only if missing)
- NGINX config (only if missing OR no SSL)
- WP-CLI (only if not installed)

### Never Overwritten

- Database (preserved)
- Database users (preserved)
- NGINX config with SSL (preserved)
- WordPress content (preserved)

## Safety Features

### Database Safety

```bash
CREATE DATABASE IF NOT EXISTS ...  # Won't fail if exists
CREATE USER IF NOT EXISTS ...       # Won't fail if exists
```

### File Safety

```bash
if [ -f "wp-config.php" ]; then
    skip creation  # Won't overwrite existing config
fi
```

### SSL Safety

```bash
if grep -q "ssl_certificate" config; then
    skip config  # Won't remove SSL certificates
fi
```

## Manual Overrides

### Force Database Recreate

```bash
# Drop existing database first
mysql -uroot -p -e "DROP DATABASE IF EXISTS autowp_wp;"

# Then run provision
sudo ./provision.sh
```

### Force WordPress Reinstall

```bash
# Remove WordPress files
sudo rm -rf /var/www/yourdomain.com/*

# Then run provision
sudo ./provision.sh
```

### Force NGINX Reconfigure

```bash
# Remove existing config
sudo rm /etc/nginx/sites-available/yourdomain.com
sudo rm /etc/nginx/sites-enabled/yourdomain.com

# Then run provision
sudo ./provision.sh
```

## Verification Commands

After re-running, verify everything:

```bash
# Check database exists
mysql -uautowp_user -p -e "SHOW DATABASES LIKE 'autowp%';"

# Check WordPress files
ls -la /var/www/yourdomain.com/wp-config.php

# Check NGINX config
sudo nginx -t

# Check services running
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mariadb

# Check site accessibility
curl -I http://yourdomain.com
# or
curl -I https://yourdomain.com  # after SSL
```

## Troubleshooting

### Script Says "Already Exists" But Something is Broken

**Database exists but corrupted:**

```bash
# Drop and recreate
mysql -uroot -p -e "DROP DATABASE autowp_wp;"
sudo ./provision.sh
```

**WordPress files exist but broken:**

```bash
# Remove and reinstall
sudo rm -rf /var/www/yourdomain.com/*
sudo ./provision.sh
```

**NGINX config exists but wrong:**

```bash
# Remove and recreate
sudo rm /etc/nginx/sites-available/yourdomain.com
sudo ./provision.sh
```

### Want Completely Fresh Start

Use the reset script first:

```bash
cd ~/autowp-lemp/provision
sudo ./test-mariadb-reset.sh
sudo rm -rf /var/www/yourdomain.com
sudo rm /etc/nginx/sites-available/yourdomain.com
sudo ./provision.sh
```

## Benefits

### Development

- Test configuration changes safely
- Roll back by re-running
- No fear of breaking things

### Production

- Update PHP/NGINX settings without data loss
- Apply security patches safely
- Recover from partial failures

### Maintenance

- Document infrastructure as code
- Consistent server setup across environments
- Easy disaster recovery

## Summary

✅ **Safe to re-run** - Won't break existing installations  
✅ **Smart detection** - Checks what's already configured  
✅ **Selective updates** - Only changes what needs changing  
✅ **SSL aware** - Never overwrites SSL configurations  
✅ **Data preservation** - Database and content protected  

You can now confidently run `sudo ./provision.sh` multiple times without worrying about breaking your site!
