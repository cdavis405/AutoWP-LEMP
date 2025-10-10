#!/bin/bash

#############################################################################
# Deploy RetaGuide Theme and MU Plugins
#############################################################################
# Purpose: Copy custom theme and MU plugins from repo to WordPress
# Usage: sudo ./deploy-theme.sh
# 
# This script can be run:
# - After WordPress is installed
# - To update theme/plugin files
# - Multiple times (idempotent)
#############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging functions
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
    log_error "This script must be run as root (use sudo)"
    exit 1
fi

# Load environment variables
if [ -f .env ]; then
    source .env
elif [ -f /root/retasite/provision/.env ]; then
    source /root/retasite/provision/.env
else
    log_error "Cannot find .env file. Please ensure it exists."
    exit 1
fi

# Verify DOMAIN is set
if [ -z "$DOMAIN" ]; then
    log_error "DOMAIN variable not set in .env file"
    exit 1
fi

WEB_ROOT="/var/www/${DOMAIN}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"

log_info "Deploying RetaGuide theme and plugins..."
log_info "Web Root: $WEB_ROOT"
log_info "Repo Root: $REPO_ROOT"

# Check if WordPress is installed
if [ ! -f "$WEB_ROOT/wp-load.php" ]; then
    log_error "WordPress not found at: $WEB_ROOT"
    log_error "Please run provision.sh first"
    exit 1
fi

# Create mu-plugins directory if it doesn't exist
log_info "Ensuring directories exist..."
mkdir -p $WEB_ROOT/wp-content/mu-plugins
mkdir -p $WEB_ROOT/wp-content/themes

# Deploy theme
if [ -d "$REPO_ROOT/wp-content/themes/retaguide" ]; then
    log_info "Deploying RetaGuide theme..."
    
    # Backup existing theme if it exists
    if [ -d "$WEB_ROOT/wp-content/themes/retaguide" ]; then
        BACKUP_DIR="$WEB_ROOT/wp-content/themes/retaguide.backup-$(date +%Y%m%d-%H%M%S)"
        log_info "Backing up existing theme to: $BACKUP_DIR"
        mv "$WEB_ROOT/wp-content/themes/retaguide" "$BACKUP_DIR"
    fi
    
    # Copy theme
    cp -r "$REPO_ROOT/wp-content/themes/retaguide" $WEB_ROOT/wp-content/themes/
    log_info "✓ Theme deployed"
else
    log_error "RetaGuide theme not found at: $REPO_ROOT/wp-content/themes/retaguide"
    exit 1
fi

# Deploy MU plugin
if [ -f "$REPO_ROOT/wp-content/mu-plugins/retaguide-security.php" ]; then
    log_info "Deploying MU security plugin..."
    
    # Backup existing plugin if it exists
    if [ -f "$WEB_ROOT/wp-content/mu-plugins/retaguide-security.php" ]; then
        BACKUP_FILE="$WEB_ROOT/wp-content/mu-plugins/retaguide-security.php.backup-$(date +%Y%m%d-%H%M%S)"
        log_info "Backing up existing plugin to: $BACKUP_FILE"
        mv "$WEB_ROOT/wp-content/mu-plugins/retaguide-security.php" "$BACKUP_FILE"
    fi
    
    # Copy plugin
    cp "$REPO_ROOT/wp-content/mu-plugins/retaguide-security.php" $WEB_ROOT/wp-content/mu-plugins/
    log_info "✓ MU plugin deployed"
else
    log_warn "MU security plugin not found at: $REPO_ROOT/wp-content/mu-plugins/retaguide-security.php"
fi

# Set proper permissions
log_info "Setting file permissions..."
chown -R www-data:www-data $WEB_ROOT/wp-content/themes/retaguide
chown -R www-data:www-data $WEB_ROOT/wp-content/mu-plugins
find $WEB_ROOT/wp-content/themes/retaguide -type d -exec chmod 755 {} \;
find $WEB_ROOT/wp-content/themes/retaguide -type f -exec chmod 644 {} \;
find $WEB_ROOT/wp-content/mu-plugins -type d -exec chmod 755 {} \;
find $WEB_ROOT/wp-content/mu-plugins -type f -exec chmod 644 {} \;
log_info "✓ Permissions set"

# Check if WordPress is installed (has tables)
cd $WEB_ROOT
if wp core is-installed --allow-root 2>/dev/null; then
    log_info ""
    log_info "WordPress is installed. Activating theme..."
    
    # Get current theme
    CURRENT_THEME=$(wp theme list --status=active --field=name --allow-root 2>/dev/null || echo "unknown")
    log_info "Current active theme: $CURRENT_THEME"
    
    if [ "$CURRENT_THEME" != "retaguide" ]; then
        # Activate theme
        if wp theme activate retaguide --allow-root 2>/dev/null; then
            log_info "✓ RetaGuide theme activated"
        else
            log_warn "Could not activate theme. You may need to activate it manually from WordPress admin."
        fi
    else
        log_info "✓ RetaGuide theme already active"
    fi
    
    # Flush rewrite rules
    log_info "Flushing rewrite rules..."
    wp rewrite flush --allow-root 2>/dev/null || log_warn "Could not flush rewrite rules"
    
    # Clear cache
    log_info "Clearing cache..."
    wp cache flush --allow-root 2>/dev/null || log_warn "Could not flush cache"
    
else
    log_warn "WordPress is not installed yet (no database tables)"
    log_warn "Theme deployed but not activated"
    log_info ""
    log_info "After installing WordPress, activate the theme with:"
    log_info "  cd $WEB_ROOT"
    log_info "  sudo -u www-data wp theme activate retaguide"
fi

log_info ""
log_info "========================================="
log_info "Deployment complete!"
log_info "========================================="
log_info ""
log_info "Theme location: $WEB_ROOT/wp-content/themes/retaguide"
log_info "MU plugin location: $WEB_ROOT/wp-content/mu-plugins/retaguide-security.php"
log_info ""
log_info "Next steps:"
log_info "1. Go to WordPress admin: https://www.${DOMAIN}/wp-admin"
log_info "2. Navigate to Appearance > Themes"
log_info "3. Activate 'RetaGuide' theme if not already active"
log_info "4. Check Plugins > Must-Use to verify security plugin loaded"
log_info ""
log_info "✓ Done!"

exit 0
