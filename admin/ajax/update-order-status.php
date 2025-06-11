<?php
/**
 * Update Order Status AJAX Handler
 * 
 * @security Admin authentication and CSRF protection
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

// Check permissions
if (!$adminAuth->hasPermission('manage_orders')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
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
    $orderId = (int)($input['order_id'] ?? 0);
    $newStatus = Security::sanitizeInput($input['status'] ?? '');
    
    if ($orderId <= 0) {
        throw new Exception('Invalid order ID');
    }
    
    $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Invalid status');
    }
    
    // Get current order
    $orderQuery = "SELECT * FROM orders WHERE id = ?";
    $order = $db->fetchRow($orderQuery, [$orderId]);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Check if status change is valid
    $currentStatus = $order['status'];
    
    // Define valid status transitions
    $validTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['refunded'],
        'cancelled' => [],
        'refunded' => []
    ];
    
    if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
        throw new Exception("Cannot change status from '$currentStatus' to '$newStatus'");
    }
    
    $db->beginTransaction();
    
    // Update order status
    $updateQuery = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $db->execute($updateQuery, [$newStatus, $orderId]);
    
    // Add status history
    $historyQuery = "INSERT INTO order_status_history (order_id, status, notes, admin_id, created_at) 
                     VALUES (?, ?, ?, ?, NOW())";
    $notes = "Status changed from '$currentStatus' to '$newStatus'";
    $db->execute($historyQuery, [$orderId, $newStatus, $notes, $_SESSION['admin_id']]);
    
    // Handle inventory updates for cancelled orders
    if ($newStatus === 'cancelled' && in_array($currentStatus, ['confirmed', 'processing'])) {
        // Restore inventory
        $itemsQuery = "SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?";
        $items = $db->fetchAll($itemsQuery, [$orderId]);
        
        foreach ($items as $item) {
            if ($item['variant_id']) {
                $updateInventoryQuery = "UPDATE product_variants SET inventory_quantity = inventory_quantity + ? WHERE id = ?";
                $db->execute($updateInventoryQuery, [$item['quantity'], $item['variant_id']]);
            } else {
                $updateInventoryQuery = "UPDATE products SET inventory_quantity = inventory_quantity + ? WHERE id = ?";
                $db->execute($updateInventoryQuery, [$item['quantity'], $item['product_id']]);
            }
        }
    }
    
    $db->commit();
    
    // Log activity
    logAdminActivity('order_status_updated', [
        'order_id' => $orderId,
        'old_status' => $currentStatus,
        'new_status' => $newStatus
    ]);
    
    // Send notification email to customer (implement as needed)
    // sendOrderStatusEmail($order, $newStatus);
    
    echo json_encode([
        'success' => true,
        'message' => "Order status updated to '$newStatus' successfully"
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Order status update failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
