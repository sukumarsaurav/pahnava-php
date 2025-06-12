<?php
/**
 * Database Connection and Operations Test
 * Tests database functionality step by step
 */

echo "<h1>🗄️ Database Test</h1>";

// Test 1: Include database config
echo "<h2>Test 1: Database Configuration</h2>";
try {
    require_once '../config/database.php';
    echo "<p>✅ Database configuration loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Database configuration error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Basic connection test
echo "<h2>Test 2: Basic Connection</h2>";
try {
    $testQuery = "SELECT 1 as test, NOW() as current_time";
    $result = $db->fetchRow($testQuery);
    
    if ($result) {
        echo "<p>✅ Database connection successful</p>";
        echo "<p>Test value: " . $result['test'] . "</p>";
        echo "<p>Current time: " . $result['current_time'] . "</p>";
    } else {
        echo "<p>❌ Database query returned no results</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check admin_users table
echo "<h2>Test 3: Admin Users Table</h2>";
try {
    // Check if table exists
    $tableCheck = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
    
    if ($tableCheck) {
        echo "<p>✅ admin_users table exists</p>";
        
        // Get table structure
        $structure = $db->fetchAll("DESCRIBE admin_users");
        echo "<p>Table structure:</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count admin users
        $countResult = $db->fetchRow("SELECT COUNT(*) as count FROM admin_users");
        echo "<p>Total admin users: " . $countResult['count'] . "</p>";
        
        // Show admin users
        if ($countResult['count'] > 0) {
            $admins = $db->fetchAll("SELECT id, username, email, first_name, last_name, role, is_active FROM admin_users");
            echo "<p>Admin users:</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>" . $admin['id'] . "</td>";
                echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
                echo "<td>" . ($admin['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p>❌ admin_users table does not exist</p>";
        echo "<p><a href='setup.php'>Run Admin Setup</a> to create the table</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin users table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Test database operations
echo "<h2>Test 4: Database Operations</h2>";
try {
    // Test INSERT
    echo "<h3>Testing INSERT operation:</h3>";
    $testTable = "CREATE TEMPORARY TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
    $db->execute($testTable);
    echo "<p>✅ Temporary table created</p>";
    
    $insertQuery = "INSERT INTO test_table (name) VALUES (?)";
    $db->execute($insertQuery, ['Test Entry']);
    $insertId = $db->lastInsertId();
    echo "<p>✅ INSERT successful, ID: $insertId</p>";
    
    // Test SELECT
    echo "<h3>Testing SELECT operation:</h3>";
    $selectQuery = "SELECT * FROM test_table WHERE id = ?";
    $result = $db->fetchRow($selectQuery, [$insertId]);
    if ($result) {
        echo "<p>✅ SELECT successful</p>";
        echo "<p>Retrieved: ID=" . $result['id'] . ", Name=" . htmlspecialchars($result['name']) . "</p>";
    } else {
        echo "<p>❌ SELECT failed</p>";
    }
    
    // Test UPDATE
    echo "<h3>Testing UPDATE operation:</h3>";
    $updateQuery = "UPDATE test_table SET name = ? WHERE id = ?";
    $db->execute($updateQuery, ['Updated Entry', $insertId]);
    $rowCount = $db->rowCount();
    echo "<p>✅ UPDATE successful, rows affected: $rowCount</p>";
    
    // Test transaction
    echo "<h3>Testing TRANSACTION:</h3>";
    $db->beginTransaction();
    $db->execute("INSERT INTO test_table (name) VALUES (?)", ['Transaction Test']);
    $db->commit();
    echo "<p>✅ Transaction successful</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database operations error: " . htmlspecialchars($e->getMessage()) . "</p>";
    if ($db->inTransaction()) {
        $db->rollback();
        echo "<p>Transaction rolled back</p>";
    }
}

// Test 5: Test admin user operations
echo "<h2>Test 5: Admin User Operations</h2>";
try {
    // Check if we can read admin user
    $adminQuery = "SELECT * FROM admin_users WHERE username = 'admin' LIMIT 1";
    $admin = $db->fetchRow($adminQuery);
    
    if ($admin) {
        echo "<p>✅ Can read admin user</p>";
        
        // Test password verification
        if (password_verify('admin123', $admin['password'])) {
            echo "<p>✅ Password verification works</p>";
        } else {
            echo "<p>❌ Password verification failed</p>";
            echo "<p>This might be why login is failing</p>";
        }
        
        // Test update operation
        echo "<h3>Testing admin user update:</h3>";
        $updateQuery = "UPDATE admin_users SET updated_at = NOW() WHERE id = ?";
        $db->execute($updateQuery, [$admin['id']]);
        $rowCount = $db->rowCount();
        echo "<p>✅ Admin user update successful, rows affected: $rowCount</p>";
        
    } else {
        echo "<p>❌ Cannot find admin user</p>";
        echo "<p><a href='reset-admin.php'>Create Admin User</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Admin user operations error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Database permissions
echo "<h2>Test 6: Database Permissions</h2>";
try {
    // Test various permissions
    $permissions = [];
    
    // SELECT permission
    try {
        $db->fetchRow("SELECT 1");
        $permissions['SELECT'] = '✅';
    } catch (Exception $e) {
        $permissions['SELECT'] = '❌';
    }
    
    // INSERT permission
    try {
        $db->execute("CREATE TEMPORARY TABLE perm_test (id INT)");
        $db->execute("INSERT INTO perm_test VALUES (1)");
        $permissions['INSERT'] = '✅';
    } catch (Exception $e) {
        $permissions['INSERT'] = '❌';
    }
    
    // UPDATE permission
    try {
        $db->execute("UPDATE perm_test SET id = 2 WHERE id = 1");
        $permissions['UPDATE'] = '✅';
    } catch (Exception $e) {
        $permissions['UPDATE'] = '❌';
    }
    
    // DELETE permission
    try {
        $db->execute("DELETE FROM perm_test WHERE id = 2");
        $permissions['DELETE'] = '✅';
    } catch (Exception $e) {
        $permissions['DELETE'] = '❌';
    }
    
    echo "<p>Database permissions:</p>";
    echo "<ul>";
    foreach ($permissions as $perm => $status) {
        echo "<li>$perm: $status</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Permission test error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>📋 Summary</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p>If all tests show ✅, the database is working correctly.</p>";
echo "<p>If you see ❌ errors, those need to be fixed before the admin panel will work.</p>";
echo "</div>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 15px;'>";
echo "<h4>Next Steps:</h4>";
echo "<ul>";
echo "<li><a href='reset-admin.php'>Reset Admin User</a> - Fix admin user issues</li>";
echo "<li><a href='update-credentials.php'>Update Credentials</a> - Change admin credentials</li>";
echo "<li><a href='login-test.php'>Test Login</a> - Test login functionality</li>";
echo "<li><a href='index.php'>Try Admin Panel</a> - Access main admin</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Database test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
