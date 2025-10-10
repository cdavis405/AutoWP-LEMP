#!/bin/bash

###############################################################################
# Quick Deploy Script
# For manual deployments without GitHub Actions
###############################################################################

set -e

# Load environment
if [ -f provision/.env ]; then
    source provision/.env
else
    echo "Error: .env file not found in provision/ directory"
    exit 1
fi

# Configuration
REMOTE_USER="${AZURE_VM_USER:-azureuser}"
REMOTE_HOST="${AZURE_VM_IP}"
REMOTE_PATH="/var/www/${DOMAIN}"
LOCAL_THEME="wp-content/themes/retaguide"
LOCAL_MU_PLUGINS="wp-content/mu-plugins"

echo "═══════════════════════════════════════════"
echo "  RetaGuide Quick Deploy"
echo "═══════════════════════════════════════════"
echo "Remote: ${REMOTE_USER}@${REMOTE_HOST}"
echo "Path: ${REMOTE_PATH}"
echo ""

# Confirm deployment
read -p "Deploy to production? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Deployment cancelled."
    exit 0
fi

# Create backup on server
echo "Creating backup on server..."
ssh ${REMOTE_USER}@${REMOTE_HOST} << EOF
    sudo mkdir -p /var/backups/retaguide
    sudo tar -czf /var/backups/retaguide/theme-backup-\$(date +%Y%m%d_%H%M%S).tar.gz -C ${REMOTE_PATH}/wp-content/themes retaguide
    echo "Backup created successfully"
EOF

# Deploy theme
echo "Deploying theme files..."
rsync -avz --delete \
    --exclude '.git' \
    --exclude 'node_modules' \
    --exclude '.env' \
    --exclude '*.log' \
    --progress \
    ${LOCAL_THEME}/ \
    ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/wp-content/themes/retaguide/

# Deploy MU plugins
echo "Deploying MU plugins..."
rsync -avz \
    --progress \
    ${LOCAL_MU_PLUGINS}/ \
    ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/wp-content/mu-plugins/

# Set permissions and clear cache
echo "Setting permissions and clearing cache..."
ssh ${REMOTE_USER}@${REMOTE_HOST} << EOF
    sudo chown -R www-data:www-data ${REMOTE_PATH}/wp-content/themes/retaguide
    sudo chown -R www-data:www-data ${REMOTE_PATH}/wp-content/mu-plugins
    sudo find ${REMOTE_PATH}/wp-content/themes/retaguide -type d -exec chmod 755 {} \;
    sudo find ${REMOTE_PATH}/wp-content/themes/retaguide -type f -exec chmod 644 {} \;
    
    # Clear WordPress cache
    sudo -u www-data wp cache flush --path=${REMOTE_PATH} --allow-root || true
    
    # Flush rewrite rules
    sudo -u www-data wp rewrite flush --path=${REMOTE_PATH} --allow-root
    
    # Restart PHP-FPM
    sudo systemctl reload php8.2-fpm
    
    echo "Cache cleared and services restarted"
EOF

# Verify deployment
echo "Verifying deployment..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN})
if [ "$RESPONSE" = "200" ] || [ "$RESPONSE" = "301" ] || [ "$RESPONSE" = "302" ]; then
    echo "✓ Site is responding (HTTP $RESPONSE)"
else
    echo "✗ Warning: Site returned status $RESPONSE"
fi

echo ""
echo "═══════════════════════════════════════════"
echo "  Deployment Complete!"
echo "═══════════════════════════════════════════"
echo "Site: https://${DOMAIN}"
echo ""
echo "To rollback, run: ./rollback.sh"
