<?php
/**
 * Test All Admin Pages
 * Comprehensive test for all updated admin pages
 */

echo "<h1>🧪 Admin Pages Test Suite</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>📋 Testing All Admin Pages</h2>";

// Test each admin page
$adminPages = [
    'products' => 'Products Management',
    'categories' => 'Categories Management', 
    'brands' => 'Brands Management',
    'orders' => 'Orders Management',
    'customers' => 'Customers Management',
    'inventory' => 'Inventory Management',
    'dashboard' => 'Dashboard Overview',
    'reports' => 'Reports & Analytics',
    'settings' => 'Settings Management'
];

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

foreach ($adminPages as $page => $title) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h4>$title</h4>";
    
    // Test if page file exists
    $pageFile = "pages/$page.php";
    if (file_exists($pageFile)) {
        echo "<p>✅ Page file exists: <code>$pageFile</code></p>";
        
        // Test page link
        echo "<p><a href='?page=$page' target='_blank' class='btn btn-primary btn-sm'>🔗 Test $title</a></p>";
        
        // Check for common issues
        $content = file_get_contents($pageFile);
        
        // Check for schema compatibility
        $schemaIssues = [];
        
        if ($page === 'products') {
            if (strpos($content, 'inventory_quantity') !== false) {
                echo "<p>✅ Uses correct inventory field: <code>inventory_quantity</code></p>";
            } else {
                $schemaIssues[] = "Should use 'inventory_quantity' instead of 'stock_quantity'";
            }
            
            if (strpos($content, 'is_active') !== false) {
                echo "<p>✅ Uses correct status field: <code>is_active</code></p>";
            } else {
                $schemaIssues[] = "Should use 'is_active' instead of 'status'";
            }
        }
        
        if ($page === 'categories') {
            if (strpos($content, 'is_active') !== false) {
                echo "<p>✅ Uses correct status field: <code>is_active</code></p>";
            } else {
                $schemaIssues[] = "Should use 'is_active' instead of 'status'";
            }
            
            if (strpos($content, 'meta_title') !== false) {
                echo "<p>✅ Includes SEO fields: <code>meta_title</code>, <code>meta_description</code></p>";
            }
        }
        
        if ($page === 'orders') {
            if (strpos($content, 'order_number') !== false) {
                echo "<p>✅ Uses correct order field: <code>order_number</code></p>";
            } else {
                $schemaIssues[] = "Should use 'order_number' field";
            }
        }
        
        if ($page === 'customers') {
            if (strpos($content, 'email_verified') !== false) {
                echo "<p>✅ Uses correct verification field: <code>email_verified</code></p>";
            } else {
                $schemaIssues[] = "Should use 'email_verified' instead of 'is_verified'";
            }
        }
        
        // Check for missing functions
        if (strpos($content, 'makeAjaxRequest') !== false) {
            $schemaIssues[] = "Uses undefined function 'makeAjaxRequest' - should be simplified";
        }
        
        if (strpos($content, 'formatAdminCurrency') !== false) {
            $schemaIssues[] = "Uses undefined function 'formatAdminCurrency' - should use number_format";
        }
        
        if (strpos($content, 'generateAdminPagination') !== false) {
            $schemaIssues[] = "Uses undefined function 'generateAdminPagination' - should use simple pagination";
        }
        
        if (!empty($schemaIssues)) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 3px; margin: 5px 0;'>";
            echo "<strong>⚠️ Potential Issues:</strong>";
            echo "<ul>";
            foreach ($schemaIssues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p>✅ No schema compatibility issues detected</p>";
        }
        
    } else {
        echo "<p>❌ Page file missing: <code>$pageFile</code></p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<h2>🗄️ Database Schema Verification</h2>";

// Test key database queries used in admin pages
$testQueries = [
    'Products with categories and brands' => "SELECT p.*, c.name as category_name, b.name as brand_name, pi.image_url as primary_image 
                                            FROM products p 
                                            LEFT JOIN categories c ON p.category_id = c.id 
                                            LEFT JOIN brands b ON p.brand_id = b.id 
                                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                                            LIMIT 1",
    
    'Categories with hierarchy' => "SELECT c.*, p.name as parent_name,
                                   (SELECT COUNT(*) FROM categories sc WHERE sc.parent_id = c.id) as subcategory_count,
                                   (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id AND pr.is_active = 1) as product_count
                                   FROM categories c 
                                   LEFT JOIN categories p ON c.parent_id = p.id 
                                   LIMIT 1",
    
    'Orders with customers' => "SELECT o.*, u.first_name, u.last_name, u.email,
                               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                               FROM orders o
                               LEFT JOIN users u ON o.user_id = u.id
                               LIMIT 1",
    
    'Customers with order stats' => "SELECT u.*,
                                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as total_orders,
                                    (SELECT SUM(o.total_amount) FROM orders o WHERE o.user_id = u.id) as total_spent
                                    FROM users u
                                    LIMIT 1",
    
    'Brands with product counts' => "SELECT b.*,
                                    (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) as product_count
                                    FROM brands b
                                    LIMIT 1"
];

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

foreach ($testQueries as $description => $query) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h4>$description</h4>";
    
    try {
        $result = $db->fetchRow($query);
        if ($result) {
            echo "<p>✅ Query executed successfully</p>";
            echo "<p><small>Fields returned: " . implode(', ', array_keys($result)) . "</small></p>";
        } else {
            echo "<p>⚠️ Query executed but no data found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><small>Query: <code>" . htmlspecialchars($query) . "</code></small></p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<h2>🔧 Quick Actions</h2>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>Admin Panel Navigation:</h4>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;'>";

foreach ($adminPages as $page => $title) {
    echo "<a href='?page=$page' target='_blank' style='display: block; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; text-align: center;'>";
    echo "<strong>$title</strong>";
    echo "</a>";
}

echo "</div>";

echo "<h4>Utility Tools:</h4>";
echo "<ul>";
echo "<li><a href='check-tables.php' target='_blank'>Database Tables Checker</a> - Verify table structure</li>";
echo "<li><a href='test-schema.php' target='_blank'>Schema Compatibility Test</a> - Test database queries</li>";
echo "<li><a href='db-test.php' target='_blank'>Database Connection Test</a> - Basic database test</li>";
echo "<li><a href='simple-password-fix.php' target='_blank'>Admin Password Fix</a> - Reset admin password</li>";
echo "</ul>";

echo "</div>";

echo "<hr>";
echo "<p><small>Admin pages test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
