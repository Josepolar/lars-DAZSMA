# LARS System - LAMP VM Integration Guide for openSUSE Leap 16

## Integration Guide for Adding LARS to Your Existing Flippie VM Setup

**Prerequisites:**
- Completed Flippie LAMP VM setup (see `LAMP_VM_SETUP_GUIDE.md`)
- LAMP stack already running (Apache, MariaDB, PHP 8.x)
- openSUSE Leap 16 VM accessible from Windows host

---

## Table of Contents

1. [Overview](#1-overview)
2. [Create LARS Database](#2-create-lars-database)
3. [Deploy LARS Application Files](#3-deploy-lars-application-files)
4. [Update Database Configuration](#4-update-database-configuration)
5. [Configure Apache Virtual Host](#5-configure-apache-virtual-host)
6. [SSL/HTTPS Configuration](#6-sslhttps-configuration)
7. [Set File Permissions](#7-set-file-permissions)
8. [Update Windows Hosts File](#8-update-windows-hosts-file)
9. [Testing](#9-testing)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Overview

LARS is a Learning Activity Recording System with:
- Student, Teacher, Staff, and Admin portals
- Activity management (quizzes, assignments, games)
- Matching games and typing games
- Profile image uploads

**Database:** `lars`
**Recommended Local URL:** `https://lars.local`

---

## 2. Create LARS Database

### 2.1 Login to MariaDB

```bash
sudo mysql -u root -p
```

### 2.2 Create Database and User

Run these SQL commands:

```sql
-- Create the database
CREATE DATABASE lars CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create a dedicated user for LARS
CREATE USER 'lars_user'@'localhost' IDENTIFIED BY 'Lars@VM00123';

-- Grant privileges
GRANT ALL PRIVILEGES ON lars.* TO 'lars_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### 2.3 Import Database

The SQL file is already in your VM at `/home/zhonmanaois/Documents/u456758764_lars.sql`.

**Import the database:**

```bash
mysql -u lars_user -p lars < /home/zhonmanaois/Documents/u456758764_lars.sql
```

When prompted, enter: `Lars@VM00123`

### 2.4 Verify Database Import

```bash
mysql -u lars_user -p

# Inside MySQL
USE lars;
SHOW TABLES;
SELECT COUNT(*) FROM users;
EXIT;
```

You should see tables like: `activities`, `users`, `subjects`, `activity_questions`, etc.

---

## 3. Deploy LARS Application Files

### 3.1 Copy from Home Directory (Recommended)

The LARS project is already in your VM at `/home/zhonmanaois/Documents/larss`.

```bash
# Create web directory (if it doesn't exist)
sudo mkdir -p /srv/www/htdocs/lars

# (Optional) Clear any previous contents to avoid duplicates
sudo rm -rf /srv/www/htdocs/lars/*

# Copy LARS from your home directory to the web root
sudo cp -r /home/zhonmanaois/Documents/larss/* /srv/www/htdocs/lars/
```

### 3.2 Alternative: Using VirtualBox Shared Folders

If you prefer to use shared folders:

```bash
# Create web directory
sudo mkdir -p /srv/www/htdocs/lars

# Mount shared folder (if not auto-mounted)
sudo mount -t vboxsf larss /mnt/larss

# Copy files
sudo cp -r /mnt/larss/* /srv/www/htdocs/lars/
```

### 3.3 Alternative: Using Git (if repository exists)

```bash
cd /srv/www/htdocs
sudo git clone https://github.com/your-repo/lars.git lars
```

---

## 4. Update Database Configuration

The LARS system uses a simple PHP database connection file. We need to update it to support the VM environment.

### 4.1 Edit Database Configuration

```bash
sudo nano /srv/www/htdocs/lars/Database/database.php
```

Replace the entire contents with:

```php
<?php
// Database connection - supports Hostinger live server, VM, and local XAMPP

// Detect environment
$is_vm = (file_exists('/srv/www/htdocs/lars') || php_uname('s') === 'Linux');
$is_localhost = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' 
                || ($_SERVER['HTTP_HOST'] ?? '') === 'lars.local'
                || ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1';

// Live server credentials (Hostinger)
$live_servername = "localhost";
$live_username = "u456758764_lars";
$live_password = "Lars@DB00123";
$live_dbname = "u456758764_lars";

// VM/Local credentials
$local_servername = "localhost";
$local_username = "lars_user";
$local_password = "Lars@VM00123";
$local_dbname = "lars";

// Legacy XAMPP credentials (fallback)
$xampp_username = "root";
$xampp_password = "";

try {
    if ($is_vm || $is_localhost) {
        // Try VM/Local connection first
        try {
            $pdo = new PDO(
                "mysql:host=$local_servername;dbname=$local_dbname;charset=utf8mb4",
                $local_username,
                $local_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            // Fallback to XAMPP root user
            $pdo = new PDO(
                "mysql:host=$local_servername;dbname=$local_dbname;charset=utf8mb4",
                $xampp_username,
                $xampp_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        }
    } else {
        // Live server connection
        $pdo = new PDO(
            "mysql:host=$live_servername;dbname=$live_dbname;charset=utf8mb4",
            $live_username,
            $live_password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
} catch(PDOException $e) {
    error_log("LARS Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}
?>
```

---

## 5. Configure Apache Virtual Host

### 5.1 Create LARS Virtual Host

```bash
sudo nano /etc/apache2/vhosts.d/lars.conf
```

Add this configuration:

```apache
<VirtualHost *:80>
    ServerName lars.local
    ServerAlias www.lars.local
    DocumentRoot /srv/www/htdocs/lars
    
    <Directory /srv/www/htdocs/lars>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog /var/log/apache2/lars_error.log
    CustomLog /var/log/apache2/lars_access.log combined
    
    # PHP settings
    <IfModule mod_php.c>
        php_value upload_max_filesize 40M
        php_value post_max_size 45M
        php_value max_execution_time 300
        php_value max_input_time 300
        php_value memory_limit 256M
    </IfModule>
</VirtualHost>
```

---

## 6. SSL/HTTPS Configuration

### 6.1 Generate SSL Certificate for LARS

```bash
# Generate self-signed certificate for lars.local
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/lars.key \
    -out /etc/apache2/ssl/lars.crt
```

**When prompted, enter:**
```
Country Name (2 letter code) [AU]: PH
State or Province Name: Metro Manila
Locality Name: Manila
Organization Name: LARS
Organizational Unit Name: IT
Common Name: lars.local
Email Address: admin@lars.local
```

### 6.2 Create HTTPS Virtual Host

```bash
sudo nano /etc/apache2/vhosts.d/lars-ssl.conf
```

Add this configuration:

```apache
<VirtualHost *:443>
    ServerName lars.local
    ServerAlias www.lars.local
    DocumentRoot /srv/www/htdocs/lars
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/apache2/ssl/lars.crt
    SSLCertificateKeyFile /etc/apache2/ssl/lars.key
    
    # Modern SSL settings
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder off
    
    <Directory /srv/www/htdocs/lars>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    
    # Logging
    ErrorLog /var/log/apache2/lars_ssl_error.log
    CustomLog /var/log/apache2/lars_ssl_access.log combined
    
    # PHP settings
    <IfModule mod_php.c>
        php_value upload_max_filesize 40M
        php_value post_max_size 45M
        php_value max_execution_time 300
        php_value max_input_time 300
        php_value memory_limit 256M
    </IfModule>
</VirtualHost>
```

### 6.3 Update HTTP Virtual Host to Redirect to HTTPS

Replace the content in `/etc/apache2/vhosts.d/lars.conf`:

```bash
sudo nano /etc/apache2/vhosts.d/lars.conf
```

```apache
# Redirect HTTP to HTTPS for LARS
<VirtualHost *:80>
    ServerName lars.local
    ServerAlias www.lars.local
    Redirect permanent / https://lars.local/
</VirtualHost>
```

### 6.4 Test and Restart Apache

```bash
# Test Apache configuration
sudo apachectl configtest

# Restart Apache
sudo systemctl restart apache2
```

---

## 7. Set File Permissions

```bash
# Set ownership to Apache user
sudo chown -R wwwrun:www /srv/www/htdocs/lars

# Set directory permissions
sudo find /srv/www/htdocs/lars -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /srv/www/htdocs/lars -type f -exec chmod 644 {} \;

# Make upload directories writable
sudo chmod -R 775 /srv/www/htdocs/lars/uploads
sudo mkdir -p /srv/www/htdocs/lars/uploads/matching_games
sudo mkdir -p /srv/www/htdocs/lars/uploads/profile_images
sudo chown -R wwwrun:www /srv/www/htdocs/lars/uploads

# Make assets directory writable (if needed for subject images)
sudo chmod -R 775 /srv/www/htdocs/lars/assets
```

---

## 8. Update Windows Hosts File

### 8.1 Edit Hosts File on Windows

Open PowerShell as Administrator:

```powershell
notepad C:\Windows\System32\drivers\etc\hosts
```

Add this line (use your VM's IP address):

```
192.168.100.48    lars.local www.lars.local
```

Your hosts file should now have both entries:

```
192.168.100.48    flippie.local www.flippie.local
192.168.100.48    lars.local www.lars.local
```

### 8.2 Flush DNS Cache (Optional)

```powershell
ipconfig /flushdns
```

---

## 9. Testing

### 9.1 Test from VM Terminal

```bash
# Test database connection
mysql -u lars_user -p -e "USE lars; SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'lars';"

# Test Apache is serving LARS
curl -k https://lars.local/
```

### 9.2 Test from Windows Browser

Open your browser and navigate to:

- **Homepage:** `https://lars.local`
- **Student Login:** `https://lars.local/students/stud-login.php`
- **Admin Login:** `https://lars.local/admin/admin-login.php`
- **Staff Login:** `https://lars.local/staff/staff-login.php`
- **Teacher Portal:** `https://lars.local/teachers/`

**Note:** Your browser will show a security warning about the self-signed certificate. Click "Advanced" → "Proceed to lars.local (unsafe)" to continue.

### 9.3 Test Database Connection

Create a test file:

```bash
sudo nano /srv/www/htdocs/lars/test_db.php
```

Add:

```php
<?php
require_once 'Database/database.php';

echo "<h2>LARS Database Connection Test</h2>";
echo "<p>Connection: <strong style='color:green'>SUCCESS</strong></p>";

try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo "<p>Users in database: <strong>" . $row['count'] . "</strong></p>";
    
    $tables = $pdo->query("SHOW TABLES");
    echo "<h3>Tables:</h3><ul>";
    while ($table = $tables->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
```

Visit `https://lars.local/test_db.php` to verify database connection.

**Delete after testing:**

```bash
sudo rm /srv/www/htdocs/lars/test_db.php
```

---

## 10. Troubleshooting

### 10.1 Database Connection Issues

```bash
# Check if MariaDB is running
sudo systemctl status mariadb

# Test user login
mysql -u lars_user -p

# Check database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'lars';"

# Check user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'lars_user'@'localhost';"
```

### 10.2 Apache Issues

```bash
# Check Apache configuration
sudo apachectl configtest

# Check Apache error logs
sudo tail -f /var/log/apache2/lars_error.log
sudo tail -f /var/log/apache2/lars_ssl_error.log

# Check virtual hosts are loaded
sudo apachectl -S
```

### 10.3 Permission Issues

```bash
# Check file ownership
ls -la /srv/www/htdocs/lars/

# Fix ownership if needed
sudo chown -R wwwrun:www /srv/www/htdocs/lars

# Check upload directory
ls -la /srv/www/htdocs/lars/uploads/
```

### 10.4 SSL Certificate Issues

```bash
# Verify certificate files exist
ls -la /etc/apache2/ssl/lars.*

# Check certificate details
openssl x509 -in /etc/apache2/ssl/lars.crt -text -noout | head -20
```

### 10.5 Can't Access from Windows

1. **Ping the VM:**
   ```powershell
   ping 192.168.100.48
   ```

2. **Check hosts file entry:**
   ```powershell
   type C:\Windows\System32\drivers\etc\hosts | findstr lars
   ```

3. **Test with IP directly:**
   `https://192.168.100.48/` (may show wrong site if using name-based vhosts)

4. **Check VM firewall:**
   ```bash
   sudo firewall-cmd --list-all
   ```

---

## Quick Reference Commands

```bash
# Apache
sudo systemctl restart apache2
sudo systemctl status apache2
sudo apachectl configtest

# MariaDB
sudo systemctl status mariadb
mysql -u lars_user -p lars

# View logs
sudo tail -f /var/log/apache2/lars_error.log
sudo tail -f /var/log/apache2/lars_ssl_error.log

# Permissions fix
sudo chown -R wwwrun:www /srv/www/htdocs/lars
sudo chmod -R 775 /srv/www/htdocs/lars/uploads
```

---

## Summary Checklist

After completing all steps, verify:

- [ ] MariaDB has `lars` database with all tables imported
- [ ] LARS files deployed to `/srv/www/htdocs/lars`
- [ ] Database configuration updated in `Database/database.php`
- [ ] Apache virtual hosts configured (HTTP redirect + HTTPS)
- [ ] SSL certificate generated for `lars.local`
- [ ] File permissions set correctly
- [ ] Windows hosts file updated with `lars.local`
- [ ] Can access `https://lars.local` from Windows browser
- [ ] Student, Admin, Staff, and Teacher logins work

---
