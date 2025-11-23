# WWW Subdomain Enforcement

This document explains how the AutoWP site enforces the www subdomain for all traffic.

## Overview

All traffic to `yourdomain.com` will redirect to `www.yourdomain.com`. This is configured at both the NGINX and WordPress levels.

## NGINX Configuration

### HTTP (Before SSL)

The provision script creates two server blocks:

```nginx
# Redirect non-www to www
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 http://www.yourdomain.com$request_uri;
}

# Main site (www)
server {
    listen 80;
    listen [::]:80;
    server_name www.yourdomain.com;
    # ... rest of configuration
}
```

### HTTPS (After SSL with Certbot)

When you run Certbot, it will automatically update the configuration:

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

**IMPORTANT**: After Certbot runs, you need to manually update the NGINX config to redirect non-www HTTPS to www HTTPS.

#### Post-Certbot Configuration

#### Option 1: Automated (Recommended)

Use the provided script to automatically configure NGINX:

```bash
cd ~/autowp-lemp/provision
sudo ./configure-nginx-https-www.sh
```

This script will:

- ✓ Backup your existing NGINX config
- ✓ Create proper HTTPS + WWW redirect configuration
- ✓ Test the configuration
- ✓ Reload NGINX
- ✓ Test all redirect scenarios

#### Option 2: Manual Configuration

Edit the NGINX config:

```bash
sudo nano /etc/nginx/sites-available/yourdomain.com
```

Certbot will have created HTTPS blocks. Update them to enforce www:

```nginx
# Redirect HTTP non-www to HTTPS www
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://www.yourdomain.com$request_uri;
}

# Redirect HTTP www to HTTPS www
server {
    listen 80;
    listen [::]:80;
    server_name www.yourdomain.com;
    return 301 https://www.yourdomain.com$request_uri;
}

# Redirect HTTPS non-www to HTTPS www
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    return 301 https://www.yourdomain.com$request_uri;
}

# Main HTTPS site (www)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    # ... rest of your WordPress configuration
}
```

Test and reload NGINX:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## WordPress Configuration

### During Installation

Install WordPress with the www subdomain:

```bash
cd /var/www/yourdomain.com
sudo -u www-data wp core install \
  --url=https://www.yourdomain.com \
  --title='AutoWP' \
  --admin_user=admin \
  --admin_password='your-password' \
  --admin_email=your-email@example.com
```

**Note**: Use `http://www.yourdomain.com` before SSL is configured, then `https://www.yourdomain.com` after.

### After Installation

WordPress will automatically use the URL you specified during installation. You can verify and update it:

```bash
# Check current URLs
sudo -u www-data wp option get siteurl
sudo -u www-data wp option get home

# Update if needed (after SSL is configured)
sudo -u www-data wp option update siteurl 'https://www.yourdomain.com'
sudo -u www-data wp option update home 'https://www.yourdomain.com'

# Enable HTTPS in wp-config.php if not already set
sudo -u www-data wp config set FORCE_SSL_ADMIN true --raw
```

## DNS Requirements

Ensure both records point to your server:

```text
A    @      YOUR_SERVER_IP    (yourdomain.com)
A    www    YOUR_SERVER_IP    (www.yourdomain.com)
```

Or use a CNAME:

```text
A       @      YOUR_SERVER_IP    (yourdomain.com)
CNAME   www    yourdomain.com     (www.yourdomain.com)
```

## Testing

After configuration, test all URL variations:

```bash
# All should redirect to https://www.yourdomain.com
curl -I http://yourdomain.com
curl -I http://www.yourdomain.com
curl -I https://yourdomain.com
curl -I https://www.yourdomain.com
```

Expected responses:

- `http://yourdomain.com` → 301 to `https://www.yourdomain.com`
- `http://www.yourdomain.com` → 301 to `https://www.yourdomain.com`
- `https://yourdomain.com` → 301 to `https://www.yourdomain.com`
- `https://www.yourdomain.com` → 200 OK (serves site)

## Troubleshooting

### WordPress Still Showing Non-WWW URLs

Check and update WordPress database:

```bash
sudo -u www-data wp search-replace 'http://yourdomain.com' 'https://www.yourdomain.com' --all-tables
sudo -u www-data wp search-replace 'https://yourdomain.com' 'https://www.yourdomain.com' --all-tables
```

### NGINX Not Redirecting

1. Check NGINX config syntax: `sudo nginx -t`
2. View active config: `cat /etc/nginx/sites-available/yourdomain.com`
3. Ensure symlink exists: `ls -la /etc/nginx/sites-enabled/yourdomain.com`
4. Reload NGINX: `sudo systemctl reload nginx`

### SSL Certificate Issues

Certbot creates certificates for both domains. If you get certificate errors:

```bash
# Renew certificate with both domains
sudo certbot certonly --nginx -d yourdomain.com -d www.yourdomain.com --force-renewal

# Update NGINX config to use the correct certificate paths
# Then reload
sudo systemctl reload nginx
```

## Summary

1. **DNS**: Both @ and www records point to server
2. **NGINX**: Redirects non-www to www at HTTP and HTTPS levels
3. **WordPress**: Installed with www URL and enforces it in database
4. **Result**: All traffic uses `www.yourdomain.com`
