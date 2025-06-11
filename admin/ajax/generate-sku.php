<?php
/**
 * Generate SKU AJAX Handler
 * 
 * @security Admin authentication required
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
if (!$adminAuth->hasPermission('manage_products')) {
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
    $productName = Security::sanitizeInput($input['name'] ?? '');
    $categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
    
    if (empty($productName)) {
        throw new Exception('Product name is required');
    }
    
    // Generate SKU
    $sku = generateSKU($productName, $categoryId);
    
    // Check if SKU already exists and make it unique
    $attempts = 0;
    $originalSku = $sku;
    
    while ($attempts < 10) {
        $checkQuery = "SELECT id FROM products WHERE sku = ?";
        $existing = $db->fetchRow($checkQuery, [$sku]);
        
        if (!$existing) {
            break;
        }
        
        $attempts++;
        $sku = $originalSku . '-' . str_pad($attempts, 2, '0', STR_PAD_LEFT);
    }
    
    if ($attempts >= 10) {
        throw new Exception('Unable to generate unique SKU');
    }
    
    echo json_encode([
        'success' => true,
        'sku' => $sku
    ]);
    
} catch (Exception $e) {
    error_log("SKU generation failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
