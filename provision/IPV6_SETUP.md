# IPv6 Configuration Guide

This guide explains how IPv6 connectivity is configured for the AutoWP site on Azure VM.

## Overview

Your Azure VM has both IPv4 and IPv6 connectivity. The provision script automatically configures:

- ✓ NGINX to listen on both IPv4 and IPv6
- ✓ UFW firewall to allow both IPv4 and IPv6 traffic
- ✓ All necessary ports (22, 80, 443) for both protocols

## Current Configuration

### NGINX (Already Configured)

The NGINX configuration includes IPv6 listen directives:

```nginx
# HTTP - Redirect non-www to www
server {
    listen 80;           # IPv4
    listen [::]:80;      # IPv6
    server_name yourdomain.com;
    return 301 http://www.yourdomain.com$request_uri;
}

# HTTP - Main site (www)
server {
    listen 80;           # IPv4
    listen [::]:80;      # IPv6
    server_name www.yourdomain.com;
    # ... rest of config
}
```

After SSL setup with Certbot:

```nginx
# HTTPS
server {
    listen 443 ssl http2;           # IPv4
    listen [::]:443 ssl http2;      # IPv6
    server_name www.yourdomain.com;
    # ... SSL and site config
}
```

### UFW Firewall (Already Configured)

UFW is configured to allow both IPv4 and IPv6 by default:

```bash
# /etc/default/ufw
IPV6=yes
```

The provision script ensures this setting and opens required ports:

```bash
ufw allow 22/tcp   # SSH (IPv4 + IPv6)
ufw allow 80/tcp   # HTTP (IPv4 + IPv6)
ufw allow 443/tcp  # HTTPS (IPv4 + IPv6)
```

## Verification Steps

### 1. Check Your IPv6 Address

```bash
# View network configuration
ip -6 addr show eth0

# Or using ifconfig
ifconfig eth0 | grep inet6
```

You should see something like:

```text
inet6 2603:1234:5678:9abc::1/128 scope global
```

### 2. Test IPv6 Connectivity

```bash
# Ping IPv6 Google DNS
ping6 -c 3 2001:4860:4860::8888

# Or ping an IPv6 website
ping6 -c 3 ipv6.google.com
```

### 3. Verify NGINX is Listening on IPv6

```bash
# Check listening ports
sudo ss -tlnp | grep nginx

# Should show both IPv4 and IPv6:
# 0.0.0.0:80    (IPv4)
# :::80         (IPv6)
# 0.0.0.0:443   (IPv4)
# :::443        (IPv6)
```

### 4. Verify UFW Allows IPv6

```bash
# Check UFW configuration
sudo cat /etc/default/ufw | grep IPV6
# Should show: IPV6=yes

# Check UFW rules
sudo ufw status verbose

# Check IPv6 rules specifically
sudo ip6tables -L -n -v
```

### 5. Test External IPv6 Access

From an external machine with IPv6 connectivity:

```bash
# Test HTTP (before SSL)
curl -6 -I http://www.yourdomain.com

# Test HTTPS (after SSL)
curl -6 -I https://www.yourdomain.com

# Force IPv6 with host header
curl -6 -I -H "Host: www.yourdomain.com" http://[YOUR_IPV6_ADDRESS]
```

## DNS Configuration for IPv6

To make your site accessible via IPv6, add AAAA records to your DNS:

### Option 1: Direct AAAA Records

```text
AAAA  @      YOUR_IPV6_ADDRESS    (yourdomain.com)
AAAA  www    YOUR_IPV6_ADDRESS    (www.yourdomain.com)
```

### Option 2: A + AAAA for @ and CNAME for www

```text
A       @      YOUR_IPV4_ADDRESS
AAAA    @      YOUR_IPV6_ADDRESS
CNAME   www    yourdomain.com
```

**Important**: If using CNAME for www, you must have both A and AAAA records on the root (@) domain.

### Verify DNS Propagation

```bash
# Check AAAA records
dig AAAA yourdomain.com
dig AAAA www.yourdomain.com

# Or using host
host -t AAAA www.yourdomain.com

# Check from external DNS
dig @8.8.8.8 AAAA www.yourdomain.com
```

## SSL Certificates with IPv6

Certbot automatically handles both IPv4 and IPv6:

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot will:

1. Validate domain ownership via both IPv4 and IPv6 (if AAAA records exist)
2. Configure NGINX with `listen [::]:443 ssl http2;`
3. Apply SSL to both protocols

## Azure VM IPv6 Configuration

### Check Azure Network Security Group (NSG)

Ensure your Azure NSG allows IPv6 traffic:

1. Go to Azure Portal → Your VM → Networking
2. Check Inbound Security Rules
3. Verify rules allow:
   - Port 22 (SSH) - Any source, Any protocol
   - Port 80 (HTTP) - Any source, TCP
   - Port 443 (HTTPS) - Any source, TCP

Azure NSG rules apply to both IPv4 and IPv6 by default when source/destination is set to "Any".

### Enable IPv6 in Azure (if not already enabled)

If you don't have an IPv6 address:

1. Azure Portal → Your Virtual Network
2. Settings → Address space
3. Add IPv6 address range (e.g., `2603:1234::/64`)
4. Subnets → Add IPv6 range to your subnet
5. Your VM → Networking → Network Interface
6. IP configurations → Add IPv6 configuration

## Troubleshooting

### Issue: NGINX Not Listening on IPv6

#### Check NGINX config syntax

```bash
sudo nginx -t
```

#### Verify listen directives include `::`

```bash
grep -n "listen" /etc/nginx/sites-available/yourdomain.com
```

#### Restart NGINX

```bash
sudo systemctl restart nginx
sudo ss -tlnp | grep nginx
```

### Issue: Cannot Connect via IPv6

#### Check if IPv6 is disabled system-wide

```bash
cat /proc/sys/net/ipv6/conf/all/disable_ipv6
# Should be 0 (enabled)
```

#### If disabled (1), enable it

```bash
sudo sysctl -w net.ipv6.conf.all.disable_ipv6=0
sudo sysctl -w net.ipv6.conf.default.disable_ipv6=0

# Make permanent
echo "net.ipv6.conf.all.disable_ipv6 = 0" | sudo tee -a /etc/sysctl.conf
echo "net.ipv6.conf.default.disable_ipv6 = 0" | sudo tee -a /etc/sysctl.conf
```

### Issue: UFW Blocking IPv6

#### Check UFW IPv6 setting

```bash
sudo cat /etc/default/ufw | grep IPV6
```

#### If IPV6=no, enable it

```bash
sudo sed -i 's/^IPV6=no/IPV6=yes/' /etc/default/ufw
sudo ufw disable
sudo ufw enable
```

#### Verify IPv6 rules

```bash
sudo ip6tables -L -n
```

### Issue: DNS Not Resolving via IPv6

#### Check AAAA records

```bash
dig AAAA www.yourdomain.com +short
```

#### If empty, add AAAA records in your DNS provider

#### Test direct IP access

```bash
# Replace with your actual IPv6 address
curl -6 -I http://[2603:xxxx:xxxx:xxxx::x]
```

### Issue: Certbot Fails with IPv6

If Certbot can't validate via IPv6:

#### Option 1: Temporarily disable IPv6 validation

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com --preferred-challenges http
```

#### Option 2: Use DNS validation instead

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com --preferred-challenges dns
```

## Testing IPv6 from Your Local Machine

### If You Have IPv6 Connectivity

```bash
# Test if your machine has IPv6
curl -6 https://ipv6.google.com

# Test your site
curl -6 -I https://www.yourdomain.com
```

### If You Don't Have IPv6 Connectivity

Use online IPv6 testing tools:

- <https://ipv6-test.com/validate.php>
- <https://www.whatismyip.com/ipv6-test/>
- <https://test-ipv6.com/>

Or use a proxy:

```bash
# Using an IPv6 proxy service
ssh -D 8080 user@ipv6-server
curl -x socks5h://localhost:8080 -6 http://www.yourdomain.com
```

## Performance Considerations

### Prefer IPv4 or IPv6?

Modern browsers prefer IPv6 when available (Happy Eyeballs - RFC 8305):

1. Browser tries IPv6 first
2. If IPv6 fails after ~300ms, falls back to IPv4
3. Fastest connection wins

### Monitoring Both Protocols

```bash
# Monitor connections by protocol
sudo ss -s

# See active connections
sudo ss -tn | grep :443  # IPv4 HTTPS
sudo ss -tn6 | grep :443 # IPv6 HTTPS
```

### NGINX Logging

NGINX logs both IPv4 and IPv6 addresses:

```bash
tail -f /var/log/nginx/yourdomain.com_access.log

# IPv4 example: 203.0.113.45 - - [...]
# IPv6 example: 2001:db8::1 - - [...]
```

## Security Considerations

### Firewall Rules Apply to Both

All UFW rules apply to both IPv4 and IPv6:

```bash
ufw allow 80/tcp    # Opens port 80 on IPv4 AND IPv6
```

### fail2ban with IPv6

The fail2ban configuration works with both protocols. It will ban abusive IPs regardless of protocol.

To verify:

```bash
sudo fail2ban-client status nginx-http-auth
# Shows both IPv4 and IPv6 bans
```

### Rate Limiting in NGINX

If you add rate limiting to NGINX, use appropriate zone sizes for IPv6:

```nginx
# IPv6 addresses are larger (128-bit vs 32-bit)
limit_req_zone $binary_remote_addr zone=one:10m rate=10r/s;
```

## Summary

✅ **NGINX**: Already configured with `listen [::]:80` and `listen [::]:443 ssl http2`  
✅ **UFW**: Automatically allows both IPv4 and IPv6 (IPV6=yes)  
✅ **Provision Script**: Now verifies and enables IPv6 in UFW  
✅ **Certbot**: Handles SSL for both protocols automatically  
✅ **Azure NSG**: Rules apply to both IPv4 and IPv6  

**Action Items**:

1. ✓ NGINX and UFW already configured (provision script handles it)
2. ✓ Verify IPv6 address exists: `ip -6 addr show eth0`
3. Add AAAA records to DNS (if not already done)
4. Test connectivity: `curl -6 https://www.yourdomain.com`

Your site will automatically serve traffic over both IPv4 and IPv6 once DNS AAAA records are configured!
