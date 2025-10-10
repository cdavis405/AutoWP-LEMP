#!/bin/bash

###############################################################################
# File Permissions Script
# Sets correct permissions for WordPress files and directories
###############################################################################

set -e

# Colors
GREEN='\033[0;32m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root"
    exit 1
fi

# Get web root from argument or use default
WEB_ROOT="${1:-/var/www/retaguide.com}"

if [ ! -d "$WEB_ROOT" ]; then
    echo "Error: Directory $WEB_ROOT does not exist"
    exit 1
fi

log_info "Setting permissions for: $WEB_ROOT"

# Set ownership
log_info "Setting ownership to www-data:www-data..."
chown -R www-data:www-data "$WEB_ROOT"

# Set directory permissions
log_info "Setting directory permissions to 755..."
find "$WEB_ROOT" -type d -exec chmod 755 {} \;

# Set file permissions
log_info "Setting file permissions to 644..."
find "$WEB_ROOT" -type f -exec chmod 644 {} \;

# Set stricter permissions for sensitive files
log_info "Setting stricter permissions for sensitive files..."
if [ -f "$WEB_ROOT/wp-config.php" ]; then
    chmod 600 "$WEB_ROOT/wp-config.php"
fi

if [ -d "$WEB_ROOT/.git" ]; then
    chmod 700 "$WEB_ROOT/.git"
fi

# Set write permissions for uploads directory
if [ -d "$WEB_ROOT/wp-content/uploads" ]; then
    log_info "Setting write permissions for uploads directory..."
    chmod 775 "$WEB_ROOT/wp-content/uploads"
fi

# Set write permissions for cache directories
if [ -d "$WEB_ROOT/wp-content/cache" ]; then
    log_info "Setting write permissions for cache directory..."
    chmod 775 "$WEB_ROOT/wp-content/cache"
fi

log_info "Permissions set successfully!"
log_info "Summary:"
log_info "  Owner: www-data:www-data"
log_info "  Directories: 755"
log_info "  Files: 644"
log_info "  wp-config.php: 600"
