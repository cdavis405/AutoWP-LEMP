# Theme and Plugin Troubleshooting

## Block Editor "Unexpected or Invalid Content" Warning

### What It Is

When editing templates in **Appearance > Editor**, you may see warnings like:
```
Block contains unexpected or invalid content
```

### Why It Happens

This is **usually safe to ignore** and occurs because:

1. **Theme is using custom CSS classes** - The block editor validates blocks against core WordPress patterns and flags custom classes
2. **Theme.json presets not fully loaded** - Sometimes spacing/color presets take a moment to register
3. **Block variations** - Custom block patterns may use valid but "unexpected" attributes

### How to Fix

**Option 1: Click "Attempt Block Recovery"**
- WordPress will try to match the block to a known pattern
- Usually works without losing content

**Option 2: Clear the warning**
- Click the `⋮` menu on the block
- Select "Clear customizations"
- Re-apply your changes

**Option 3: Ignore it (safe)**
- If the page displays correctly on the frontend
- The warning doesn't affect functionality
- It's just the editor being cautious

### Verify Your Theme is Working

Check these on the **frontend** (not in editor):

1. **Homepage displays correctly** - `https://www.yourdomain.com`
2. **Custom colors show** - Medical blue/teal theme colors visible
3. **Navigation works** - Header and footer menus function
4. **Custom post types** - Guides are available in admin

If the frontend looks good, the warnings are cosmetic!

### Common Causes in AutoWP Theme

The warnings appear on these blocks because they use custom classes:

```html
<!-- Custom site-header class -->
<div class="wp-block-group site-header has-white-background-color">

<!-- Custom spacing presets -->
style="padding-top:var(--wp--preset--spacing--40)"

<!-- Custom navigation structure -->
<!-- wp:navigation {"layout":{"type":"flex"}} -->
```

**These are all valid** - WordPress just doesn't recognize them immediately.

## MU Plugins Not Showing in Plugins Menu

### This is Normal!

Must-Use (MU) plugins **don't appear in the regular Plugins menu** by design.

### Where to Find MU Plugins

**In WordPress Admin:**

1. Go to **Plugins** in the sidebar
2. Look for a link that says **"Must-Use"** (usually above the plugins list)
3. Click it to see MU plugins

**Or check the URL directly:**
```
https://www.yourdomain.com/wp-admin/plugins.php?plugin_status=mustuse
```

### What You Should See

**Plugin Name:** AutoWP Security  
**Description:** Security hardening for AutoWP WordPress site  
**Version:** 1.0.0

### Features Provided by MU Plugin

The AutoWP Security plugin automatically:

✅ Disables file editing in admin (DISALLOW_FILE_EDIT)  
✅ Disables XML-RPC (prevents brute force)  
✅ Removes X-Pingback header  
✅ Limits REST API access for non-authenticated users  
✅ Adds security headers (X-Frame-Options, X-Content-Type-Options, etc.)  
✅ Limits login attempts (5 attempts, 15-minute lockout)  
✅ Removes WordPress version from feeds  
✅ Disables user enumeration  
✅ Hides login errors (prevents username discovery)

### Why MU Plugins?

**Must-Use plugins:**
- ✅ Load before regular plugins (priority)
- ✅ Cannot be accidentally disabled
- ✅ Perfect for security features
- ✅ Don't clutter the plugins menu
- ✅ Always active (no on/off switch)

### Verify MU Plugin is Working

**Test login limiting:**
1. Open incognito/private browser
2. Try to login with wrong password 5 times
3. You should be locked out for 15 minutes

**Test security headers:**
```bash
curl -I https://www.yourdomain.com
```

Look for:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
```

**Test XML-RPC disabled:**
```bash
curl -X POST https://www.yourdomain.com/xmlrpc.php
```

Should return 403 or similar error.

## wp-config.php Permission Warning

### The Warning

```
wp-config.php is writable. Set permissions to 600.
```

### Is It Serious?

**YES** - This is a legitimate security concern.

If `wp-config.php` has permissions like `644` or `664`, other users on the server could potentially read your database credentials.

### Fix It

**Option 1: Run the deploy script (fixes automatically)**
```bash
cd ~/autowp-lemp/provision
sudo ./deploy-theme.sh
```

**Option 2: Manual fix**
```bash
sudo chmod 600 /var/www/yourdomain.com/wp-config.php
sudo chown www-data:www-data /var/www/yourdomain.com/wp-config.php
```

**Option 3: Run permissions script**
```bash
cd ~/autowp-lemp/provision
sudo ./set-permissions.sh /var/www/yourdomain.com
```

### Verify Fix

```bash
ls -la /var/www/yourdomain.com/wp-config.php
```

Should show:
```
-rw------- 1 www-data www-data ... wp-config.php
```

The `-rw-------` (600) means:
- Owner (www-data) can read and write
- Group cannot access
- Others cannot access

### Why It Happens

The warning appears if:
1. File was created before permissions script ran
2. FTP/file manager changed permissions
3. Deployment didn't set permissions
4. wp-config.php was edited manually

### Correct Permissions Summary

**For security, always use:**

```bash
# WordPress root
chown -R www-data:www-data /var/www/yourdomain.com

# Directories
find /var/www/yourdomain.com -type d -exec chmod 755 {} \;

# Files
find /var/www/yourdomain.com -type f -exec chmod 644 {} \;

# wp-config.php (most important!)
chmod 600 /var/www/yourdomain.com/wp-config.php

# .htaccess (if exists)
chmod 644 /var/www/yourdomain.com/.htaccess

# Uploads (writable)
chmod 775 /var/www/yourdomain.com/wp-content/uploads
```

## Quick Fixes - Run These Now

```bash
# SSH to your server
ssh user@your-server

# Fix all three issues at once
cd ~/autowp-lemp/provision
git pull origin main
sudo ./deploy-theme.sh

# This will:
# ✓ Deploy latest theme fixes
# ✓ Set proper permissions (including wp-config.php)
# ✓ Activate theme
# ✓ Clear cache
```

After running, refresh WordPress admin and the wp-config warning should be gone!

## Theme Customization Tips

Once everything is working:

### Create Your First Guide

1. **Guides > Add New** in WordPress admin
2. Select **Guide Level**: Beginner, Intermediate, Advanced, Protocol, Safety
3. Select **Guide Topic**: Mechanism of Action, Dosing, Safety, etc.
4. Add content using blocks
5. Fill in **Guide Details** meta box (Last Reviewed, Version, Reading Time)
6. Publish!

### Use Block Patterns

1. Click `+` in block editor
2. Go to **Patterns** tab
3. Find **AutoWP** patterns:
   - Standard Article
   - Step-by-step Protocol
   - FAQ Guide
   - Overview Guide
   - Safety Notice
   - Key Takeaways
   - Further Reading

### Configure Disclaimer

1. Go to **Appearance > Legal & Disclaimer**
2. Enable global disclaimer
3. Edit the disclaimer text
4. All posts/guides will show it automatically

### Manage Pinned Navigation

1. Go to **Appearance > Pinned Navigation**
2. Search for posts/pages to pin
3. Drag to reorder
4. Optionally set custom titles
5. Items appear in right side of header

## Still Having Issues?

### Check PHP Error Logs

```bash
sudo tail -f /var/log/php8.2-fpm.log
```

### Check WordPress Debug Log

Enable debug mode in wp-config.php:
```bash
sudo nano /var/www/yourdomain.com/wp-config.php
```

Add before "That's all":
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

View logs:
```bash
tail -f /var/www/yourdomain.com/wp-content/debug.log
```

### Clear All Caches

```bash
cd /var/www/yourdomain.com
sudo -u www-data wp cache flush
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### Verify Theme Files Exist

```bash
ls -la /var/www/yourdomain.com/wp-content/themes/retaguide/
ls -la /var/www/yourdomain.com/wp-content/mu-plugins/
```

Should see:
- Theme: style.css, functions.php, theme.json, templates/, parts/, inc/, assets/
- MU: retaguide-security.php
