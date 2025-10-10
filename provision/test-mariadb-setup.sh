#!/bin/bash
###############################################################################
# Test script for MariaDB setup logic from provision.sh
# Runs a temporary MariaDB container and tests the authentication setup
###############################################################################

set -e

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[TEST INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[TEST ERROR]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[TEST WARN]${NC} $1"
}

# Test parameters
DB_NAME="retaguide_wp"
DB_USER="retaguide_user"
DB_PASSWORD="TestDBPass123!"
CONTAINER_NAME="mariadb-provision-test"

log_info "Starting MariaDB dry-run test..."

# Clean up any existing test container
docker rm -f $CONTAINER_NAME >/dev/null 2>&1 || true

# Start MariaDB container
log_info "Starting temporary MariaDB 10.11 container..."
docker run --rm --name $CONTAINER_NAME \
    -e MARIADB_ROOT_PASSWORD=initialrootpass \
    -d mariadb:10.11

# Wait for MariaDB to be ready
log_info "Waiting for MariaDB to initialize..."
sleep 8

# Show initial state
log_info "Initial mysql.user state:"
docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass \
    -e "SELECT User,Host,plugin FROM mysql.user;"

# Test 1: Check if mysql client is available (mimics provision.sh check)
log_info "Test 1: Checking mysql client availability..."
docker exec $CONTAINER_NAME mysql --version

# Test 2: Detect authentication plugin for root
log_info "Test 2: Detecting root authentication plugin..."
HAS_UNIX_SOCKET=$(docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass \
    -N -s -e "SELECT plugin FROM mysql.user WHERE User='root' LIMIT 1;" 2>/dev/null || echo "")
echo "Root plugin: $HAS_UNIX_SOCKET"

if echo "$HAS_UNIX_SOCKET" | grep -qi "socket\|unix"; then
    log_info "Root uses unix_socket - would create admin user"
    ADMIN_USER="admin_${DB_USER}"
    docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass -e \
        "CREATE USER IF NOT EXISTS '${ADMIN_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}'; \
         GRANT ALL PRIVILEGES ON *.* TO '${ADMIN_USER}'@'localhost' WITH GRANT OPTION; \
         FLUSH PRIVILEGES;" || log_warn "Could not create admin user"
    MYSQL_CMD="mysql -u${ADMIN_USER} -p'${DB_PASSWORD}'"
else
    log_info "Root does not use unix_socket - testing ALTER USER..."
    
    # Test 3: Try ALTER USER
    docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass \
        -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_info "✓ ALTER USER succeeded"
        MYSQL_CMD="mysql -uroot -p'${DB_PASSWORD}'"
    else
        log_warn "ALTER USER failed - trying fallback UPDATE methods..."
        docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass \
            -e "UPDATE mysql.user SET authentication_string=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null || \
        docker exec $CONTAINER_NAME mysql -uroot -pinitialrootpass \
            -e "UPDATE mysql.user SET Password=PASSWORD('${DB_PASSWORD}') WHERE User='root' AND Host='localhost';" 2>/dev/null || \
        log_error "All fallback methods failed"
        MYSQL_CMD="mysql -uroot -p'${DB_PASSWORD}'"
    fi
    
    # Test 4: Remove anonymous users and test DB
    log_info "Test 4: Removing anonymous users and test database..."
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');" 2>/dev/null || true
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "FLUSH PRIVILEGES;" 2>/dev/null || true
fi

# Test 5: Create WordPress database and user with determined credentials
log_info "Test 5: Creating WordPress database and user..."
if echo "$MYSQL_CMD" | grep -q "admin_"; then
    # Using admin user
    ADMIN_USER="admin_${DB_USER}"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "FLUSH PRIVILEGES;"
else
    # Using root with new password
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "FLUSH PRIVILEGES;"
fi

# Test 6: Verify database and user were created
log_info "Test 6: Verifying database and user creation..."
if echo "$MYSQL_CMD" | grep -q "admin_"; then
    ADMIN_USER="admin_${DB_USER}"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "SHOW DATABASES LIKE 'retaguide%';"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "SELECT User,Host FROM mysql.user WHERE User='${DB_USER}';"
else
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "SHOW DATABASES LIKE 'retaguide%';"
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "SELECT User,Host FROM mysql.user WHERE User='${DB_USER}';"
fi

# Test 7: Verify the new WordPress user can connect
log_info "Test 7: Testing WordPress user connection..."
docker exec $CONTAINER_NAME mysql -u${DB_USER} -p${DB_PASSWORD} \
    -e "SHOW DATABASES;" && log_info "✓ WordPress user can connect successfully"

# Show final state
log_info "Final mysql.user state:"
if echo "$MYSQL_CMD" | grep -q "admin_"; then
    ADMIN_USER="admin_${DB_USER}"
    docker exec $CONTAINER_NAME mysql -u${ADMIN_USER} -p${DB_PASSWORD} \
        -e "SELECT User,Host,plugin FROM mysql.user;"
else
    docker exec $CONTAINER_NAME mysql -uroot -p${DB_PASSWORD} \
        -e "SELECT User,Host,plugin FROM mysql.user;"
fi

# Clean up
log_info "Cleaning up test container..."
docker stop $CONTAINER_NAME >/dev/null

log_info "========================================="
log_info "✓ All tests passed successfully!"
log_info "========================================="
log_info ""
log_info "Summary:"
log_info "- ALTER USER or fallback methods worked"
log_info "- Database '${DB_NAME}' created"
log_info "- User '${DB_USER}' created with proper privileges"
log_info "- User can connect and access databases"
log_info ""
log_info "The provision.sh MariaDB setup logic is working correctly."
