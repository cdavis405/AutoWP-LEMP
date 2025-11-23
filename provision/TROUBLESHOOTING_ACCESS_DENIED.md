# Troubleshooting: "Access denied for user 'root'@'localhost' (using password: NO)"

## Your Specific Error

```text
[INFO] Securing MariaDB...
[INFO] Configuring root user authentication...
[INFO] ALTER USER failed; attempting compatible fallback to set root password...
[WARN] Failed to set root password via fallback methods.
[INFO] Creating WordPress database...
ERROR 1045 (28000): Access denied for user 'root'@'localhost' (using password: NO)
```

## Root Cause

The script tried to:

1. Change root password with `ALTER USER` → Failed
2. Try UPDATE fallback methods → Failed
3. Use `mysql` (no credentials) to create database → Failed

This happens because after a fresh MariaDB install, the root user authentication is in transition and the script's MYSQL_CMD variable wasn't being updated when all password-setting methods failed.

## Fix Applied

Updated `provision.sh` to:

1. Check the status of each authentication method (ALTER USER, UPDATE methods)
2. If all fail, create an admin user instead of failing silently
3. Always set MYSQL_CMD to valid credentials before database creation
4. Use the determined MYSQL_CMD for all subsequent operations

## What to Do on Your VM

### Option 1: Pull Latest Changes and Re-run (Recommended)

```bash
# On your VM
cd ~/autowp-lemp
git pull --ff-only origin main

# Check current MariaDB state
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User='root';"

# If MariaDB is in a bad state, reset it:
sudo systemctl stop mariadb
sudo rm -rf /var/lib/mysql/*
sudo mysql_install_db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl start mariadb

# Now run the updated provision script
cd provision
sudo ./provision.sh
```

### Option 2: Manual Fix (If You Don't Want to Reset MariaDB)

```bash
# Check current authentication
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user;"

# Create an admin user manually
sudo mysql -e "CREATE USER IF NOT EXISTS 'admin_autowp'@'localhost' IDENTIFIED BY 'YourDBPassword'; GRANT ALL PRIVILEGES ON *.* TO 'admin_autowp'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Test the admin user
mysql -uadmin_autowp -pYourDBPassword -e "SHOW DATABASES;"

# Create WordPress database manually
mysql -uadmin_autowp -pYourDBPassword -e "CREATE DATABASE IF NOT EXISTS autowp_wp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create WordPress user
mysql -uadmin_autowp -pYourDBPassword -e "CREATE USER IF NOT EXISTS 'autowp_user'@'localhost' IDENTIFIED BY 'YourDBPassword'; GRANT ALL PRIVILEGES ON autowp_wp.* TO 'autowp_user'@'localhost'; FLUSH PRIVILEGES;"

# Verify
mysql -uautowp_user -pYourDBPassword autowp_wp -e "SHOW TABLES;"
```

### Option 3: Fresh Start (Cleanest)

```bash
# Completely remove MariaDB
sudo systemctl stop mariadb
sudo apt-get remove --purge mariadb-server mariadb-client -y
sudo apt-get autoremove -y
sudo rm -rf /var/lib/mysql
sudo rm -rf /etc/mysql

# Pull latest code
cd ~/autowp-lemp
git pull --ff-only origin main

# Run the updated provision script (it will reinstall MariaDB)
cd provision
sudo ./provision.sh
```

## Understanding the Fix

### Before (OLD CODE - BROKEN)

```bash
if ALTER USER fails; then
    try UPDATE methods
    if UPDATE fails; then
        log warning  # ← MYSQL_CMD stays as "mysql" (no password)
    fi
fi
# Later:
mysql -e "CREATE DATABASE..."  # ← Uses "mysql" without password → FAILS!
```

### After (NEW CODE - FIXED)

```bash
if ALTER USER fails; then
    try UPDATE method 1
    if that fails; then
        try UPDATE method 2
        if that fails; then
            # Create admin user as last resort
            CREATE USER 'admin_autowp'...
            if success; then
                MYSQL_CMD="mysql -uadmin_autowp -p'password'"  # ← Set valid credentials
            fi
        else
            MYSQL_CMD="mysql -uroot -p'password'"  # ← Set valid credentials
        fi
    else
        MYSQL_CMD="mysql -uroot -p'password'"  # ← Set valid credentials
    fi
fi
# Later:
$MYSQL_CMD -e "CREATE DATABASE..."  # ← Uses correct credentials → SUCCESS!
```

## Verification After Fix

After running the updated provision.sh, you should see:

### Success Scenario 1: ALTER USER works

```text
[INFO] Securing MariaDB...
[INFO] Configuring root user authentication...
[INFO] Creating WordPress database...
[INFO] Configuring NGINX...
```

### Success Scenario 2: Admin user created

```text
[INFO] Securing MariaDB...
[INFO] Configuring root user authentication...
[INFO] ALTER USER failed; attempting compatible fallback to set root password...
[WARN] Failed to set root password via fallback methods.
[INFO] Root may already use passwordless authentication - will attempt to use current credentials
[INFO] Created admin user as fallback
[INFO] Creating WordPress database...
[INFO] Configuring NGINX...
```

### What You Should NOT See

```text
ERROR 1045 (28000): Access denied for user 'root'@'localhost' (using password: NO)
```

## About Docker and test-mariadb-setup.sh

**Important**: Docker is NOT required for provisioning your actual server.

- `provision.sh` = Production script (installs real MariaDB, NO Docker needed)
- `test-mariadb-setup.sh` = Development testing tool (requires Docker)

You only needed Docker to test the script logic. You can uninstall it if you want:

```bash
sudo apt-get remove docker docker-engine docker.io containerd runc -y
```

## Next Steps

1. **Pull the latest changes**:

   ```bash
   cd ~/autowp-lemp
   git pull --ff-only origin main
   ```

2. **Check the fix is present**:

   ```bash
   grep -n "Created admin user as fallback" provision/provision.sh
   # Should show line ~168
   ```

3. **Choose your fix approach** (Option 1, 2, or 3 above)

4. **Run provision.sh** and verify success

5. **Complete WordPress setup**:

   ```bash
   cd /var/www/yourdomain.com
   sudo wp core install --url=https://yourdomain.com --title='AutoWP' --admin_user=admin --admin_password='YourAdminPass' --admin_email=admin@yourdomain.com --allow-root
   ```

## Still Having Issues?

Run these diagnostics and share the output:

```bash
# Check MariaDB version
mysql --version

# Check root authentication method
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User='root';"

# Try connecting as root without password
sudo mysql -e "SELECT 1;" && echo "✓ Root can connect without password"

# Check if admin user exists
sudo mysql -e "SELECT User,Host FROM mysql.user WHERE User LIKE 'admin%';"

# Show provision.sh version
grep -A5 "Created admin user as fallback" ~/autowp-lemp/provision/provision.sh
```

Share these outputs and I can provide specific guidance for your situation.
