<?php
/**
 * Admin Debug Script
 * Helps identify issues with the admin panel
 */

echo "<h1>Pahnava Admin Debug</h1>";

// Check PHP version
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "</p>";

// Check file existence
echo "<h2>File Check</h2>";
$requiredFiles = [
    '../config/database.php',
    '../includes/security.php',
    '../includes/functions.php',
    'includes/admin-auth.php',
    'includes/admin-functions.php',
    'includes/header.php',
    'includes/footer.php',
    'pages/login.php',
    'pages/dashboard.php'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✅' : '❌';
    echo "<p>$status $file</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check if admin tables exist
    $adminTables = [
        'admin_users',
        'admin_remember_tokens', 
        'admin_activity_logs',
        'settings',
        'order_status_history',
        'security_events',
        'rate_limits'
    ];
    
    echo "<h3>Admin Tables Check</h3>";
    foreach ($adminTables as $table) {
        try {
            $result = $db->fetchRow("SHOW TABLES LIKE '$table'");
            $exists = !empty($result);
            $status = $exists ? '✅' : '❌';
            echo "<p>$status Table: $table</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error checking table $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check if admin user exists
    echo "<h3>Admin User Check</h3>";
    try {
        $adminUser = $db->fetchRow("SELECT * FROM admin_users WHERE username = 'admin'");
        if ($adminUser) {
            echo "<p>✅ Default admin user exists</p>";
            echo "<p>Username: " . htmlspecialchars($adminUser['username']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($adminUser['email']) . "</p>";
            echo "<p>Role: " . htmlspecialchars($adminUser['role']) . "</p>";
        } else {
            echo "<p>❌ Default admin user does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking admin user: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check includes
echo "<h2>Include Test</h2>";
try {
    require_once '../includes/security.php';
    echo "<p>✅ Security class loaded</p>";
    
    require_once '../includes/functions.php';
    echo "<p>✅ Functions loaded</p>";
    
    require_once 'includes/admin-auth.php';
    echo "<p>✅ Admin auth class loaded</p>";
    
    require_once 'includes/admin-functions.php';
    echo "<p>✅ Admin functions loaded</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Include error: " . $e->getMessage() . "</p>";
}

// Test admin auth
echo "<h2>Admin Auth Test</h2>";
try {
    session_start();
    Security::init();
    $adminAuth = new AdminAuth($db);
    echo "<p>✅ Admin auth initialized successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Admin auth error: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations</h2>";
echo "<p>1. If admin tables are missing, run <a href='setup.php'>setup.php</a></p>";
echo "<p>2. If files are missing, check file permissions</p>";
echo "<p>3. If database connection fails, check config/database.php</p>";
echo "<p>4. After fixing issues, try accessing <a href='index.php'>admin panel</a></p>";

echo "<hr>";
echo "<p><small>Debug completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
