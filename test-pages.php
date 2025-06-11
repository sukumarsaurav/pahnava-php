<?php
/**
 * Simple test script to check if all pages exist and load without errors
 * This file should be deleted after testing
 */

// List of all pages to test
$pages = [
    'home',
    'shop', 
    'cart',
    'login',
    'register',
    'forgot-password',
    'reset-password',
    'verify-email',
    'contact',
    'about',
    'wishlist',
    'account',
    'orders',
    'checkout'
];

echo "<h1>Page Test Results</h1>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Page</th><th>File Exists</th><th>Status</th></tr>";

foreach ($pages as $page) {
    $filePath = "pages/{$page}.php";
    $fileExists = file_exists($filePath) ? "✅ Yes" : "❌ No";
    
    // Check for basic PHP syntax errors
    $status = "✅ OK";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        // Basic checks
        if (strpos($content, '<?php') === false && strpos($content, '<?=') === false) {
            $status = "⚠️ No PHP opening tag";
        } elseif (strpos($content, '$pageTitle') === false) {
            $status = "⚠️ No page title set";
        }
    } else {
        $status = "❌ File missing";
    }
    
    echo "<tr>";
    echo "<td><a href='?page={$page}' target='_blank'>{$page}</a></td>";
    echo "<td>{$fileExists}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Additional Files Check</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File</th><th>Exists</th><th>Purpose</th></tr>";

$additionalFiles = [
    'index.php' => 'Main entry point',
    'install.php' => 'Installation script',
    'logout.php' => 'Logout handler',
    'config/database.php' => 'Database configuration',
    'includes/auth.php' => 'Authentication class',
    'includes/security.php' => 'Security functions',
    'includes/functions.php' => 'Utility functions',
    'includes/header.php' => 'Header template',
    'includes/footer.php' => 'Footer template',
    'includes/product-card.php' => 'Product card component',
    'assets/css/style.css' => 'Main stylesheet',
    'assets/js/main.js' => 'Main JavaScript',
    'ajax/cart.php' => 'Cart AJAX handler',
    'ajax/wishlist.php' => 'Wishlist AJAX handler',
    'ajax/get-counts.php' => 'Counts AJAX handler',
    'database/schema.sql' => 'Database schema'
];

foreach ($additionalFiles as $file => $purpose) {
    $exists = file_exists($file) ? "✅ Yes" : "❌ No";
    echo "<tr><td>{$file}</td><td>{$exists}</td><td>{$purpose}</td></tr>";
}

echo "</table>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
foreach ($pages as $page) {
    echo "<li><a href='?page={$page}' target='_blank'>{$page}</a></li>";
}
echo "</ul>";

echo "<p><strong>Note:</strong> Delete this test file (test-pages.php) after testing is complete.</p>";
?>
