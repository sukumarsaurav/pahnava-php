<?php
/**
 * Simple Admin User Creation Script
 * Creates or resets the admin user with minimal dependencies
 */

echo "<h1>👤 Create Admin User</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration.</p>";
    exit;
}

// Check if admin_users table exists
try {
    $tableCheck = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
    if (!$tableCheck) {
        echo "<p>❌ admin_users table does not exist</p>";
        echo "<p><a href='setup.php'>Run Admin Setup</a> to create the table first.</p>";
        exit;
    }
    echo "<p>✅ admin_users table exists</p>";
} catch (Exception $e) {
    echo "<p>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? 'admin');
    $password = $_POST['password'] ?? 'admin123';
    $email = trim($_POST['email'] ?? 'admin@pahnava.com');
    $firstName = trim($_POST['first_name'] ?? 'Admin');
    $lastName = trim($_POST['last_name'] ?? 'User');
    
    try {
        // Check if user already exists
        $existingQuery = "SELECT id FROM admin_users WHERE username = ?";
        $existing = $db->fetchRow($existingQuery, [$username]);
        
        if ($existing) {
            echo "<h2>🔄 Updating Existing Admin User</h2>";
            
            // Update existing user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE admin_users SET 
                           email = ?, 
                           password = ?, 
                           first_name = ?, 
                           last_name = ?, 
                           role = 'super_admin',
                           is_active = 1,
                           failed_attempts = 0, 
                           locked_until = NULL, 
                           updated_at = NOW() 
                           WHERE username = ?";
            
            $stmt = $db->getConnection()->prepare($updateQuery);
            $result = $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $username]);
            
            if ($result) {
                echo "<p>✅ Admin user updated successfully!</p>";
            } else {
                echo "<p>❌ Failed to update admin user</p>";
            }
            
        } else {
            echo "<h2>➕ Creating New Admin User</h2>";
            
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insertQuery = "INSERT INTO admin_users 
                           (username, email, password, first_name, last_name, role, is_active, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, 'super_admin', 1, NOW(), NOW())";
            
            $stmt = $db->getConnection()->prepare($insertQuery);
            $result = $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName]);
            
            if ($result) {
                $newId = $db->getConnection()->lastInsertId();
                echo "<p>✅ Admin user created successfully! ID: $newId</p>";
            } else {
                echo "<p>❌ Failed to create admin user</p>";
            }
        }
        
        // Test the password
        echo "<h2>🧪 Testing Password</h2>";
        $testQuery = "SELECT password FROM admin_users WHERE username = ?";
        $testUser = $db->fetchRow($testQuery, [$username]);
        
        if ($testUser && password_verify($password, $testUser['password'])) {
            echo "<p>✅ Password verification successful!</p>";
        } else {
            echo "<p>❌ Password verification failed!</p>";
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Admin User Ready!</h4>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<div style='margin-top: 15px;'>";
        echo "<a href='index.php?page=login' class='btn btn-success' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>Try Login</a>";
        echo "<a href='login-test.php' class='btn btn-info' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Test Login</a>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    // Show current admin users
    try {
        $adminsQuery = "SELECT username, email, first_name, last_name, role, is_active, created_at FROM admin_users";
        $admins = $db->fetchAll($adminsQuery);
        
        if (!empty($admins)) {
            echo "<h2>👥 Current Admin Users</h2>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='padding: 8px;'>Username</th>";
            echo "<th style='padding: 8px;'>Email</th>";
            echo "<th style='padding: 8px;'>Name</th>";
            echo "<th style='padding: 8px;'>Role</th>";
            echo "<th style='padding: 8px;'>Active</th>";
            echo "<th style='padding: 8px;'>Created</th>";
            echo "</tr>";
            
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($admin['username']) . "</strong></td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($admin['email']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($admin['role']) . "</td>";
                echo "<td style='padding: 8px;'>" . ($admin['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
                echo "<td style='padding: 8px;'>" . date('M j, Y', strtotime($admin['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No admin users found.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error loading admin users: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Show form
    ?>
    <h2>➕ Create/Update Admin User</h2>
    <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px; max-width: 500px;">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Username:</label>
            <input type="text" name="username" value="admin" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password:</label>
            <input type="text" name="password" value="admin123" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            <small style="color: #666;">Visible for easy setup - change after login</small>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
            <input type="email" name="email" value="admin@pahnava.com" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">First Name:</label>
            <input type="text" name="first_name" value="Admin" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Last Name:</label>
            <input type="text" name="last_name" value="User" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
        </div>
        
        <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;">
            Create/Update Admin User
        </button>
    </form>
    <?php
}

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Troubleshooting Tools:</h4>";
echo "<ul>";
echo "<li><a href='db-test.php'>Database Test</a> - Test database operations</li>";
echo "<li><a href='setup.php'>Admin Setup</a> - Create admin tables</li>";
echo "<li><a href='login-test.php'>Login Test</a> - Test login functionality</li>";
echo "<li><a href='index.php'>Admin Panel</a> - Try accessing admin</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Admin creation completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
