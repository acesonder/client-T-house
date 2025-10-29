# Shelter Management System - Installation Guide

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher (8.0+ recommended)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Disk Space**: Minimum 500MB (1GB+ recommended for documents)
- **RAM**: Minimum 2GB (4GB+ recommended)

### PHP Extensions Required
- PDO
- PDO_MySQL
- JSON
- Session
- MBString
- OpenSSL
- FileInfo

## Installation Steps

### 1. Clone or Download the Repository

```bash
git clone https://github.com/acesonder/client-T-house.git
cd client-T-house
```

### 2. Configure Database

#### Create MySQL Database

```sql
CREATE DATABASE shelter_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shelter_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON shelter_management.* TO 'shelter_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Import Database Schema

```bash
mysql -u shelter_user -p shelter_management < database/schema.sql
```

### 3. Configure Application

Edit `/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shelter_management');
define('DB_USER', 'shelter_user');
define('DB_PASS', 'your_secure_password');

define('APP_NAME', 'Your Shelter Name');
define('APP_URL', 'https://yourdomain.com');
define('APP_ENV', 'production'); // Change from 'development'
```

### 4. Set Directory Permissions

```bash
# Create required directories
mkdir -p logs assets/uploads

# Set permissions
chmod 755 logs
chmod 755 assets/uploads
chmod 644 config/config.php

# If using Apache, ensure .htaccess is readable
chmod 644 .htaccess
```

### 5. Configure Web Server

#### Apache Configuration

Create `/public/.htaccess`:

```apache
# Enable Rewrite Engine
RewriteEngine On

# Redirect to HTTPS (production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Block access to sensitive files
<FilesMatch "^(config|includes|\.git|\.env)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default character encoding
AddDefaultCharset UTF-8

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

Create virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/client-T-house
    
    <Directory /path/to/client-T-house>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/shelter_error.log
    CustomLog ${APACHE_LOG_DIR}/shelter_access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/client-T-house;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Block access to sensitive files
    location ~ /(config|includes|\.git) {
        deny all;
    }
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6. Create Default Admin User

Run this SQL to create the first admin account:

```sql
INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, status)
VALUES (
    'admin',
    'admin@yourshelter.org',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    (SELECT role_id FROM roles WHERE role_name = 'admin'),
    'System',
    'Administrator',
    'active'
);

-- Insert user settings for admin
INSERT INTO user_settings (user_id)
VALUES (LAST_INSERT_ID());
```

**IMPORTANT**: Change the password immediately after first login!

### 7. Security Hardening

#### Change Default Passwords
1. Log in with the default admin account
2. Navigate to Profile Settings
3. Change password to a strong, unique password

#### Secure config.php
```bash
chmod 400 config/config.php
```

#### Enable HTTPS
- Obtain SSL certificate (Let's Encrypt recommended)
- Configure web server to redirect HTTP to HTTPS
- Update APP_URL in config.php to use https://

#### Configure PHP Security
Edit `php.ini`:

```ini
display_errors = Off
log_errors = On
error_log = /path/to/client-T-house/logs/php_errors.log
upload_max_filesize = 10M
post_max_size = 10M
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 8. Test Installation

1. Navigate to `http://yourdomain.com/public/index.html`
2. Verify the landing page loads correctly
3. Click "Login" and use default credentials
4. Verify dashboard loads and no errors appear
5. Check error logs: `tail -f logs/error.log`

### 9. Post-Installation Tasks

#### Update System Configuration
1. Log in as admin
2. Navigate to Admin Panel → System Config
3. Update:
   - Site name
   - Contact email
   - Emergency hotline
   - Operating hours
   - Theme colors

#### Create User Accounts
1. Navigate to Admin Panel → Users
2. Create staff, volunteer, and test client accounts
3. Assign appropriate roles and permissions

#### Configure Service Providers
1. Navigate to Admin Panel → Service Providers
2. Add local service providers for:
   - Housing services
   - Mental health services
   - Addiction treatment
   - Legal aid
   - ID services
   - Children services

#### Create Sample Bulletin Posts
1. Navigate to Admin Panel → Bulletin Board
2. Create welcome message for public landing page
3. Add important announcements and updates

### 10. Backup Configuration

#### Automated Database Backup
Create cron job for daily backups:

```bash
# Add to crontab: crontab -e
0 2 * * * /usr/bin/mysqldump -u shelter_user -p'password' shelter_management | gzip > /backups/shelter_$(date +\%Y\%m\%d).sql.gz
```

#### File Backup
```bash
# Backup uploads directory
0 3 * * * tar -czf /backups/uploads_$(date +\%Y\%m\%d).tar.gz /path/to/client-T-house/assets/uploads/
```

## Troubleshooting

### Common Issues

#### Database Connection Failed
- Verify MySQL is running: `systemctl status mysql`
- Check credentials in config.php
- Ensure database user has proper permissions
- Check MySQL error logs: `tail -f /var/log/mysql/error.log`

#### Page Not Found / 404 Errors
- Verify web server configuration
- Check DocumentRoot path
- Ensure mod_rewrite is enabled (Apache)
- Check file permissions

#### Upload Errors
- Check `assets/uploads/` directory permissions
- Verify PHP upload settings in php.ini
- Check disk space: `df -h`

#### Session Issues
- Verify PHP session directory is writable
- Check session configuration in php.ini
- Clear browser cookies

#### Blank Page / White Screen
- Check PHP error logs
- Enable display_errors temporarily in config.php
- Verify all required PHP extensions are installed

### Getting Help

- Check error logs: `tail -f logs/error.log`
- Review PHP error log: `tail -f logs/php_errors.log`
- Check MySQL error log: `tail -f /var/log/mysql/error.log`
- Review web server error log

## Upgrading

### Backup Before Upgrading
```bash
# Backup database
mysqldump -u shelter_user -p shelter_management > backup_$(date +\%Y\%m\%d).sql

# Backup files
tar -czf backup_files_$(date +\%Y\%m\%d).tar.gz /path/to/client-T-house
```

### Apply Updates
```bash
git pull origin main
# Review CHANGELOG.md for database migrations
mysql -u shelter_user -p shelter_management < database/migrations/version_X.sql
```

## Maintenance

### Regular Tasks
- Weekly: Review error logs
- Monthly: Database optimization
- Quarterly: Security updates
- Annually: Full system audit

### Performance Optimization
```sql
-- Optimize database tables
OPTIMIZE TABLE users, messages, intake_assessments, client_resources;

-- Analyze query performance
ANALYZE TABLE users, messages, intake_assessments;
```

## Support

For technical support or questions:
- Email: support@yourshelter.org
- Documentation: /docs/
- GitHub Issues: https://github.com/acesonder/client-T-house/issues
