<?php
/**
 * Check Database Tables
 * Shows what tables exist in the database
 */

echo "<h1>🗄️ Database Tables Check</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Show all tables
try {
    $tablesQuery = "SHOW TABLES";
    $tables = $db->fetchAll($tablesQuery);
    
    echo "<h2>📋 Available Tables</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    
    if (!empty($tables)) {
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0]; // Get the table name
            echo "<li><strong>" . htmlspecialchars($tableName) . "</strong></li>";
        }
        echo "</ul>";
        
        echo "<h3>📊 Table Details</h3>";
        
        // Show structure for each table
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            try {
                // Get table structure
                $structureQuery = "DESCRIBE `$tableName`";
                $structure = $db->fetchAll($structureQuery);
                
                // Get row count
                $countQuery = "SELECT COUNT(*) as count FROM `$tableName`";
                $count = $db->fetchRow($countQuery)['count'];
                
                echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
                echo "<h4>$tableName ($count rows)</h4>";
                
                if (!empty($structure)) {
                    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
                    echo "<tr style='background: #e9ecef;'>";
                    echo "<th style='padding: 5px;'>Field</th>";
                    echo "<th style='padding: 5px;'>Type</th>";
                    echo "<th style='padding: 5px;'>Null</th>";
                    echo "<th style='padding: 5px;'>Key</th>";
                    echo "<th style='padding: 5px;'>Default</th>";
                    echo "</tr>";
                    
                    foreach ($structure as $column) {
                        echo "<tr>";
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($column['Field']) . "</td>";
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($column['Type']) . "</td>";
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($column['Null']) . "</td>";
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($column['Key']) . "</td>";
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No structure information available.</p>";
                }
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #f00; border-radius: 5px;'>";
                echo "<h4>$tableName</h4>";
                echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<p>No tables found in the database.</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p>❌ Error getting tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Create basic tables if they don't exist
echo "<h2>🔧 Create Basic Tables</h2>";

$basicTables = [
    'products' => "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        sku VARCHAR(100),
        stock_quantity INT DEFAULT 0,
        category_id INT,
        image VARCHAR(255),
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'categories' => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255),
        parent_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'orders' => "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        customer_phone VARCHAR(20),
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        shipping_address TEXT,
        billing_address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

echo "<form method='POST'>";
echo "<p>Click the button below to create basic ecommerce tables:</p>";
echo "<button type='submit' name='create_tables' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Basic Tables</button>";
echo "</form>";

if (isset($_POST['create_tables'])) {
    echo "<h3>Creating Tables...</h3>";
    
    foreach ($basicTables as $tableName => $sql) {
        try {
            $db->execute($sql);
            echo "<p>✅ Table '$tableName' created successfully</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error creating table '$tableName': " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<p><strong>Tables creation completed!</strong></p>";
    echo "<p><a href='check-tables.php'>Refresh to see updated tables</a></p>";
}

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Admin Tools:</h4>";
echo "<ul>";
echo "<li><a href='db-test.php'>Database Test</a> - Test database operations</li>";
echo "<li><a href='simple-password-fix.php'>Password Fix</a> - Fix admin password</li>";
echo "<li><a href='index.php'>Admin Panel</a> - Try accessing admin</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Table check completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
