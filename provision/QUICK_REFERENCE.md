# Quick Reference: Fixed MariaDB Setup in provision.sh

## ✅ What Was Fixed

### Issue 1: Syntax Error

```bash
# BEFORE (line 165): syntax error near unexpected token `fi'
        fi
        fi  # ← DUPLICATE!
```

**FIXED**: Removed duplicate `fi` statement

### Issue 2: MariaDB Access Denied

```bash
# BEFORE: Always used root without password after changing it
mysql -e "UPDATE mysql.user SET Password=..."  # ← Failed on modern MariaDB
mysql -e "CREATE DATABASE ..."  # ← Access denied!
```

**FIXED**:

- Use `ALTER USER` (modern method)
- Detect unix_socket authentication
- Set `MYSQL_CMD` variable with correct credentials

## 🚀 Quick Commands

### Test the Fix (Dry Run)

```bash
cd /workspaces/AutoWP-LEMP/provision
./test-mariadb-setup.sh
```

Expected output: "✓ All tests passed successfully!"

### Run Full Provisioning

```bash
cd /workspaces/AutoWP-LEMP/provision
cp .env.example .env
nano .env  # Edit your settings
sudo ./provision.sh
```

### Check Syntax Anytime

```bash
bash -n provision/provision.sh && echo "✓ OK"
```

## 🔍 Manual Verification on Your VM

### 1. Check Root Authentication Method

```bash
sudo mysql -e "SELECT User,Host,plugin FROM mysql.user WHERE User='root';"
```

Possible outputs:

- `mysql_native_password` → Script will use ALTER USER
- `unix_socket` or `auth_socket` → Script will create admin user

### 2. Test Database Connection After Provisioning

```bash
# With the WordPress user
mysql -u autowp_user -p -e "SHOW DATABASES;"

# Or with admin user (if unix_socket was detected)
mysql -u admin_autowp_user -p -e "SHOW DATABASES;"
```

### 3. Verify Database Was Created

```bash
mysql -u autowp_user -p autowp_wp -e "SHOW TABLES;"
```

## 📝 Key Logic Flow

```text
┌─────────────────────────────────────┐
│  Install MariaDB                    │
└──────────┬──────────────────────────┘
           │
           ▼
┌─────────────────────────────────────┐
│  Set MYSQL_CMD="mysql" (default)    │
└──────────┬──────────────────────────┘
           │
           ▼
┌─────────────────────────────────────┐
│  Detect root authentication plugin  │
└──────────┬──────────────────────────┘
           │
           ▼
    ┌──────┴───────┐
    │              │
    ▼              ▼
unix_socket?   mysql_native_password?
    │              │
    ▼              ▼
Create admin   ALTER USER root
user with      with new password
password       │
    │          ▼
    │      Success?
    │      │   │
    │   Yes│   │No → Try fallback
    │      │   │     UPDATE methods
    ▼      ▼   ▼
Set MYSQL_CMD with correct credentials
(admin user or root with password)
    │
    ▼
┌─────────────────────────────────────┐
│ Use $MYSQL_CMD for:                 │
│ - CREATE DATABASE autowp_wp         │
│ - CREATE USER autowp_user           │
│ - GRANT PRIVILEGES                  │
└─────────────────────────────────────┘
```

## 🛠️ Troubleshooting

### Still Getting Access Denied?

```bash
# Check what MYSQL_CMD would be set to
sudo mysql -N -s -e "SELECT plugin FROM mysql.user WHERE User='root' LIMIT 1;"

# If it says "unix_socket", the script will create admin_autowp_user
# Connect with:
mysql -u admin_autowp_user -p<your-db-password>

# If it says "mysql_native_password", the script will use root
# Connect with:
mysql -u root -p<your-db-password>
```

### Check Provisioning Logs

```bash
# During provisioning, you'll see:
[INFO] Configuring root user authentication...
[INFO] Creating local admin user: admin_autowp_user  # ← If unix_socket
# OR
[INFO] ✓ ALTER USER succeeded  # ← If mysql_native_password
```

### Verify Script Version

```bash
# Check if fix is present
grep -n "MYSQL_CMD" provision/provision.sh
# Should show line ~116 and ~145, ~157

# Check for duplicate fi issue
awk '/^[[:space:]]*fi[[:space:]]*$/ {c++; if(c==2 && NR-prev<5) print "Duplicate fi at line " NR " (previous at " prev ")"; prev=NR}' provision/provision.sh
# Should print nothing (no duplicates)
```

## 📦 Files Affected

- ✅ `provision/provision.sh` - Main provisioning script (FIXED)
- ✅ `provision/test-mariadb-setup.sh` - Dry-run test script (NEW)
- ✅ `provision/MARIADB_FIX.md` - Detailed fix documentation (NEW)
- ✅ `README.md` - Added troubleshooting section

## 🎯 What to Do Next

1. **Review the changes**:

   ```bash
   git diff provision/provision.sh
   ```

2. **Test locally** (if you have Docker):

   ```bash
   ./provision/test-mariadb-setup.sh
   ```

3. **Commit the fixes**:

   ```bash
   git add provision/provision.sh provision/test-mariadb-setup.sh provision/MARIADB_FIX.md README.md
   git commit -m "Fix MariaDB authentication issues in provision.sh

   - Fix duplicate fi syntax error
   - Use ALTER USER instead of direct UPDATE on mysql.user
   - Detect unix_socket auth and create admin user when needed
   - Set MYSQL_CMD variable for consistent credential usage
   - Add dry-run test script for validation
   - Update README with troubleshooting section"
   git push origin main
   ```

4. **Deploy to VM**: Run the updated `provision.sh` on a fresh Ubuntu 22.04 VM

## ✨ Success Indicators

When provisioning completes successfully, you'll see:

```text
[INFO] Securing MariaDB...
[INFO] Configuring root user authentication...
[INFO] Creating WordPress database...
[INFO] Creating web directory: /var/www/yourdomain.com
[INFO] Downloading WordPress...
...
[INFO] Provisioning complete!
```

No more "ERROR 1356" or "Access denied" errors! 🎉
