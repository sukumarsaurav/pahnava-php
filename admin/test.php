<?php
/**
 * Simple Admin Test Script
 * Helps identify the exact cause of the 500 error
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Panel Test</h1>";
echo "<p>Testing admin panel components step by step...</p>";

// Test 1: Basic PHP
echo "<h2>Test 1: Basic PHP</h2>";
echo "<p>✅ PHP is working - Version: " . phpversion() . "</p>";

// Test 2: File existence
echo "<h2>Test 2: File Existence</h2>";
$files = [
    '../config/database.php' => 'Database Config',
    '../includes/security.php' => 'Security Class',
    '../includes/functions.php' => 'Functions',
    'includes/admin-auth.php' => 'Admin Auth',
    'includes/admin-functions.php' => 'Admin Functions'
];

foreach ($files as $file => $name) {
    if (file_exists($file)) {
        echo "<p>✅ $name: Found</p>";
    } else {
        echo "<p>❌ $name: Missing ($file)</p>";
    }
}

// Test 3: Database connection
echo "<h2>Test 3: Database Connection</h2>";
try {
    if (file_exists('../config/database.php')) {
        require_once '../config/database.php';
        echo "<p>✅ Database file loaded</p>";
        
        // Test connection
        $testQuery = "SELECT 1 as test";
        $result = $db->fetchRow($testQuery);
        if ($result && $result['test'] == 1) {
            echo "<p>✅ Database connection successful</p>";
        } else {
            echo "<p>❌ Database query failed</p>";
        }
    } else {
        echo "<p>❌ Database config file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Include files one by one
echo "<h2>Test 4: Include Files</h2>";

try {
    if (file_exists('../includes/security.php')) {
        require_once '../includes/security.php';
        echo "<p>✅ Security class loaded</p>";
    } else {
        echo "<p>❌ Security file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Security include error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        echo "<p>✅ Functions loaded</p>";
    } else {
        echo "<p>❌ Functions file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Functions include error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    if (file_exists('includes/admin-auth.php')) {
        require_once 'includes/admin-auth.php';
        echo "<p>✅ Admin auth loaded</p>";
    } else {
        echo "<p>❌ Admin auth file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin auth include error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    if (file_exists('includes/admin-functions.php')) {
        require_once 'includes/admin-functions.php';
        echo "<p>✅ Admin functions loaded</p>";
    } else {
        echo "<p>❌ Admin functions file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin functions include error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Session start
echo "<h2>Test 5: Session</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Session started successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Check admin tables
echo "<h2>Test 6: Admin Tables</h2>";
if (isset($db)) {
    $adminTables = ['admin_users', 'settings', 'admin_activity_logs'];
    foreach ($adminTables as $table) {
        try {
            $result = $db->fetchRow("SHOW TABLES LIKE '$table'");
            if ($result) {
                echo "<p>✅ Table $table exists</p>";
            } else {
                echo "<p>❌ Table $table missing</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking table $table: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<p>❌ Database not available for table check</p>";
}

// Test 7: Try creating admin auth
echo "<h2>Test 7: Admin Auth Creation</h2>";
try {
    if (isset($db) && class_exists('AdminAuth')) {
        $adminAuth = new AdminAuth($db);
        echo "<p>✅ AdminAuth created successfully</p>";
    } else {
        echo "<p>❌ Cannot create AdminAuth (missing database or class)</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ AdminAuth creation error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>If you see any ❌ errors above, those need to be fixed first.</p>";
echo "<p>Common solutions:</p>";
echo "<ul>";
echo "<li>Run <a href='../deploy.php'>deploy.php</a> to set up database config</li>";
echo "<li>Run <a href='setup.php'>setup.php</a> to create admin tables</li>";
echo "<li>Check file permissions (755 for directories, 644 for files)</li>";
echo "<li>Verify all files were uploaded correctly</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
