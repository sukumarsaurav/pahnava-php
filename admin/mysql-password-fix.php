<?php
/**
 * MySQL-Specific Password Fix Script
 * Optimized for MySQL database with proper password hashing
 */

echo "<h1>🔑 MySQL Password Fix</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ MySQL database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Show MySQL version
try {
    $versionQuery = "SELECT VERSION() as mysql_version";
    $versionResult = $db->fetchRow($versionQuery);
    echo "<p><strong>MySQL Version:</strong> " . $versionResult['mysql_version'] . "</p>";
} catch (Exception $e) {
    echo "<p>MySQL version check failed</p>";
}

// Get current admin user
try {
    $adminQuery = "SELECT * FROM admin_users WHERE username = 'admin'";
    $admin = $db->fetchRow($adminQuery);
    
    if (!$admin) {
        echo "<p>❌ Admin user not found</p>";
        
        // Create admin user if not exists
        echo "<h2>➕ Creating Admin User</h2>";
        
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO admin_users 
                       (username, email, password, first_name, last_name, role, is_active, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->getConnection()->prepare($insertQuery);
        $result = $stmt->execute([
            'admin',
            'admin@pahnava.com',
            $hashedPassword,
            'Admin',
            'User',
            'super_admin',
            1
        ]);
        
        if ($result) {
            $newId = $db->getConnection()->lastInsertId();
            echo "<p>✅ Admin user created successfully! ID: $newId</p>";
            
            // Get the newly created user
            $admin = $db->fetchRow($adminQuery);
        } else {
            echo "<p>❌ Failed to create admin user</p>";
            exit;
        }
    }
    
    echo "<p>✅ Admin user found: " . htmlspecialchars($admin['username']) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error with admin user: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test current password
echo "<h2>🧪 Testing Current Password</h2>";
$testPassword = 'admin123';

echo "<p><strong>Current password hash:</strong> " . substr($admin['password'], 0, 60) . "...</p>";
echo "<p><strong>Hash length:</strong> " . strlen($admin['password']) . " characters</p>";

if (password_verify($testPassword, $admin['password'])) {
    echo "<p>✅ Current password works perfectly!</p>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
    echo "<h4>🎉 Password is Working!</h4>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Status:</strong> Ready to login</p>";
    echo "<div style='margin-top: 15px;'>";
    echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;'>🚀 Try Login Now</a>";
    echo "<a href='login-test.php' style='background: #17a2b8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Test Login</a>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p>❌ Current password verification failed - fixing now...</p>";
    
    // Fix the password with MySQL-optimized approach
    echo "<h2>🔧 Fixing Password for MySQL</h2>";
    
    try {
        // Test different password hashing methods
        echo "<h3>Testing Password Hash Methods:</h3>";
        
        $methods = [];
        
        // Method 1: PASSWORD_DEFAULT (recommended for MySQL)
        $hash1 = password_hash($testPassword, PASSWORD_DEFAULT);
        $test1 = password_verify($testPassword, $hash1);
        $methods['PASSWORD_DEFAULT'] = ['hash' => $hash1, 'works' => $test1];
        echo "<p>✅ PASSWORD_DEFAULT: " . ($test1 ? 'Works' : 'Failed') . " (Length: " . strlen($hash1) . ")</p>";
        
        // Method 2: PASSWORD_BCRYPT (MySQL compatible)
        $hash2 = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $test2 = password_verify($testPassword, $hash2);
        $methods['PASSWORD_BCRYPT'] = ['hash' => $hash2, 'works' => $test2];
        echo "<p>✅ PASSWORD_BCRYPT: " . ($test2 ? 'Works' : 'Failed') . " (Length: " . strlen($hash2) . ")</p>";
        
        // Method 3: Simple MD5 (fallback, not recommended but works)
        $hash3 = md5($testPassword);
        $methods['MD5'] = ['hash' => $hash3, 'works' => true]; // MD5 always "works" but not secure
        echo "<p>⚠️ MD5 (fallback): Available (Length: " . strlen($hash3) . ") - Not recommended</p>";
        
        // Choose the best method
        $chosenMethod = 'PASSWORD_DEFAULT';
        $chosenHash = $hash1;
        
        if (!$test1 && $test2) {
            $chosenMethod = 'PASSWORD_BCRYPT';
            $chosenHash = $hash2;
        }
        
        echo "<p><strong>✅ Using method:</strong> $chosenMethod</p>";
        echo "<p><strong>New hash:</strong> " . substr($chosenHash, 0, 60) . "...</p>";
        
        // Update password in MySQL database
        echo "<h3>Updating Password in MySQL Database:</h3>";
        
        $updateQuery = "UPDATE admin_users SET 
                       password = ?, 
                       failed_attempts = 0, 
                       locked_until = NULL, 
                       updated_at = NOW() 
                       WHERE username = 'admin'";
        
        $stmt = $db->getConnection()->prepare($updateQuery);
        $updateResult = $stmt->execute([$chosenHash]);
        
        if ($updateResult) {
            $rowsAffected = $stmt->rowCount();
            echo "<p>✅ Password updated in MySQL database (Rows affected: $rowsAffected)</p>";
            
            // Verify the update worked
            echo "<h3>Verifying Password Fix:</h3>";
            
            $verifyQuery = "SELECT password FROM admin_users WHERE username = 'admin'";
            $verifyResult = $db->fetchRow($verifyQuery);
            
            if ($verifyResult) {
                echo "<p>✅ Password retrieved from database</p>";
                echo "<p><strong>Updated hash:</strong> " . substr($verifyResult['password'], 0, 60) . "...</p>";
                
                if (password_verify($testPassword, $verifyResult['password'])) {
                    echo "<p>🎉 <strong>Password verification successful!</strong></p>";
                    
                    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
                    echo "<h4>🎉 Password Fixed Successfully!</h4>";
                    echo "<p><strong>Username:</strong> admin</p>";
                    echo "<p><strong>Password:</strong> admin123</p>";
                    echo "<p><strong>Hash Method:</strong> $chosenMethod</p>";
                    echo "<p><strong>Database:</strong> MySQL</p>";
                    echo "<div style='margin-top: 15px;'>";
                    echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;'>🚀 Login Now</a>";
                    echo "<a href='login-test.php' style='background: #17a2b8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Test Login</a>";
                    echo "</div>";
                    echo "</div>";
                    
                } else {
                    echo "<p>❌ Password verification still failed after update</p>";
                    
                    // Try alternative approach
                    echo "<h4>🔄 Trying Alternative Approach:</h4>";
                    
                    // Use simple bcrypt with lower cost
                    $simpleHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 8]);
                    
                    $altUpdateQuery = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
                    $altStmt = $db->getConnection()->prepare($altUpdateQuery);
                    $altResult = $altStmt->execute([$simpleHash]);
                    
                    if ($altResult) {
                        $altVerify = $db->fetchRow("SELECT password FROM admin_users WHERE username = 'admin'");
                        if (password_verify($testPassword, $altVerify['password'])) {
                            echo "<p>✅ Alternative method worked!</p>";
                        } else {
                            echo "<p>❌ Alternative method also failed</p>";
                        }
                    }
                }
            } else {
                echo "<p>❌ Could not retrieve updated password from database</p>";
            }
        } else {
            echo "<p>❌ Failed to update password in MySQL database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error fixing password: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Show MySQL and PHP information
echo "<h2>📋 System Information</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Database:</strong> MySQL</p>";

// Check password functions
echo "<p><strong>Password Functions Available:</strong></p>";
echo "<ul>";
echo "<li>password_hash(): " . (function_exists('password_hash') ? '✅ Available' : '❌ Not available') . "</li>";
echo "<li>password_verify(): " . (function_exists('password_verify') ? '✅ Available' : '❌ Not available') . "</li>";
echo "<li>PASSWORD_DEFAULT: " . (defined('PASSWORD_DEFAULT') ? '✅ Available (value: ' . PASSWORD_DEFAULT . ')' : '❌ Not available') . "</li>";
echo "<li>PASSWORD_BCRYPT: " . (defined('PASSWORD_BCRYPT') ? '✅ Available (value: ' . PASSWORD_BCRYPT . ')' : '❌ Not available') . "</li>";
echo "</ul>";
echo "</div>";

// Show current admin info
echo "<h2>👤 Current Admin Information</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>ID</td><td style='padding: 8px; border: 1px solid #ddd;'>" . $admin['id'] . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Username</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['username']) . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Email</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['email']) . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Name</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Role</td><td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($admin['role']) . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Active</td><td style='padding: 8px; border: 1px solid #ddd;'>" . ($admin['is_active'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Failed Attempts</td><td style='padding: 8px; border: 1px solid #ddd;'>" . $admin['failed_attempts'] . "</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>Locked Until</td><td style='padding: 8px; border: 1px solid #ddd;'>" . ($admin['locked_until'] ? $admin['locked_until'] : 'Not locked') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Additional Tools:</h4>";
echo "<ul>";
echo "<li><a href='db-test.php'>Database Test</a> - Full MySQL database testing</li>";
echo "<li><a href='create-admin.php'>Create Admin</a> - Simple admin creation</li>";
echo "<li><a href='login-test.php'>Login Test</a> - Test login functionality</li>";
echo "<li><a href='index.php'>Admin Panel</a> - Try accessing admin panel</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>MySQL password fix completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
