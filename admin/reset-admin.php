<?php
/**
 * Reset Admin User Script
 * Resets the admin user password and verifies login
 */

// Include required files
require_once '../config/database.php';
require_once '../includes/security.php';

echo "<h1>🔧 Admin User Reset</h1>";

try {
    // Check if admin user exists
    $adminQuery = "SELECT * FROM admin_users WHERE username = 'admin'";
    $admin = $db->fetchRow($adminQuery);
    
    if (!$admin) {
        echo "<div style='color: red;'>❌ Admin user not found. Creating new admin user...</div>";
        
        // Create new admin user
        $password = password_hash('admin123', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $insertQuery = "INSERT INTO admin_users (username, email, password, first_name, last_name, role, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($insertQuery, [
            'admin',
            'admin@pahnava.com',
            $password,
            'Admin',
            'User',
            'super_admin',
            1
        ]);
        
        echo "<div style='color: green;'>✅ New admin user created successfully!</div>";
        
    } else {
        echo "<div style='color: green;'>✅ Admin user found:</div>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</li>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</li>";
        echo "<li><strong>Role:</strong> " . htmlspecialchars($admin['role']) . "</li>";
        echo "<li><strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "</li>";
        echo "<li><strong>Last Login:</strong> " . ($admin['last_login'] ? $admin['last_login'] : 'Never') . "</li>";
        echo "</ul>";
        
        // Reset password
        echo "<h2>🔑 Resetting Password</h2>";
        
        $newPassword = password_hash('admin123', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $updateQuery = "UPDATE admin_users SET password = ?, failed_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE username = 'admin'";
        $db->execute($updateQuery, [$newPassword]);
        
        echo "<div style='color: green;'>✅ Password reset successfully!</div>";
    }
    
    // Test password verification
    echo "<h2>🧪 Testing Password Verification</h2>";
    
    // Get updated admin user
    $admin = $db->fetchRow($adminQuery);
    
    if (password_verify('admin123', $admin['password'])) {
        echo "<div style='color: green;'>✅ Password verification successful!</div>";
    } else {
        echo "<div style='color: red;'>❌ Password verification failed!</div>";
    }
    
    // Test admin auth class
    echo "<h2>🔐 Testing Admin Auth Class</h2>";
    
    require_once 'includes/admin-auth.php';
    require_once 'includes/admin-functions.php';
    
    $adminAuth = new AdminAuth($db);
    $loginResult = $adminAuth->login('admin', 'admin123', false);
    
    if ($loginResult['success']) {
        echo "<div style='color: green;'>✅ Admin auth login test successful!</div>";
        
        // Logout to clean up
        $adminAuth->logout();
    } else {
        echo "<div style='color: red;'>❌ Admin auth login test failed: " . htmlspecialchars($loginResult['error']) . "</div>";
    }
    
    echo "<h2>📋 Summary</h2>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3;'>";
    echo "<h4>Login Credentials:</h4>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Status:</strong> Ready to use</p>";
    echo "</div>";
    
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin-top: 15px;'>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><a href='index.php?page=login'>Try Admin Login</a></li>";
    echo "<li><a href='simple.php'>Try Simple Admin</a></li>";
    echo "<li><a href='function-test.php'>Run Function Test</a></li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<hr>";
echo "<p><small>Reset completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
