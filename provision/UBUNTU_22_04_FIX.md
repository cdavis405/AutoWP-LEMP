# Ubuntu 22.04 MariaDB 10.6 - unix_socket Authentication Fix

## The Issue on Ubuntu 22.04

On Ubuntu 22.04, MariaDB 10.6 is configured by default to use **unix_socket** authentication for the root user. This means:

- Root can ONLY connect when running as the system root user (or with `sudo`)
- Password authentication for root doesn't work by default
- Running `mysql` as a regular user (even with sudo) won't authenticate as MySQL root

## What Was Happening

Your provision.sh script was:

1. Running with `sudo` ✓ (script has root privileges)
2. BUT calling `mysql` commands without considering unix_socket ✗
3. `mysql -e "SELECT plugin..."` failed silently
4. Script thought root didn't use unix_socket
5. Tried ALTER USER → Failed (no connection)
6. Tried to create admin user → Failed (no connection)
7. Tried to create database → **Access denied** ✗

## The Fix

Updated provision.sh to use `MYSQL_ROOT_CMD` variable that:

- Uses `mysql` when script runs as root (EUID=0)
- Uses `sudo mysql` when script runs as non-root
- Properly detects unix_socket authentication
- Creates admin user using the root connection
- Falls back to root connection if admin user creation fails

### Key Changes

**Before:**

```bash
mysql -e "SELECT plugin FROM mysql.user..."  # ✗ Fails with unix_socket
```

**After:**

```bash
MYSQL_ROOT_CMD="mysql"  # We're running as root
$MYSQL_ROOT_CMD -e "SELECT plugin FROM mysql.user..."  # ✓ Works!
```

## How to Apply the Fix

### On Your VM

```bash
cd ~/autowp-lemp
git pull --ff-only origin main

# Verify the fix is present
grep -n "MYSQL_ROOT_CMD" provision/provision.sh
# Should show multiple lines (~118, ~123, ~133, etc.)

# Run the updated script
cd provision
sudo ./provision.sh
```

## Expected Output After Fix

### You should see

```text
[INFO] Securing MariaDB...
[INFO] Configuring root user authentication...
[INFO] Root uses unix_socket authentication; creating a passworded admin user.
[INFO] Creating local admin user: admin_autowp_user
[INFO] ✓ Created admin user successfully
[INFO] Creating WordPress database...
[INFO] Configuring NGINX...
...
[INFO] Provisioning complete!
```

### You should NOT see

```text
ERROR 1045 (28000): Access denied for user 'root'@'localhost' (using password: NO)
[WARN] Could not create admin user either - database creation may fail
```

## Understanding unix_socket Authentication

### Check Your Current Setup

```bash
# Check root authentication method
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User='root';"
```

**Typical Ubuntu 22.04 output:**

```
User    Host       plugin
root    localhost  unix_socket
```

This means root authenticates via the Unix socket - only the system root user can connect.

### Connecting to MariaDB on Ubuntu 22.04

```bash
# ✓ Works - using sudo (authenticates as system root)
sudo mysql

# ✗ Fails - no sudo (not authenticated as system root)
mysql

# ✓ Works - using the admin user created by provision.sh
mysql -uadmin_autowp_user -pYourPassword

# ✓ Works - using the WordPress user
mysql -uautowp_user -pYourPassword
```

## Why unix_socket is Actually Good

It's more secure because:

- Root can only be accessed from the system itself (no remote root access)
- No password to guess or brute force for root
- Requires local system root privileges to access MySQL root
- Regular database users (like your WordPress user) still use passwords

## After Provisioning

The script creates an `admin_autowp_user` which uses password authentication, so you can:

```bash
# Connect as admin user (has full privileges)
mysql -uadmin_autowp_user -p

# Or connect as WordPress user (limited to autowp_wp database)
mysql -uautowp_user -p autowp_wp
```

## Verifying the Fix Worked

After running the updated provision.sh:

```bash
# 1. Check that admin user was created
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User LIKE 'admin%';"

# Expected:
# admin_autowp_user    localhost    mysql_native_password

# 2. Test admin user connection
mysql -uadmin_autowp_user -p<your-db-password> -e "SHOW DATABASES;"

# Should show autowp_wp database

# 3. Test WordPress user connection
mysql -uautowp_user -p<your-db-password> -e "USE autowp_wp; SHOW TABLES;"

# Should connect successfully (may be empty if WordPress not installed yet)
```

## Troubleshooting

### Still Getting Access Denied?

```bash
# Check if the script ran as root
whoami  # Should show: root (when running with sudo)

# Check MariaDB is running
sudo systemctl status mariadb

# Try connecting as root with sudo
sudo mysql -e "SELECT 1;"
# Should work and return: 1

# If that works, manually create the admin user:
sudo mysql -e "CREATE USER 'admin_autowp_user'@'localhost' IDENTIFIED BY 'YourPassword'; GRANT ALL PRIVILEGES ON *.* TO 'admin_autowp_user'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Test it:
mysql -uadmin_autowp_user -pYourPassword -e "SHOW DATABASES;"
```

### Want to Change Root to Use Password Instead?

If you prefer password authentication for root (less secure, but more convenient):

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YourRootPassword'; FLUSH PRIVILEGES;"

# Now you can connect with:
mysql -uroot -pYourRootPassword
```

But I recommend keeping unix_socket and using the admin user for administrative tasks.

## Summary

✅ **Fixed**: Script now properly detects and handles unix_socket authentication  
✅ **Fixed**: Uses `MYSQL_ROOT_CMD` to run commands with proper privileges  
✅ **Fixed**: Creates admin user successfully  
✅ **Fixed**: Database creation works  

The provision.sh script is now fully compatible with Ubuntu 22.04's default MariaDB 10.6 configuration.
