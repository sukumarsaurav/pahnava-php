<?php
/**
 * Test Schema Compatibility
 * Verify that admin pages work with the actual database schema
 */

echo "<h1>🧪 Schema Compatibility Test</h1>";

// Include database
try {
    require_once '../config/database.php';
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>📋 Testing Database Schema</h2>";

// Test products table structure
echo "<h3>Products Table Test</h3>";
try {
    $productTest = $db->fetchRow("SELECT p.*, c.name as category_name, pi.image_url as primary_image 
                                  FROM products p 
                                  LEFT JOIN categories c ON p.category_id = c.id 
                                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                                  LIMIT 1");
    
    if ($productTest) {
        echo "<p>✅ Products query structure is correct</p>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "Sample product fields: " . implode(', ', array_keys($productTest));
        echo "</div>";
    } else {
        echo "<p>⚠️ No products found, but table structure is correct</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Products table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test categories table
echo "<h3>Categories Table Test</h3>";
try {
    $categoryTest = $db->fetchRow("SELECT c.*, p.name as parent_name,
                                   (SELECT COUNT(*) FROM categories sc WHERE sc.parent_id = c.id) as subcategory_count,
                                   (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id AND pr.is_active = 1) as product_count
                                   FROM categories c 
                                   LEFT JOIN categories p ON c.parent_id = p.id 
                                   LIMIT 1");
    
    if ($categoryTest) {
        echo "<p>✅ Categories query structure is correct</p>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "Sample category fields: " . implode(', ', array_keys($categoryTest));
        echo "</div>";
    } else {
        echo "<p>⚠️ No categories found, but table structure is correct</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Categories table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test orders table
echo "<h3>Orders Table Test</h3>";
try {
    $orderTest = $db->fetchRow("SELECT o.*, u.first_name, u.last_name, u.email,
                                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                                FROM orders o
                                LEFT JOIN users u ON o.user_id = u.id
                                LIMIT 1");
    
    if ($orderTest) {
        echo "<p>✅ Orders query structure is correct</p>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "Sample order fields: " . implode(', ', array_keys($orderTest));
        echo "</div>";
    } else {
        echo "<p>⚠️ No orders found, but table structure is correct</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Orders table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test inventory query
echo "<h3>Inventory Query Test</h3>";
try {
    $inventoryTest = $db->fetchRow("SELECT p.*, c.name as category_name, pi.image_url as primary_image
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                    WHERE p.is_active = 1
                                    LIMIT 1");
    
    if ($inventoryTest) {
        echo "<p>✅ Inventory query structure is correct</p>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "Sample inventory fields: " . implode(', ', array_keys($inventoryTest));
        echo "</div>";
    } else {
        echo "<p>⚠️ No active products found, but query structure is correct</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Inventory query error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test admin users
echo "<h3>Admin Users Test</h3>";
try {
    $adminTest = $db->fetchRow("SELECT * FROM admin_users WHERE is_active = 1 LIMIT 1");
    
    if ($adminTest) {
        echo "<p>✅ Admin users table is correct</p>";
        echo "<p>Found admin: " . htmlspecialchars($adminTest['username']) . " (" . htmlspecialchars($adminTest['email']) . ")</p>";
    } else {
        echo "<p>⚠️ No active admin users found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin users error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>🔧 Sample Data Creation</h2>";

// Create sample data if tables are empty
if (isset($_POST['create_sample_data'])) {
    try {
        // Check if we already have data
        $productCount = $db->fetchRow("SELECT COUNT(*) as count FROM products")['count'];
        
        if ($productCount == 0) {
            echo "<h4>Creating sample products...</h4>";
            
            // Get a category ID
            $category = $db->fetchRow("SELECT id FROM categories WHERE is_active = 1 LIMIT 1");
            $categoryId = $category ? $category['id'] : 1;
            
            // Insert sample products
            $sampleProducts = [
                ['Men\'s Cotton T-Shirt', 'MENS-TSHIRT-001', 599.00, 50, 5],
                ['Women\'s Denim Jeans', 'WOMENS-JEANS-001', 1299.00, 30, 5],
                ['Kids Cotton Shirt', 'KIDS-SHIRT-001', 399.00, 25, 3],
                ['Casual Sneakers', 'SHOES-CASUAL-001', 1999.00, 15, 2],
                ['Summer Dress', 'WOMENS-DRESS-001', 899.00, 20, 3]
            ];
            
            foreach ($sampleProducts as $product) {
                $slug = strtolower(str_replace([' ', '\''], ['-', ''], $product[0]));
                $db->execute("INSERT INTO products (category_id, name, slug, sku, price, inventory_quantity, low_stock_threshold, is_active, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())", 
                             [$categoryId, $product[0], $slug, $product[1], $product[2], $product[3], $product[4]]);
                echo "<p>✅ Created product: " . htmlspecialchars($product[0]) . "</p>";
            }
            
            echo "<p><strong>Sample products created successfully!</strong></p>";
        } else {
            echo "<p>Products already exist ($productCount products found)</p>";
        }
        
        // Check orders
        $orderCount = $db->fetchRow("SELECT COUNT(*) as count FROM orders")['count'];
        if ($orderCount == 0) {
            echo "<h4>Creating sample orders...</h4>";
            
            // Create sample orders
            $sampleOrders = [
                ['ORD-001', 'pending', 'John Doe', 'john@example.com', 1299.00],
                ['ORD-002', 'confirmed', 'Jane Smith', 'jane@example.com', 899.00],
                ['ORD-003', 'shipped', 'Bob Johnson', 'bob@example.com', 1999.00]
            ];
            
            foreach ($sampleOrders as $order) {
                $db->execute("INSERT INTO orders (order_number, status, billing_first_name, billing_last_name, total_amount, subtotal, created_at) 
                             VALUES (?, ?, ?, '', ?, ?, NOW())", 
                             [$order[0], $order[1], $order[2], $order[4], $order[4]]);
                echo "<p>✅ Created order: " . htmlspecialchars($order[0]) . " for " . htmlspecialchars($order[2]) . "</p>";
            }
            
            echo "<p><strong>Sample orders created successfully!</strong></p>";
        } else {
            echo "<p>Orders already exist ($orderCount orders found)</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error creating sample data: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<form method='POST'>";
echo "<button type='submit' name='create_sample_data' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Sample Data</button>";
echo "</form>";

echo "<h2>🔗 Admin Page Links</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>Test these admin pages:</h4>";
echo "<ul>";
echo "<li><a href='?page=products' target='_blank'>Products Page</a> - Should show products with proper fields</li>";
echo "<li><a href='?page=categories' target='_blank'>Categories Page</a> - Should show categories with hierarchy</li>";
echo "<li><a href='?page=orders' target='_blank'>Orders Page</a> - Should show orders with customer info</li>";
echo "<li><a href='?page=inventory' target='_blank'>Inventory Page</a> - Should show stock levels</li>";
echo "<li><a href='?page=dashboard' target='_blank'>Dashboard</a> - Should show overview stats</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Schema test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
