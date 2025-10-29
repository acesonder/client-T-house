<?php
/**
 * Authentication Class
 * 
 * Handles user authentication, sessions, and role-based access control
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ErrorHandler.php';

class Auth {
    private $db;
    private $errorHandler;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->errorHandler = new ErrorHandler();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $roleId, $firstName = null, $lastName = null, $phone = null) {
        // Validate password strength
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Check if username or email already exists
        $existing = $this->db->fetchOne(
            "SELECT user_id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, [$username, $email, $passwordHash, $roleId, $firstName, $lastName, $phone]);
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            
            // Create default user settings
            $this->db->query(
                "INSERT INTO user_settings (user_id) VALUES (?)",
                [$userId]
            );
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Fetch user
        $user = $this->db->fetchOne(
            "SELECT u.*, r.role_name, r.permissions 
             FROM users u 
             JOIN roles r ON u.role_id = r.role_id 
             WHERE u.username = ? AND u.status = 'active'",
            [$username]
        );
        
        if (!$user) {
            $this->errorHandler->logError('security', 'warning', 
                "Failed login attempt for username: $username", null, __FILE__, __LINE__);
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->errorHandler->logError('security', 'warning', 
                "Failed password verification for user: $username", null, __FILE__, __LINE__);
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Create session
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $this->db->query(
            "INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at) 
             VALUES (?, ?, ?, ?, ?)",
            [$sessionId, $user['user_id'], $this->getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '', $expiresAt]
        );
        
        // Update last login
        $this->db->query(
            "UPDATE users SET last_login = NOW() WHERE user_id = ?",
            [$user['user_id']]
        );
        
        // Set session variables
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true);
        $_SESSION['logged_in'] = true;
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['session_id'])) {
            // Delete session from database
            $this->db->query(
                "DELETE FROM sessions WHERE session_id = ?",
                [$_SESSION['session_id']]
            );
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || !isset($_SESSION['session_id'])) {
            return false;
        }
        
        // Verify session in database
        $session = $this->db->fetchOne(
            "SELECT * FROM sessions WHERE session_id = ? AND expires_at > NOW()",
            [$_SESSION['session_id']]
        );
        
        return $session !== false;
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $permissions = $_SESSION['permissions'] ?? [];
        
        // Admin has all permissions
        if (isset($permissions['all']) && $permissions['all']) {
            return true;
        }
        
        return isset($permissions[$permission]) && $permissions[$permission];
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['role_name'] === $role;
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT u.*, r.role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.role_id 
             WHERE u.user_id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Get user settings
     */
    public function getUserSettings($userId = null) {
        $userId = $userId ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM user_settings WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Update user settings
     */
    public function updateUserSettings($userId, $settings) {
        $allowedFields = ['theme_color', 'navbar_color', 'button_color', 'font_size', 'layout_style', 'custom_css'];
        $updateFields = [];
        $updateValues = [];
        
        foreach ($settings as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $updateValues[] = $userId;
        $sql = "UPDATE user_settings SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        
        return $this->db->query($sql, $updateValues) !== false;
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /public/login.php');
            exit;
        }
    }
    
    /**
     * Require permission (redirect if not authorized)
     */
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            header('Location: /public/unauthorized.php');
            exit;
        }
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
}
