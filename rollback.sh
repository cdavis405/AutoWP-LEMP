#!/bin/bash

###############################################################################
# Rollback Script
# Restore previous theme version from backup
###############################################################################

set -e

# Load environment
if [ -f provision/.env ]; then
    source provision/.env
else
    echo "Error: .env file not found"
    exit 1
fi

REMOTE_USER="${AZURE_VM_USER:-azureuser}"
REMOTE_HOST="${AZURE_VM_IP}"
REMOTE_PATH="/var/www/${DOMAIN}"
BACKUP_DIR="/var/backups/retaguide"

echo "═══════════════════════════════════════════"
echo "  RetaGuide Rollback"
echo "═══════════════════════════════════════════"
echo ""

# List available backups
echo "Fetching available backups..."
ssh ${REMOTE_USER}@${REMOTE_HOST} << EOF
    sudo ls -lht ${BACKUP_DIR}/theme-backup-*.tar.gz | head -10
EOF

echo ""
read -p "Enter backup filename to restore (or 'latest' for most recent): " backup_choice

if [ "$backup_choice" = "latest" ]; then
    BACKUP_FILE="latest"
else
    BACKUP_FILE="$backup_choice"
fi

echo ""
read -p "Are you sure you want to rollback? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

# Perform rollback
echo "Rolling back..."
ssh ${REMOTE_USER}@${REMOTE_HOST} << EOF
    set -e
    
    if [ "$BACKUP_FILE" = "latest" ]; then
        BACKUP_PATH=\$(sudo ls -t ${BACKUP_DIR}/theme-backup-*.tar.gz | head -n 1)
    else
        BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILE}"
    fi
    
    if [ ! -f "\$BACKUP_PATH" ]; then
        echo "Error: Backup file not found: \$BACKUP_PATH"
        exit 1
    fi
    
    echo "Restoring from: \$BACKUP_PATH"
    
    # Create a backup of current state before rollback
    sudo tar -czf ${BACKUP_DIR}/pre-rollback-\$(date +%Y%m%d_%H%M%S).tar.gz -C ${REMOTE_PATH}/wp-content/themes retaguide
    
    # Extract backup
    sudo tar -xzf \$BACKUP_PATH -C ${REMOTE_PATH}/wp-content/themes/
    
    # Set permissions
    sudo chown -R www-data:www-data ${REMOTE_PATH}/wp-content/themes/retaguide
    
    # Clear cache
    sudo -u www-data wp cache flush --path=${REMOTE_PATH} --allow-root || true
    sudo systemctl reload php8.2-fpm
    
    echo "Rollback completed successfully"
EOF

# Verify site
echo ""
echo "Verifying site..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN})
if [ "$RESPONSE" = "200" ] || [ "$RESPONSE" = "301" ] || [ "$RESPONSE" = "302" ]; then
    echo "✓ Site is responding (HTTP $RESPONSE)"
else
    echo "✗ Warning: Site returned status $RESPONSE"
fi

echo ""
echo "═══════════════════════════════════════════"
echo "  Rollback Complete!"
echo "═══════════════════════════════════════════"
