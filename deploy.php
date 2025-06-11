<?php
/**
 * Pahnava Deployment Script
 * Automatically configures database and sets up the system
 */

// Your database credentials
$DB_HOST = 'localhost';
$DB_USERNAME = 'u911550082_pahnava';
$DB_PASSWORD = 'Milk@sdk14';
$DB_NAME = 'u911550082_pahnava';

echo "<h1>🚀 Pahnava Deployment Script</h1>";

// Step 1: Create database configuration file
echo "<h2>Step 1: Creating Database Configuration</h2>";

$databaseConfig = '<?php
/**
 * Database Configuration and Connection
 * Secure MySQL connection with error handling
 * 
 * @security Uses prepared statements and secure connection
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = \'' . $DB_HOST . '\';
    private $username = \'' . $DB_USERNAME . '\';
    private $password = \'' . $DB_PASSWORD . '\';
    private $database = \'' . $DB_NAME . '\';
    private $charset = \'utf8mb4\';
    
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
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new Exception("Database operation failed");
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
        return $this->connection->rowCount();
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
?>';

// Write database configuration
if (file_put_contents('config/database.php', $databaseConfig)) {
    echo "<p>✅ Database configuration created successfully</p>";
} else {
    echo "<p>❌ Failed to create database configuration</p>";
    exit;
}

// Step 2: Test database connection
echo "<h2>Step 2: Testing Database Connection</h2>";
try {
    require_once 'config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Step 3: Create necessary directories
echo "<h2>Step 3: Creating Directories</h2>";
$directories = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/brands',
    'uploads/users',
    'logs',
    'cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>✅ Created directory: $dir</p>";
        } else {
            echo "<p>⚠️ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p>✅ Directory exists: $dir</p>";
    }
}

// Step 4: Set file permissions
echo "<h2>Step 4: Setting File Permissions</h2>";
$permissionFiles = [
    'config' => 0755,
    'uploads' => 0755,
    'logs' => 0755,
    'cache' => 0755
];

foreach ($permissionFiles as $path => $permission) {
    if (is_dir($path)) {
        if (chmod($path, $permission)) {
            echo "<p>✅ Set permissions for: $path</p>";
        } else {
            echo "<p>⚠️ Failed to set permissions for: $path</p>";
        }
    }
}

echo "<h2>🎉 Deployment Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li><a href='admin/setup.php'>Run Admin Setup</a> - Creates admin tables and default user</li>";
echo "<li><a href='admin/'>Access Admin Panel</a> - Login with admin/admin123</li>";
echo "<li><a href='index.php'>View Frontend</a> - Check the main website</li>";
echo "</ol>";

echo "<p><strong>Security Reminders:</strong></p>";
echo "<ul>";
echo "<li>Change the default admin password immediately</li>";
echo "<li>Delete this deploy.php file after deployment</li>";
echo "<li>Review and update .gitignore file</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Deployment completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
