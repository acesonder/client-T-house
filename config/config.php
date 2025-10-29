<?php
/**
 * Database Configuration
 * 
 * Configure your database connection settings here.
 * For production, use environment variables instead of hardcoded values.
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'shelter_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Shelter Management System');
define('APP_URL', 'http://localhost');
define('APP_ENV', 'development'); // development, staging, production

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_UPLOAD_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Error handling
define('ERROR_LOGGING', true);
define('DISPLAY_ERRORS', APP_ENV === 'development');
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('America/Toronto');

// Auto-load error handler
if (ERROR_LOGGING) {
    error_reporting(E_ALL);
    ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ERROR_LOG_FILE);
}
