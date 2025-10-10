# SSL Certificate Setup Guide

## What Changed

The provision.sh script now creates an **HTTP-only** NGINX configuration first. This avoids the error:
```
nginx: [emerg] cannot load certificate "/etc/letsencrypt/live/retaguide.com/fullchain.pem"
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
cd ~/retasite/provision
sudo ./provision.sh
```

You should see:
```
[INFO] Configuring NGINX...
[INFO] Restarting services...
[INFO] ✓ Provisioning complete!
[INFO] Site is now accessible at: http://retaguide.com
```

### 2. Verify DNS is Configured

Before running Certbot, ensure your domain points to the server:

```bash
# Check DNS resolution
dig +short retaguide.com
dig +short www.retaguide.com

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
curl -I http://retaguide.com

# Should return:
HTTP/1.1 200 OK
# or HTTP/1.1 302 if WordPress redirects
```

### 4. Run Certbot for SSL

```bash
sudo certbot --nginx -d retaguide.com -d www.retaguide.com
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
curl -I https://retaguide.com

# Should return:
HTTP/2 200
```

### 6. Install WordPress

```bash
cd /var/www/retaguide.com

# After SSL is configured, use https://
sudo wp core install \
  --url=https://retaguide.com \
  --title='RetaGuide' \
  --admin_user=admin \
  --admin_password='YourAdminPassword' \
  --admin_email=admin@retaguide.com \
  --allow-root
```

## NGINX Config Changes

### Before Certbot (HTTP only)
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name retaguide.com www.retaguide.com;
    
    root /var/www/retaguide.com;
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
    server_name retaguide.com www.retaguide.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name retaguide.com www.retaguide.com;
    
    ssl_certificate /etc/letsencrypt/live/retaguide.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/retaguide.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    root /var/www/retaguide.com;
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
dig +short retaguide.com

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
curl http://retaguide.com
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
sudo mkdir -p /etc/ssl/retaguide
sudo cp fullchain.pem /etc/ssl/retaguide/
sudo cp privkey.pem /etc/ssl/retaguide/
sudo chmod 600 /etc/ssl/retaguide/*
```

2. Edit NGINX config:
```bash
sudo nano /etc/nginx/sites-available/retaguide.com
```

Add HTTPS server block with your certificate paths.

But **Certbot is much easier** and handles auto-renewal!

## Summary

✅ **Fixed**: NGINX config now starts with HTTP only  
✅ **Fixed**: No more certificate file errors during provisioning  
✅ **Process**: HTTP first → Certbot → HTTPS  
✅ **Auto-renewal**: Certbot configures automatic certificate renewal  

The two-step approach (provision → Certbot) is the standard way to deploy with Let's Encrypt.
