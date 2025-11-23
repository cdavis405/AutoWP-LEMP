# AutoWP Refactoring Walkthrough

## Overview

This walkthrough documents the refactoring of the "RetaGuide" WordPress auto-installer into the generic "AutoWP" installer. The goal was to remove all specific branding and domain references, making the project a truly generic starting point for any WordPress site.

## Changes Made

### 1. Branding & Domain Updates

- **Project Name**: Changed from "RetaGuide" to "AutoWP".
- **Domain**: Replaced `retaguide.com` with `yourdomain.com`.
- **Paths**: Updated `~/retasite` to `~/autowp-lemp`.
- **Database**:
  - `retaguide_wp` → `autowp_wp`
  - `retaguide_user` → `autowp_user`
  - `admin_retaguide` → `admin_autowp`

### 2. File Renaming

- **Theme**: `wp-content/themes/retaguide` → `wp-content/themes/autowp-theme`
- **MU Plugin**: `wp-content/mu-plugins/retaguide-security.php` → `wp-content/mu-plugins/autowp-security.php`

### 3. Code Refactoring

- **Theme Functions**: Updated prefixes from `retaguide_` to `autowp_`.
- **Security Plugin**: Updated class names and log prefixes.
- **Provisioning Scripts**: Updated all paths, variables, and log messages in `provision/`.
- **Deployment Scripts**: Updated `deploy.sh` and `rollback.sh` to use new paths and names.

### 4. Documentation Updates

- **README.md**: Updated title, description, and instructions.
- **CONTRIBUTING.md**: Updated contribution guidelines.
- **Provisioning Docs**: Updated all guides in `provision/` (`SSL_SETUP_GUIDE.md`, `WWW_ENFORCEMENT.md`, etc.) to use generic terms.
- **Lint Fixes**: Fixed markdown lint errors (headers, code blocks, lists) in documentation.

## Verification

### Syntax Checks

- **Shell Scripts**: Verified syntax of `provision.sh`, `deploy.sh`, `rollback.sh`, and helper scripts.
- **PHP Files**: Verified syntax of theme files and MU plugins.

### Manual Verification Steps

1. **Provisioning**:
   - Run `provision/test-mariadb-setup.sh` (dry run) to verify database logic.
   - Run `provision/provision.sh` on a fresh VM.
   - Verify NGINX config, database creation, and WordPress installation.

2. **Deployment**:
   - Run `deploy.sh` to verify rsync paths and remote commands.
   - Run `rollback.sh` to verify backup restoration.

3. **Documentation**:
   - Reviewed all `.md` files for correct generic references.

## Next Steps

1. **Test on Fresh VM**: Execute the provisioning script on a clean Ubuntu 22.04 server.
2. **Verify WordPress**: Log in to the new WordPress site and check theme activation.
3. **Check Security**: Verify that `autowp-security.php` is active and logging correctly.
