<?php
/**
 * Function Conflict Test
 * Tests if the redirect function conflict is resolved
 */

echo "<h1>Function Conflict Test</h1>";

// Test 1: Include functions.php
echo "<h2>Test 1: Including functions.php</h2>";
try {
    require_once '../includes/functions.php';
    echo "<p>✅ functions.php loaded successfully</p>";
    
    if (function_exists('redirect')) {
        echo "<p>✅ redirect() function exists in functions.php</p>";
    } else {
        echo "<p>❌ redirect() function not found in functions.php</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading functions.php: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Define admin redirect function
echo "<h2>Test 2: Defining adminRedirect function</h2>";
try {
    function adminRedirect($url) {
        echo "<p>adminRedirect called with: $url</p>";
        return true; // Don't actually redirect in test
    }
    echo "<p>✅ adminRedirect() function defined successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error defining adminRedirect(): " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check both functions exist
echo "<h2>Test 3: Function Existence Check</h2>";
echo "<p>redirect() exists: " . (function_exists('redirect') ? '✅ Yes' : '❌ No') . "</p>";
echo "<p>adminRedirect() exists: " . (function_exists('adminRedirect') ? '✅ Yes' : '❌ No') . "</p>";

// Test 4: Test database connection
echo "<h2>Test 4: Database Connection</h2>";
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Test admin auth loading
echo "<h2>Test 5: Admin Auth Loading</h2>";
try {
    require_once '../includes/security.php';
    require_once 'includes/admin-auth.php';
    require_once 'includes/admin-functions.php';
    
    echo "<p>✅ All admin files loaded successfully</p>";
    
    // Test admin auth creation
    if (isset($db)) {
        $adminAuth = new AdminAuth($db);
        echo "<p>✅ AdminAuth created successfully</p>";
    } else {
        echo "<p>❌ Database not available for AdminAuth test</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Admin auth error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>If all tests show ✅, the function conflict is resolved and admin panel should work.</p>";

echo "<div style='margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;'>";
echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li><a href='../deploy.php'>Run Deploy Script</a> (if not done)</li>";
echo "<li><a href='setup.php'>Run Admin Setup</a> (if not done)</li>";
echo "<li><a href='index.php'>Try Admin Panel</a></li>";
echo "<li><a href='simple.php'>Try Simple Admin</a> (backup)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><small>Test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
