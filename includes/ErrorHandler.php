<?php
/**
 * Error Handler Class
 * 
 * Comprehensive error logging and handling system
 */

require_once __DIR__ . '/../config/config.php';

class ErrorHandler {
    
    /**
     * Log error to database
     */
    public function logError($type, $severity, $message, $code = null, $file = null, $line = null, $trace = null, $context = []) {
        // Ensure logs directory exists
        $logDir = dirname(ERROR_LOG_FILE);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Log to file first (in case database is down)
        $this->logToFile($type, $severity, $message, $file, $line);
        
        // Try to log to database
        try {
            // Avoid circular dependency - use direct PDO connection
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            $sql = "INSERT INTO error_logs (
                error_type, severity, error_message, error_code, file_path, 
                line_number, stack_trace, user_id, ip_address, request_uri, 
                request_method, user_agent, context
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $type,
                $severity,
                $message,
                $code,
                $file,
                $line,
                $trace,
                $this->getCurrentUserId(),
                $this->getClientIp(),
                $_SERVER['REQUEST_URI'] ?? null,
                $_SERVER['REQUEST_METHOD'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                json_encode($context)
            ]);
        } catch (Exception $e) {
            // If database logging fails, just log to file
            $this->logToFile('system', 'error', 'Failed to log to database: ' . $e->getMessage());
        }
    }
    
    /**
     * Log to file
     */
    private function logToFile($type, $severity, $message, $file = null, $line = null) {
        $logMessage = sprintf(
            "[%s] [%s] [%s] %s (File: %s, Line: %s)\n",
            date('Y-m-d H:i:s'),
            strtoupper($severity),
            strtoupper($type),
            $message,
            $file ?? 'N/A',
            $line ?? 'N/A'
        );
        
        error_log($logMessage, 3, ERROR_LOG_FILE);
    }
    
    /**
     * Get current user ID from session
     */
    private function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Handle PHP errors
     */
    public static function handlePhpError($errno, $errstr, $errfile, $errline) {
        $handler = new self();
        
        $severity = 'error';
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $severity = 'critical';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $severity = 'warning';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $severity = 'info';
                break;
        }
        
        $handler->logError('php', $severity, $errstr, $errno, $errfile, $errline);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        $handler = new self();
        $handler->logError(
            'application',
            'critical',
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        if (DISPLAY_ERRORS) {
            echo '<h1>Application Error</h1>';
            echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>An error occurred</h1>';
            echo '<p>The system administrators have been notified.</p>';
        }
    }
}

// Set custom error and exception handlers
if (ERROR_LOGGING) {
    set_error_handler(['ErrorHandler', 'handlePhpError']);
    set_exception_handler(['ErrorHandler', 'handleException']);
}
