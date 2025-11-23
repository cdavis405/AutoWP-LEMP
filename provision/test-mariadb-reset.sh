#!/bin/bash
###############################################################################
# MariaDB Reset and Test Script for Ubuntu 22.04
# Use this to completely reset MariaDB and verify the fix works
###############################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    log_error "Please run with sudo: sudo ./test-mariadb-reset.sh"
    exit 1
fi

log_info "========================================="
log_info "MariaDB Reset and Test Script"
log_info "========================================="
echo ""

# Step 1: Check current state
log_step "1. Checking current MariaDB state..."
systemctl status mariadb --no-pager || true
echo ""

# Step 2: Stop MariaDB
log_step "2. Stopping MariaDB..."
systemctl stop mariadb || true
sleep 2

# Step 3: Backup current data (just in case)
log_step "3. Backing up current data..."
BACKUP_DIR="/root/mariadb-backup-$(date +%Y%m%d-%H%M%S)"
if [ -d /var/lib/mysql ]; then
    mkdir -p "$BACKUP_DIR"
    cp -r /var/lib/mysql "$BACKUP_DIR/" 2>/dev/null || true
    log_info "Backup saved to: $BACKUP_DIR"
fi
echo ""

# Step 4: Remove corrupted data
log_step "4. Removing MariaDB data directory..."
rm -rf /var/lib/mysql/*
log_info "✓ Data directory cleaned"
echo ""

# Step 5: Reinitialize MariaDB
log_step "5. Reinitializing MariaDB..."
mysql_install_db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
log_info "✓ MariaDB reinitialized"
echo ""

# Step 6: Start MariaDB
log_step "6. Starting MariaDB..."
systemctl start mariadb
sleep 3

# Check it started
if systemctl is-active --quiet mariadb; then
    log_info "✓ MariaDB is running"
else
    log_error "✗ MariaDB failed to start"
    journalctl -u mariadb --no-pager -n 50
    exit 1
fi
echo ""

# Step 7: Test root connection
log_step "7. Testing root connection..."
mysql -e "SELECT 1;" >/dev/null 2>&1
if [ $? -eq 0 ]; then
    log_info "✓ Root can connect without password (expected for fresh install)"
else
    log_error "✗ Root connection failed"
    exit 1
fi
echo ""

# Step 8: Check root authentication method
log_step "8. Checking root authentication method..."
ROOT_PLUGIN=$(mysql -N -s -e "SELECT plugin FROM mysql.user WHERE User='root' AND Host='localhost' LIMIT 1;")
log_info "Root authentication plugin: $ROOT_PLUGIN"
echo ""

# Step 9: Test the provision.sh logic
log_step "9. Testing admin user creation (provision.sh logic)..."

DB_PASSWORD="TestPassword123!"
DB_USER="autowp_user"

# Try to create admin user
mysql -e "CREATE USER IF NOT EXISTS 'admin_${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'; GRANT ALL PRIVILEGES ON *.* TO 'admin_${DB_USER}'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null

if [ $? -eq 0 ]; then
    log_info "✓ Admin user created successfully"
    
    # Test admin user connection
    mysql -uadmin_${DB_USER} -p"${DB_PASSWORD}" -e "SELECT 1;" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_info "✓ Admin user can connect"
    else
        log_error "✗ Admin user cannot connect"
        exit 1
    fi
else
    log_error "✗ Failed to create admin user"
    exit 1
fi
echo ""

# Step 10: Test database creation
log_step "10. Testing database creation..."
mysql -uadmin_${DB_USER} -p"${DB_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS test_db; SHOW DATABASES LIKE 'test_db';"
if [ $? -eq 0 ]; then
    log_info "✓ Database creation works"
    mysql -uadmin_${DB_USER} -p"${DB_PASSWORD}" -e "DROP DATABASE test_db;"
else
    log_error "✗ Database creation failed"
    exit 1
fi
echo ""

# Step 11: Clean up test admin user
log_step "11. Cleaning up test user..."
mysql -e "DROP USER IF EXISTS 'admin_${DB_USER}'@'localhost';" 2>/dev/null || true
log_info "✓ Test user removed"
echo ""

log_info "========================================="
log_info "✓ MariaDB Reset Complete!"
log_info "========================================="
echo ""
log_info "MariaDB has been reset to a fresh state."
log_info "Root can connect without password (passwordless/unix_socket)."
log_info "Admin user creation and database operations work correctly."
echo ""
log_info "Next steps:"
log_info "1. Run: cd ~/autowp-lemp/provision"
log_info "2. Run: sudo ./provision.sh"
echo ""
log_warn "Note: The backup of your old MariaDB data is at: $BACKUP_DIR"
log_warn "You can delete it if you don't need it: rm -rf $BACKUP_DIR"
