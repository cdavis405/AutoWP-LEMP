# SSL Certificate Setup Guide

## What Changed

The provision.sh script now creates an **HTTP-only** NGINX configuration first. This avoids the error:

```text
nginx: [emerg] cannot load certificate "/etc/letsencrypt/live/yourdomain.com/fullchain.pem"
```

## Why HTTP First?

SSL certificates don't exist until you run Certbot. The old config tried to reference certificates that didn't exist yet, causing NGINX to fail. The new approach:

1. ✓ Create HTTP-only config
2. ✓ Start NGINX successfully
3. ✓ Run Certbot to get certificates
4. ✓ Certbot automatically updates NGINX config for HTTPS

## Setup Steps

### 1. Complete Provisioning (HTTP)

```bash
cd ~/autowp-lemp/provision
sudo ./provision.sh
```

You should see:

```text
[INFO] Configuring NGINX...
[INFO] Restarting services...
[INFO] ✓ Provisioning complete!
[INFO] Site is now accessible at: http://yourdomain.com
```

### 2. Verify DNS is Configured

Before running Certbot, ensure your domain points to the server:

```bash
# Check DNS resolution
dig +short yourdomain.com
dig +short www.yourdomain.com

# Should show your server's IP address
```

If not configured:

- Go to your domain registrar (Namecheap, GoDaddy, etc.)
- Add/update A records:
  - `@` (or root) → Your server IP
  - `www` → Your server IP

### 3. Test HTTP Access

```bash
# From your local machine or the server:
curl -I http://yourdomain.com

# Should return:
HTTP/1.1 200 OK
# or HTTP/1.1 302 if WordPress redirects
```

### 4. Run Certbot for SSL

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

**Certbot will:**

1. Verify domain ownership (via HTTP challenge)
2. Request certificates from Let's Encrypt
3. Automatically update your NGINX config
4. Add HTTPS server block
5. Add HTTP→HTTPS redirect
6. Set up auto-renewal

**Interactive prompts:**

- Email address: Enter your email (for expiration notices)
- Terms of service: Agree (yes)
- Share email with EFF: Your choice (optional)
- Redirect HTTP to HTTPS: Choose 2 (Redirect)

### 5. Verify HTTPS Works

```bash
# Test HTTPS
curl -I https://yourdomain.com

# Should return:
HTTP/2 200
```

### 6. Configure WWW Redirect (Post-Certbot)

After Certbot runs, manually configure NGINX to redirect non-www to www:

```bash
sudo nano /etc/nginx/sites-available/yourdomain.com
```

Update the server blocks to enforce www (see `WWW_ENFORCEMENT.md` for details):

- Redirect HTTP non-www to HTTPS www
- Redirect HTTPS non-www to HTTPS www
- Serve site on HTTPS www

Test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Install WordPress

```bash
cd /var/www/yourdomain.com

# After SSL is configured, use https://www (enforces www subdomain)
sudo -u www-data wp core install \
  --url=https://www.yourdomain.com \
  --title='AutoWP' \
  --admin_user=admin \
  --admin_password='YourAdminPassword' \
  --admin_email=admin@yourdomain.com
```

## NGINX Config Changes

### Before Certbot (HTTP only)

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/yourdomain.com;
    index index.php index.html;
    
    # PHP and WordPress configuration...
}
```

### After Certbot (HTTPS + HTTP redirect)

```nginx
# HTTP - Redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    root /var/www/yourdomain.com;
    # ... rest of config
}
```

Certbot adds all the SSL directives automatically!

## Certificate Renewal

Certbot sets up automatic renewal. Verify it works:

```bash
# Test renewal (dry run)
sudo certbot renew --dry-run

# Should show:
Congratulations, all simulated renewals succeeded
```

Certificates auto-renew via cron/systemd timer before they expire (90 days).

## Troubleshooting

### Certbot Fails: "Could not connect to server"

**Problem**: DNS not configured or firewall blocking port 80

**Fix**:

```bash
# Check DNS
dig +short yourdomain.com

# Check firewall
sudo ufw status
# Should show: 80/tcp ALLOW

# Check NGINX is running
sudo systemctl status nginx
```

### Certbot Fails: "Invalid response from http://..."

**Problem**: NGINX not serving files correctly

**Fix**:

```bash
# Check NGINX config
sudo nginx -t

# Check site is enabled
ls -la /etc/nginx/sites-enabled/

# Restart NGINX
sudo systemctl restart nginx

# Test HTTP access
curl http://yourdomain.com
```

### Certificate Renewal Fails

**Check renewal log**:

```bash
sudo tail -50 /var/log/letsencrypt/letsencrypt.log
```

**Manually renew**:

```bash
sudo certbot renew --force-renewal
```

## Alternative: Manual SSL (Not Recommended)

If you have your own SSL certificates:

1. Copy certificates to server:

   ```bash
   sudo mkdir -p /etc/ssl/yourdomain
   sudo cp fullchain.pem /etc/ssl/yourdomain/
   sudo cp privkey.pem /etc/ssl/yourdomain/
   sudo chmod 600 /etc/ssl/yourdomain/*
   ```

2. Edit NGINX config:

```bash
sudo nano /etc/nginx/sites-available/yourdomain.com
```

Add HTTPS server block with your certificate paths.

But **Certbot is much easier** and handles auto-renewal!

## Summary

✅ **Fixed**: NGINX config now starts with HTTP only  
✅ **Fixed**: No more certificate file errors during provisioning  
✅ **Process**: HTTP first → Certbot → Configure www redirect → Install WordPress with www  
✅ **Auto-renewal**: Certbot configures automatic certificate renewal  
✅ **WWW Enforcement**: All traffic redirects to <www.yourdomain.com>

The two-step approach (provision → Certbot) is the standard way to deploy with Let's Encrypt.

**See also**: `WWW_ENFORCEMENT.md` for complete www subdomain configuration details.
