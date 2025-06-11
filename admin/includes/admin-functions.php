<?php
/**
 * Admin Helper Functions
 * 
 * @security All functions include proper validation and sanitization
 */

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $db;
    
    $stats = [];
    
    // Total orders
    $query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders";
    $result = $db->fetchRow($query);
    $stats['total_orders'] = $result['count'] ?? 0;
    $stats['total_revenue'] = $result['total'] ?? 0;
    
    // Orders today
    $query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $result = $db->fetchRow($query);
    $stats['orders_today'] = $result['count'] ?? 0;
    $stats['revenue_today'] = $result['total'] ?? 0;
    
    // Total customers
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
    $result = $db->fetchRow($query);
    $stats['total_customers'] = $result['count'] ?? 0;
    
    // Total products
    $query = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
    $result = $db->fetchRow($query);
    $stats['total_products'] = $result['count'] ?? 0;
    
    // Low stock products
    $query = "SELECT COUNT(*) as count FROM products WHERE inventory_quantity <= low_stock_threshold AND is_active = 1";
    $result = $db->fetchRow($query);
    $stats['low_stock_products'] = $result['count'] ?? 0;
    
    // Pending orders
    $query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $result = $db->fetchRow($query);
    $stats['pending_orders'] = $result['count'] ?? 0;
    
    return $stats;
}

/**
 * Get recent orders
 */
function getRecentOrders($limit = 10) {
    global $db;
    
    $query = "SELECT o.*, u.first_name, u.last_name, u.email 
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              ORDER BY o.created_at DESC
              LIMIT ?";
    
    return $db->fetchAll($query, [$limit]);
}

/**
 * Get sales data for chart
 */
function getSalesData($days = 30) {
    global $db;
    
    $query = "SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
              FROM orders 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY DATE(created_at)
              ORDER BY date ASC";
    
    return $db->fetchAll($query, [$days]);
}

/**
 * Get top selling products
 */
function getTopSellingProducts($limit = 10) {
    global $db;
    
    $query = "SELECT p.id, p.name, p.price, pi.image_url, SUM(oi.quantity) as total_sold
              FROM products p
              LEFT JOIN order_items oi ON p.id = oi.product_id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              WHERE p.is_active = 1
              GROUP BY p.id
              ORDER BY total_sold DESC
              LIMIT ?";
    
    return $db->fetchAll($query, [$limit]);
}

/**
 * Format admin currency
 */
function formatAdminCurrency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Get order status badge class
 */
function getOrderStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'secondary'
    ];
    
    return $badges[$status] ?? 'secondary';
}

/**
 * Generate admin pagination
 */
function generateAdminPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevUrl = $baseUrl . '&page=' . ($currentPage - 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($prevUrl) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pageUrl = $baseUrl . '&page=' . $i;
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($pageUrl) . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextUrl = $baseUrl . '&page=' . ($currentPage + 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($nextUrl) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Upload admin file
 */
function uploadAdminFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Validate file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    // Create directory if it doesn't exist
    $uploadDir = "../uploads/$directory/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => "uploads/$directory/$filename"
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to upload file'];
}

/**
 * Delete admin file
 */
function deleteAdminFile($filepath) {
    $fullPath = "../$filepath";
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Get all categories for dropdown
 */
function getAllCategories() {
    global $db;
    
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC";
    return $db->fetchAll($query);
}

/**
 * Get all brands for dropdown
 */
function getAllBrands() {
    global $db;
    
    $query = "SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC";
    return $db->fetchAll($query);
}

/**
 * Log admin activity
 */
function logAdminActivity($action, $details = []) {
    global $db;
    
    if (!isset($_SESSION['admin_id'])) {
        return;
    }
    
    try {
        $query = "INSERT INTO admin_activity_logs (admin_id, action, details, ip_address, user_agent, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $_SESSION['admin_id'],
            $action,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $db->execute($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

/**
 * Generate SKU
 */
function generateSKU($productName, $categoryId = null) {
    // Get category prefix
    $prefix = 'PRD';
    if ($categoryId) {
        global $db;
        $query = "SELECT name FROM categories WHERE id = ?";
        $category = $db->fetchRow($query, [$categoryId]);
        if ($category) {
            $prefix = strtoupper(substr($category['name'], 0, 3));
        }
    }
    
    // Generate unique part
    $unique = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 3));
    $timestamp = substr(time(), -4);
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    return $prefix . '-' . $unique . '-' . $timestamp . $random;
}

/**
 * Validate admin CSRF token
 */
function validateAdminCSRF($token) {
    return Security::verifyCSRFToken($token);
}

/**
 * Set admin flash message
 */
function setAdminFlashMessage($message, $type = 'info') {
    $_SESSION['admin_flash_message'] = $message;
    $_SESSION['admin_flash_type'] = $type;
}

/**
 * Get and clear admin flash message
 */
function getAdminFlashMessage() {
    if (isset($_SESSION['admin_flash_message'])) {
        $message = $_SESSION['admin_flash_message'];
        $type = $_SESSION['admin_flash_type'] ?? 'info';
        
        unset($_SESSION['admin_flash_message']);
        unset($_SESSION['admin_flash_type']);
        
        return ['message' => $message, 'type' => $type];
    }
    
    return null;
}
?>
