<?php
/**
 * Error Check Script
 * Helps identify server errors and configuration issues
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 Pahnava Error Diagnosis</h1>";

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";

// Check error logs
echo "<h2>Error Log Check</h2>";
$errorLog = ini_get('error_log');
echo "<p><strong>Error Log Location:</strong> " . ($errorLog ?: 'Default system log') . "</p>";

// Check if we can read error logs
if ($errorLog && file_exists($errorLog) && is_readable($errorLog)) {
    echo "<h3>Recent Error Log Entries:</h3>";
    $logContent = file_get_contents($errorLog);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20); // Last 20 lines
    
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recentLines as $line) {
        if (trim($line)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>⚠️ Cannot access error log file</p>";
}

// Check file permissions
echo "<h2>File Permissions Check</h2>";
$checkPaths = [
    '.',
    'config',
    'admin',
    'admin/includes',
    'admin/pages',
    'admin/assets',
    'includes'
];

foreach ($checkPaths as $path) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path) ? '✅' : '❌';
        $writable = is_writable($path) ? '✅' : '❌';
        echo "<p><strong>$path/</strong> - Permissions: $perms | Readable: $readable | Writable: $writable</p>";
    } else {
        echo "<p><strong>$path/</strong> - ❌ Directory not found</p>";
    }
}

// Check critical files
echo "<h2>Critical Files Check</h2>";
$criticalFiles = [
    'config/database.php' => 'Database Configuration',
    'includes/security.php' => 'Security Class',
    'includes/functions.php' => 'Core Functions',
    'admin/includes/admin-auth.php' => 'Admin Authentication',
    'admin/includes/admin-functions.php' => 'Admin Functions',
    'admin/pages/login.php' => 'Login Page',
    'admin/pages/dashboard.php' => 'Dashboard Page'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $readable = is_readable($file) ? '✅' : '❌';
        echo "<p>✅ <strong>$description</strong> ($file) - Size: {$size}B | Readable: $readable</p>";
    } else {
        echo "<p>❌ <strong>$description</strong> ($file) - Missing</p>";
    }
}

// Test basic PHP functionality
echo "<h2>PHP Functionality Test</h2>";

// Test sessions
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Sessions working</p>";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test JSON
try {
    $testArray = ['test' => 'value'];
    $json = json_encode($testArray);
    $decoded = json_decode($json, true);
    if ($decoded['test'] === 'value') {
        echo "<p>✅ JSON functions working</p>";
    } else {
        echo "<p>❌ JSON decode failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ JSON error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test PDO
try {
    if (class_exists('PDO')) {
        $drivers = PDO::getAvailableDrivers();
        if (in_array('mysql', $drivers)) {
            echo "<p>✅ PDO MySQL available</p>";
        } else {
            echo "<p>❌ PDO MySQL driver not available</p>";
        }
    } else {
        echo "<p>❌ PDO not available</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ PDO error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test database connection if config exists
echo "<h2>Database Connection Test</h2>";
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        echo "<p>✅ Database config loaded</p>";
        
        // Test simple query
        $result = $db->fetchRow("SELECT 1 as test, NOW() as current_time");
        if ($result) {
            echo "<p>✅ Database connection successful</p>";
            echo "<p>Current time from DB: " . htmlspecialchars($result['current_time']) . "</p>";
        } else {
            echo "<p>❌ Database query failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ Database config file missing</p>";
}

// Memory and execution limits
echo "<h2>Server Limits</h2>";
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "s</p>";
echo "<p><strong>Upload Max Size:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>Post Max Size:</strong> " . ini_get('post_max_size') . "</p>";

// Recommendations
echo "<h2>🎯 Recommendations</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3;'>";
echo "<h4>To fix the 500 error:</h4>";
echo "<ol>";
echo "<li><strong>Run Deploy Script:</strong> <a href='deploy.php'>deploy.php</a> - Sets up database config</li>";
echo "<li><strong>Run Admin Setup:</strong> <a href='admin/setup.php'>admin/setup.php</a> - Creates admin tables</li>";
echo "<li><strong>Test Components:</strong> <a href='admin/test.php'>admin/test.php</a> - Detailed component test</li>";
echo "<li><strong>Try Simple Admin:</strong> <a href='admin/simple.php'>admin/simple.php</a> - Simplified admin panel</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin-top: 15px;'>";
echo "<h4>If problems persist:</h4>";
echo "<ul>";
echo "<li>Check your hosting provider's error logs</li>";
echo "<li>Verify PHP version is 7.4 or higher</li>";
echo "<li>Ensure all files were uploaded correctly</li>";
echo "<li>Check file permissions (755 for directories, 644 for files)</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Diagnosis completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
