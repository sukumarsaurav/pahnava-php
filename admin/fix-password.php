<?php
/**
 * Password Fix Script
 * Fixes the admin password hash issue
 */

echo "<h1>🔑 Fix Admin Password</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Get current admin user
try {
    $adminQuery = "SELECT * FROM admin_users WHERE username = 'admin'";
    $admin = $db->fetchRow($adminQuery);
    
    if (!$admin) {
        echo "<p>❌ Admin user not found</p>";
        echo "<p><a href='create-admin.php'>Create Admin User</a></p>";
        exit;
    }
    
    echo "<p>✅ Admin user found: " . htmlspecialchars($admin['username']) . "</p>";
    echo "<p>Current password hash: " . substr($admin['password'], 0, 50) . "...</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error getting admin user: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test current password
echo "<h2>🧪 Testing Current Password</h2>";
$testPassword = 'admin123';

if (password_verify($testPassword, $admin['password'])) {
    echo "<p>✅ Current password works! No fix needed.</p>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Password is Working!</h4>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login</a>";
    echo "</div>";
} else {
    echo "<p>❌ Current password verification failed</p>";
    
    // Fix the password
    echo "<h2>🔧 Fixing Password</h2>";
    
    try {
        // Create new password hash using different methods
        echo "<h3>Testing Password Hash Methods:</h3>";
        
        // Method 1: PASSWORD_DEFAULT (recommended)
        $hash1 = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<p>Method 1 (DEFAULT): " . (password_verify($testPassword, $hash1) ? '✅' : '❌') . "</p>";
        
        // Method 2: PASSWORD_BCRYPT
        $hash2 = password_hash($testPassword, PASSWORD_BCRYPT);
        echo "<p>Method 2 (BCRYPT): " . (password_verify($testPassword, $hash2) ? '✅' : '❌') . "</p>";
        
        // Method 3: PASSWORD_ARGON2ID (if available)
        if (defined('PASSWORD_ARGON2ID')) {
            $hash3 = password_hash($testPassword, PASSWORD_ARGON2ID);
            echo "<p>Method 3 (ARGON2ID): " . (password_verify($testPassword, $hash3) ? '✅' : '❌') . "</p>";
        } else {
            echo "<p>Method 3 (ARGON2ID): Not available on this server</p>";
            $hash3 = null;
        }
        
        // Choose the best working method
        $newHash = $hash1; // Default to PASSWORD_DEFAULT
        $method = 'PASSWORD_DEFAULT';
        
        if ($hash3 && password_verify($testPassword, $hash3)) {
            $newHash = $hash3;
            $method = 'PASSWORD_ARGON2ID';
        } elseif (password_verify($testPassword, $hash2)) {
            $newHash = $hash2;
            $method = 'PASSWORD_BCRYPT';
        }
        
        echo "<p>✅ Using method: $method</p>";
        
        // Update the password in database
        echo "<h3>Updating Password in Database:</h3>";
        
        $updateQuery = "UPDATE admin_users SET password = ?, updated_at = NOW() WHERE username = 'admin'";
        $stmt = $db->getConnection()->prepare($updateQuery);
        $result = $stmt->execute([$newHash]);
        
        if ($result) {
            echo "<p>✅ Password updated in database</p>";
            
            // Verify the update worked
            $verifyQuery = "SELECT password FROM admin_users WHERE username = 'admin'";
            $verifyResult = $db->fetchRow($verifyQuery);
            
            if ($verifyResult && password_verify($testPassword, $verifyResult['password'])) {
                echo "<p>✅ Password verification successful after update!</p>";
                
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h4>🎉 Password Fixed Successfully!</h4>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
                echo "<p><strong>Hash Method:</strong> $method</p>";
                echo "<div style='margin-top: 15px;'>";
                echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Try Login</a>";
                echo "<a href='login-test.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Login</a>";
                echo "</div>";
                echo "</div>";
                
            } else {
                echo "<p>❌ Password verification still failed after update</p>";
            }
        } else {
            echo "<p>❌ Failed to update password in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error fixing password: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Show PHP password info
echo "<h2>📋 PHP Password Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Available Password Algorithms:</strong></p>";
echo "<ul>";

$algorithms = [
    'PASSWORD_DEFAULT' => PASSWORD_DEFAULT,
    'PASSWORD_BCRYPT' => PASSWORD_BCRYPT
];

if (defined('PASSWORD_ARGON2I')) {
    $algorithms['PASSWORD_ARGON2I'] = PASSWORD_ARGON2I;
}

if (defined('PASSWORD_ARGON2ID')) {
    $algorithms['PASSWORD_ARGON2ID'] = PASSWORD_ARGON2ID;
}

foreach ($algorithms as $name => $value) {
    echo "<li>$name (value: $value)</li>";
}
echo "</ul>";

// Show current admin info
echo "<h2>👤 Current Admin Information</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th style='padding: 8px;'>Field</th><th style='padding: 8px;'>Value</th></tr>";
echo "<tr><td style='padding: 8px;'>ID</td><td style='padding: 8px;'>" . $admin['id'] . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Username</td><td style='padding: 8px;'>" . htmlspecialchars($admin['username']) . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Email</td><td style='padding: 8px;'>" . htmlspecialchars($admin['email']) . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Name</td><td style='padding: 8px;'>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Role</td><td style='padding: 8px;'>" . htmlspecialchars($admin['role']) . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Active</td><td style='padding: 8px;'>" . ($admin['is_active'] ? 'Yes' : 'No') . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Failed Attempts</td><td style='padding: 8px;'>" . $admin['failed_attempts'] . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Locked Until</td><td style='padding: 8px;'>" . ($admin['locked_until'] ? $admin['locked_until'] : 'Not locked') . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Last Login</td><td style='padding: 8px;'>" . ($admin['last_login'] ? $admin['last_login'] : 'Never') . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Created</td><td style='padding: 8px;'>" . $admin['created_at'] . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Updated</td><td style='padding: 8px;'>" . $admin['updated_at'] . "</td></tr>";
echo "</table>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Other Tools:</h4>";
echo "<ul>";
echo "<li><a href='db-test.php'>Database Test</a> - Full database testing</li>";
echo "<li><a href='create-admin.php'>Create Admin</a> - Simple admin creation</li>";
echo "<li><a href='login-test.php'>Login Test</a> - Test login functionality</li>";
echo "<li><a href='index.php'>Admin Panel</a> - Try accessing admin</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Password fix completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
