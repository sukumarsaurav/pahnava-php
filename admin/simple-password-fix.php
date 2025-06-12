<?php
/**
 * Simple Password Fix Script
 * Only updates the password column - no extra columns
 */

echo "<h1>🔑 Simple Password Fix</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ MariaDB database connection successful</p>";
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
        exit;
    }
    
    echo "<p>✅ Admin user found: " . htmlspecialchars($admin['username']) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error getting admin user: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test current password
echo "<h2>🧪 Testing Current Password</h2>";
$testPassword = 'admin123';

echo "<p><strong>Current password hash:</strong> " . substr($admin['password'], 0, 60) . "...</p>";
echo "<p><strong>Hash type:</strong> " . (strpos($admin['password'], '$argon2id$') === 0 ? 'Argon2ID' : 'Other') . "</p>";

if (password_verify($testPassword, $admin['password'])) {
    echo "<p>✅ Current password works perfectly!</p>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
    echo "<h4>🎉 Password is Already Working!</h4>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Status:</strong> Ready to login</p>";
    echo "<div style='margin-top: 15px;'>";
    echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;'>🚀 Try Login Now</a>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<p>❌ Current password verification failed - fixing now...</p>";
    
    // Fix the password with simple approach
    echo "<h2>🔧 Fixing Password (Simple Method)</h2>";
    
    try {
        // Create new password hash using PASSWORD_DEFAULT (which works)
        $newPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        
        echo "<p><strong>New password hash:</strong> " . substr($newPassword, 0, 60) . "...</p>";
        echo "<p><strong>Hash type:</strong> " . (strpos($newPassword, '$2y$') === 0 ? 'Bcrypt' : 'Other') . "</p>";
        
        // Test the new hash before updating
        if (password_verify($testPassword, $newPassword)) {
            echo "<p>✅ New password hash verified successfully</p>";
            
            // Update ONLY the password column
            echo "<h3>Updating Password in Database:</h3>";
            
            $updateQuery = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
            
            $stmt = $db->getConnection()->prepare($updateQuery);
            $updateResult = $stmt->execute([$newPassword]);
            
            if ($updateResult) {
                $rowsAffected = $stmt->rowCount();
                echo "<p>✅ Password updated successfully (Rows affected: $rowsAffected)</p>";
                
                // Verify the update worked
                echo "<h3>Verifying Password Fix:</h3>";
                
                $verifyQuery = "SELECT password FROM admin_users WHERE username = 'admin'";
                $verifyResult = $db->fetchRow($verifyQuery);
                
                if ($verifyResult && password_verify($testPassword, $verifyResult['password'])) {
                    echo "<p>🎉 <strong>Password verification successful!</strong></p>";
                    
                    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
                    echo "<h4>🎉 Password Fixed Successfully!</h4>";
                    echo "<p><strong>Username:</strong> admin</p>";
                    echo "<p><strong>Password:</strong> admin123</p>";
                    echo "<p><strong>Hash Method:</strong> PASSWORD_DEFAULT (Bcrypt)</p>";
                    echo "<p><strong>Database:</strong> MariaDB</p>";
                    echo "<p><strong>Status:</strong> Ready to login</p>";
                    echo "<div style='margin-top: 15px;'>";
                    echo "<a href='index.php?page=login' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;'>🚀 Login Now</a>";
                    echo "<a href='login-test.php' style='background: #17a2b8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Test Login</a>";
                    echo "</div>";
                    echo "</div>";
                    
                } else {
                    echo "<p>❌ Password verification still failed after update</p>";
                }
            } else {
                echo "<p>❌ Failed to update password in database</p>";
            }
        } else {
            echo "<p>❌ New password hash verification failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error fixing password: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Show what columns exist in the admin_users table
echo "<h2>📋 Database Table Information</h2>";
try {
    $columnsQuery = "SHOW COLUMNS FROM admin_users";
    $columns = $db->fetchAll($columnsQuery);
    
    echo "<p><strong>Available columns in admin_users table:</strong></p>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #e9ecef;'>";
    echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Column</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Type</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Null</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Key</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Default</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Check if failed_attempts column exists
    $hasFailedAttempts = false;
    $hasLockedUntil = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'failed_attempts') $hasFailedAttempts = true;
        if ($column['Field'] === 'locked_until') $hasLockedUntil = true;
    }
    
    echo "<p><strong>Missing columns that caused the error:</strong></p>";
    echo "<ul>";
    echo "<li>failed_attempts: " . ($hasFailedAttempts ? '✅ Exists' : '❌ Missing') . "</li>";
    echo "<li>locked_until: " . ($hasLockedUntil ? '✅ Exists' : '❌ Missing') . "</li>";
    echo "</ul>";
    
    if (!$hasFailedAttempts || !$hasLockedUntil) {
        echo "<p><em>Note: These columns are optional and not required for basic login functionality.</em></p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error getting table information: " . htmlspecialchars($e->getMessage()) . "</p>";
}

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
echo "</table>";
echo "</div>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Next Steps:</h4>";
echo "<ul>";
echo "<li><a href='index.php?page=login'>Try Admin Login</a> - Test the login with admin/admin123</li>";
echo "<li><a href='login-test.php'>Login Test</a> - Detailed login testing</li>";
echo "<li><a href='db-test.php'>Database Test</a> - Full database testing</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Simple password fix completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
