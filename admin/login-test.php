<?php
/**
 * Login Test Script
 * Tests the login functionality step by step
 */

session_start();

// Include required files
require_once '../config/database.php';
require_once '../includes/security.php';
require_once 'includes/admin-auth.php';
require_once 'includes/admin-functions.php';

echo "<h1>🔐 Login Test</h1>";

// Initialize security
Security::init();

// Test 1: Check admin user exists
echo "<h2>Test 1: Admin User Check</h2>";
try {
    $adminQuery = "SELECT * FROM admin_users WHERE username = 'admin'";
    $admin = $db->fetchRow($adminQuery);
    
    if ($admin) {
        echo "<p>✅ Admin user found</p>";
        echo "<ul>";
        echo "<li>Username: " . htmlspecialchars($admin['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($admin['email']) . "</li>";
        echo "<li>Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "</li>";
        echo "<li>Failed Attempts: " . $admin['failed_attempts'] . "</li>";
        echo "<li>Locked Until: " . ($admin['locked_until'] ? $admin['locked_until'] : 'Not locked') . "</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Admin user not found</p>";
        echo "<p><a href='reset-admin.php'>Create Admin User</a></p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Password verification
echo "<h2>Test 2: Password Verification</h2>";
$testPassword = 'admin123';

if (password_verify($testPassword, $admin['password'])) {
    echo "<p>✅ Password verification successful</p>";
} else {
    echo "<p>❌ Password verification failed</p>";
    echo "<p>Password hash: " . substr($admin['password'], 0, 50) . "...</p>";
    echo "<p><a href='reset-admin.php'>Reset Password</a></p>";
}

// Test 3: AdminAuth class
echo "<h2>Test 3: AdminAuth Class Test</h2>";
try {
    $adminAuth = new AdminAuth($db);
    echo "<p>✅ AdminAuth class initialized</p>";
    
    // Test login
    $loginResult = $adminAuth->login('admin', 'admin123', false);
    
    if ($loginResult['success']) {
        echo "<p>✅ Login successful!</p>";
        echo "<p>Admin ID: " . $_SESSION['admin_id'] . "</p>";
        echo "<p>Admin Username: " . $_SESSION['admin_username'] . "</p>";
        
        // Test if logged in
        if ($adminAuth->isLoggedIn()) {
            echo "<p>✅ isLoggedIn() returns true</p>";
        } else {
            echo "<p>❌ isLoggedIn() returns false</p>";
        }
        
        // Get current admin
        $currentAdmin = $adminAuth->getCurrentAdmin();
        if ($currentAdmin) {
            echo "<p>✅ getCurrentAdmin() successful</p>";
            echo "<p>Current admin: " . htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']) . "</p>";
        } else {
            echo "<p>❌ getCurrentAdmin() failed</p>";
        }
        
        // Logout for clean test
        $adminAuth->logout();
        echo "<p>✅ Logout successful</p>";
        
    } else {
        echo "<p>❌ Login failed: " . htmlspecialchars($loginResult['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ AdminAuth error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: CSRF Token
echo "<h2>Test 4: CSRF Token Test</h2>";
try {
    $csrfToken = Security::getCSRFToken();
    echo "<p>✅ CSRF token generated: " . substr($csrfToken, 0, 20) . "...</p>";
    
    if (Security::verifyCSRFToken($csrfToken)) {
        echo "<p>✅ CSRF token verification successful</p>";
    } else {
        echo "<p>❌ CSRF token verification failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ CSRF error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Session test
echo "<h2>Test 5: Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . " (1=disabled, 2=active)</p>";

if (isset($_SESSION)) {
    echo "<p>✅ Session is working</p>";
    echo "<p>Session variables: " . count($_SESSION) . "</p>";
} else {
    echo "<p>❌ Session not working</p>";
}

// Test 6: Manual login form test
echo "<h2>Test 6: Manual Login Form</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    echo "<h3>Processing Login Form...</h3>";
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password: " . (empty($password) ? 'Empty' : 'Provided') . "</p>";
    echo "<p>CSRF Token: " . (empty($csrfToken) ? 'Empty' : 'Provided') . "</p>";
    
    if (Security::verifyCSRFToken($csrfToken)) {
        echo "<p>✅ CSRF token valid</p>";
        
        $adminAuth = new AdminAuth($db);
        $loginResult = $adminAuth->login($username, $password, false);
        
        if ($loginResult['success']) {
            echo "<p>✅ Manual login test successful!</p>";
            $adminAuth->logout(); // Clean up
        } else {
            echo "<p>❌ Manual login test failed: " . htmlspecialchars($loginResult['error']) . "</p>";
        }
    } else {
        echo "<p>❌ CSRF token invalid</p>";
    }
}

?>

<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 20px;">
    <h4>Test Login Form</h4>
    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
    
    <div style="margin-bottom: 10px;">
        <label>Username:</label><br>
        <input type="text" name="username" value="admin" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="password" value="admin123" style="width: 200px; padding: 5px;">
    </div>
    
    <button type="submit" name="test_login" style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 3px;">
        Test Login
    </button>
</form>

<div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;">
    <h4>Troubleshooting Links:</h4>
    <ul>
        <li><a href="reset-admin.php">Reset Admin User</a></li>
        <li><a href="index.php?page=login">Try Main Login</a></li>
        <li><a href="simple.php">Try Simple Admin</a></li>
        <li><a href="function-test.php">Function Test</a></li>
    </ul>
</div>

<?php
echo "<hr>";
echo "<p><small>Test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
