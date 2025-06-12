<?php
/**
 * Database Configuration and Connection
 * Secure MySQL connection with error handling
 * 
 * @security Uses prepared statements and secure connection
 */

class Database {
    private static $instance = null;
    private $connection;
    private $lastStatement = null;

    // Database configuration
    private $host = 'localhost';
    private $username = 'u911550082_pahnava';
    private $password = 'Milk@sdk14';
    private $database = 'u911550082_pahnava';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish secure database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute prepared statement with parameters
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $this->lastStatement = $stmt; // Store for rowCount
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage() . " Query: " . $query);
            throw new Exception("Database operation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchRow($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Get row count from last statement
     */
    public function rowCount() {
        if ($this->lastStatement) {
            return $this->lastStatement->rowCount();
        }
        return 0;
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
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
        return $this->connection->rollback();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        $query = "SHOW TABLES LIKE ?";
        $result = $this->fetchRow($query, [$tableName]);
        return !empty($result);
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}
?>
