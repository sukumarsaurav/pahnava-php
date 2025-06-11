<?php
/**
 * Bulk Actions AJAX Handler
 * 
 * @security Admin authentication and permissions required
 */

session_start();

// Include required files
require_once '../../config/database.php';
require_once '../../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/admin-functions.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize admin auth
$adminAuth = new AdminAuth($db);

// Check if admin is logged in
if (!$adminAuth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate input
    $action = Security::sanitizeInput($input['action'] ?? '');
    $ids = $input['ids'] ?? [];
    
    if (empty($action)) {
        throw new Exception('Action is required');
    }
    
    if (empty($ids) || !is_array($ids)) {
        throw new Exception('No items selected');
    }
    
    // Sanitize IDs
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function($id) { return $id > 0; });
    
    if (empty($ids)) {
        throw new Exception('Invalid item IDs');
    }
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $affectedRows = 0;
    
    $db->beginTransaction();
    
    switch ($action) {
        case 'activate':
            // Check permission
            if (!$adminAuth->hasPermission('manage_products')) {
                throw new Exception('Insufficient permissions');
            }
            
            $query = "UPDATE products SET is_active = 1, updated_at = NOW() WHERE id IN ($placeholders)";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            logAdminActivity('products_bulk_activated', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows products activated successfully";
            break;
            
        case 'deactivate':
            // Check permission
            if (!$adminAuth->hasPermission('manage_products')) {
                throw new Exception('Insufficient permissions');
            }
            
            $query = "UPDATE products SET is_active = 0, updated_at = NOW() WHERE id IN ($placeholders)";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            logAdminActivity('products_bulk_deactivated', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows products deactivated successfully";
            break;
            
        case 'delete':
            // Check permission
            if (!$adminAuth->hasPermission('manage_products')) {
                throw new Exception('Insufficient permissions');
            }
            
            // Delete related data first
            $deleteImagesQuery = "DELETE FROM product_images WHERE product_id IN ($placeholders)";
            $db->execute($deleteImagesQuery, $ids);
            
            $deleteVariantsQuery = "DELETE FROM product_variants WHERE product_id IN ($placeholders)";
            $db->execute($deleteVariantsQuery, $ids);
            
            // Delete products
            $query = "DELETE FROM products WHERE id IN ($placeholders)";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            logAdminActivity('products_bulk_deleted', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows products deleted successfully";
            break;
            
        case 'mark_processing':
            // Check permission
            if (!$adminAuth->hasPermission('manage_orders')) {
                throw new Exception('Insufficient permissions');
            }
            
            $query = "UPDATE orders SET status = 'processing', updated_at = NOW() WHERE id IN ($placeholders) AND status IN ('pending', 'confirmed')";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            // Add status history for each order
            foreach ($ids as $orderId) {
                $historyQuery = "INSERT INTO order_status_history (order_id, status, notes, admin_id, created_at) 
                                VALUES (?, 'processing', 'Bulk status update', ?, NOW())";
                $db->execute($historyQuery, [$orderId, $_SESSION['admin_id']]);
            }
            
            logAdminActivity('orders_bulk_processing', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows orders marked as processing";
            break;
            
        case 'mark_shipped':
            // Check permission
            if (!$adminAuth->hasPermission('manage_orders')) {
                throw new Exception('Insufficient permissions');
            }
            
            $query = "UPDATE orders SET status = 'shipped', updated_at = NOW() WHERE id IN ($placeholders) AND status = 'processing'";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            // Add status history for each order
            foreach ($ids as $orderId) {
                $historyQuery = "INSERT INTO order_status_history (order_id, status, notes, admin_id, created_at) 
                                VALUES (?, 'shipped', 'Bulk status update', ?, NOW())";
                $db->execute($historyQuery, [$orderId, $_SESSION['admin_id']]);
            }
            
            logAdminActivity('orders_bulk_shipped', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows orders marked as shipped";
            break;
            
        case 'mark_delivered':
            // Check permission
            if (!$adminAuth->hasPermission('manage_orders')) {
                throw new Exception('Insufficient permissions');
            }
            
            $query = "UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id IN ($placeholders) AND status = 'shipped'";
            $db->execute($query, $ids);
            $affectedRows = $db->rowCount();
            
            // Add status history for each order
            foreach ($ids as $orderId) {
                $historyQuery = "INSERT INTO order_status_history (order_id, status, notes, admin_id, created_at) 
                                VALUES (?, 'delivered', 'Bulk status update', ?, NOW())";
                $db->execute($historyQuery, [$orderId, $_SESSION['admin_id']]);
            }
            
            logAdminActivity('orders_bulk_delivered', ['count' => $affectedRows, 'ids' => $ids]);
            $message = "$affectedRows orders marked as delivered";
            break;
            
        case 'export_selected':
            // This would typically generate a CSV/Excel file
            // For now, just return success
            $message = "Export functionality will be implemented";
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Bulk action failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
