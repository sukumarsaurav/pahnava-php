<?php
/**
 * Admin Setup Script
 * Creates necessary admin tables and default admin user
 */

// Include database connection
require_once '../config/database.php';

// Check if admin tables exist and create them if not
function setupAdminTables($db) {
    $tables = [];
    
    // Admin Users Table
    $tables['admin_users'] = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'staff',
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            failed_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    // Admin Remember Tokens Table
    $tables['admin_remember_tokens'] = "
        CREATE TABLE IF NOT EXISTS admin_remember_tokens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
        )
    ";
    
    // Admin Activity Logs Table
    $tables['admin_activity_logs'] = "
        CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
        )
    ";
    
    // Settings Table
    $tables['settings'] = "
        CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    // Order Status History Table
    $tables['order_status_history'] = "
        CREATE TABLE IF NOT EXISTS order_status_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            notes TEXT,
            admin_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
        )
    ";
    
    // Security Events Table
    $tables['security_events'] = "
        CREATE TABLE IF NOT EXISTS security_events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_type VARCHAR(100) NOT NULL,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    // Rate Limiting Table
    $tables['rate_limits'] = "
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(100) NOT NULL,
            attempts INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rate_limit (identifier, action)
        )
    ";
    
    $created = [];
    $errors = [];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $db->execute($sql);
            $created[] = $tableName;
        } catch (Exception $e) {
            $errors[] = "Failed to create table $tableName: " . $e->getMessage();
        }
    }
    
    return ['created' => $created, 'errors' => $errors];
}

// Create default admin user
function createDefaultAdmin($db) {
    try {
        // Check if admin user already exists
        $existingAdmin = $db->fetchRow("SELECT id FROM admin_users WHERE username = 'admin'");
        
        if ($existingAdmin) {
            return ['exists' => true, 'message' => 'Default admin user already exists'];
        }
        
        // Create password hash for 'admin123'
        $password = password_hash('admin123', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        // Insert default admin user
        $query = "INSERT INTO admin_users (username, email, password, first_name, last_name, role) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $db->execute($query, [
            'admin',
            'admin@pahnava.com',
            $password,
            'Admin',
            'User',
            'super_admin'
        ]);
        
        return ['created' => true, 'message' => 'Default admin user created successfully'];
        
    } catch (Exception $e) {
        return ['error' => true, 'message' => 'Failed to create admin user: ' . $e->getMessage()];
    }
}

// Insert default settings
function insertDefaultSettings($db) {
    $defaultSettings = [
        'site_name' => 'Pahnava',
        'site_description' => 'Premium Fashion & Clothing Store',
        'contact_email' => 'contact@pahnava.com',
        'currency' => 'INR',
        'timezone' => 'Asia/Kolkata',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s'
    ];
    
    $inserted = [];
    $errors = [];
    
    foreach ($defaultSettings as $key => $value) {
        try {
            // Check if setting already exists
            $existing = $db->fetchRow("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            
            if (!$existing) {
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                $db->execute($query, [$key, $value]);
                $inserted[] = $key;
            }
        } catch (Exception $e) {
            $errors[] = "Failed to insert setting $key: " . $e->getMessage();
        }
    }
    
    return ['inserted' => $inserted, 'errors' => $errors];
}

// Run setup
try {
    echo "<h1>Pahnava Admin Setup</h1>";
    
    // Setup tables
    echo "<h2>Setting up admin tables...</h2>";
    $tableResult = setupAdminTables($db);
    
    if (!empty($tableResult['created'])) {
        echo "<p style='color: green;'>✅ Created tables: " . implode(', ', $tableResult['created']) . "</p>";
    }
    
    if (!empty($tableResult['errors'])) {
        echo "<p style='color: red;'>❌ Errors: " . implode('<br>', $tableResult['errors']) . "</p>";
    }
    
    // Create default admin
    echo "<h2>Creating default admin user...</h2>";
    $adminResult = createDefaultAdmin($db);
    
    if (isset($adminResult['created'])) {
        echo "<p style='color: green;'>✅ " . $adminResult['message'] . "</p>";
        echo "<p><strong>Default Login:</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<em>Please change this password immediately after first login!</em></p>";
    } elseif (isset($adminResult['exists'])) {
        echo "<p style='color: orange;'>⚠️ " . $adminResult['message'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ " . $adminResult['message'] . "</p>";
    }
    
    // Insert default settings
    echo "<h2>Inserting default settings...</h2>";
    $settingsResult = insertDefaultSettings($db);
    
    if (!empty($settingsResult['inserted'])) {
        echo "<p style='color: green;'>✅ Inserted settings: " . implode(', ', $settingsResult['inserted']) . "</p>";
    }
    
    if (!empty($settingsResult['errors'])) {
        echo "<p style='color: red;'>❌ Settings errors: " . implode('<br>', $settingsResult['errors']) . "</p>";
    }
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p><a href='index.php'>Go to Admin Panel</a></p>";
    echo "<p><em>You can delete this setup.php file after successful setup.</em></p>";
    
} catch (Exception $e) {
    echo "<h1>Setup Failed</h1>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
