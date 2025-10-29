<?php
/**
 * Database Connection Class
 * 
 * Handles MySQL database connections with error handling and logging
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ErrorHandler.php';

class Database {
    private static $instance = null;
    private $connection;
    private $errorHandler;
    
    private function __construct() {
        $this->errorHandler = new ErrorHandler();
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->errorHandler->logError('mysql', 'critical', 
                'Database connection failed: ' . $e->getMessage(), 
                $e->getCode(), __FILE__, __LINE__, $e->getTraceAsString());
            
            if (DISPLAY_ERRORS) {
                die('Database connection failed. Please check your configuration.');
            } else {
                die('A system error occurred. Please contact the administrator.');
            }
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query with error handling
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->errorHandler->logError('mysql', 'error', 
                'Query failed: ' . $e->getMessage(), 
                $e->getCode(), __FILE__, __LINE__, $e->getTraceAsString(),
                ['sql' => $sql, 'params' => $params]);
            return false;
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
}
