<?php
/**
 * Wishlist AJAX Handler
 * Handles add/remove wishlist operations
 * 
 * @security CSRF protection, authentication required, input validation
 */

session_start();

// Include required files
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize security
Security::init();

// Set JSON response header
header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? '';

if (!Security::verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist']);
    exit;
}

// Rate limiting
if (!Security::checkRateLimit('wishlist_action', 20, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$action = Security::sanitizeInput($input['action'] ?? '');

try {
    switch ($action) {
        case 'add':
            $result = addToWishlist($input);
            break;
        case 'remove':
            $result = removeFromWishlist($input);
            break;
        case 'toggle':
            $result = toggleWishlist($input);
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Wishlist operation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed. Please try again.']);
}

/**
 * Add product to wishlist
 */
function addToWishlist($input) {
    global $db;
    
    $productId = (int)($input['product_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    // Check if product exists and is active
    $productQuery = "SELECT id, name FROM products WHERE id = ? AND is_active = 1";
    $product = $db->fetchRow($productQuery, [$productId]);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    // Check if already in wishlist
    $existingQuery = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $existing = $db->fetchRow($existingQuery, [$userId, $productId]);
    
    if ($existing) {
        return ['success' => false, 'message' => 'Product already in wishlist'];
    }
    
    // Add to wishlist
    $insertQuery = "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
    $db->execute($insertQuery, [$userId, $productId]);
    
    // Get updated wishlist count
    $wishlistCount = getWishlistCount();
    
    // Log activity
    logActivity($userId, 'wishlist_add', ['product_id' => $productId]);
    
    return [
        'success' => true,
        'message' => 'Product added to wishlist',
        'wishlist_count' => $wishlistCount
    ];
}

/**
 * Remove product from wishlist
 */
function removeFromWishlist($input) {
    global $db;
    
    $productId = (int)($input['product_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    // Remove from wishlist
    $deleteQuery = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $db->execute($deleteQuery, [$userId, $productId]);
    
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Product not found in wishlist'];
    }
    
    // Get updated wishlist count
    $wishlistCount = getWishlistCount();
    
    // Log activity
    logActivity($userId, 'wishlist_remove', ['product_id' => $productId]);
    
    return [
        'success' => true,
        'message' => 'Product removed from wishlist',
        'wishlist_count' => $wishlistCount
    ];
}

/**
 * Toggle product in wishlist (add if not exists, remove if exists)
 */
function toggleWishlist($input) {
    global $db;
    
    $productId = (int)($input['product_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    // Check if product exists and is active
    $productQuery = "SELECT id, name FROM products WHERE id = ? AND is_active = 1";
    $product = $db->fetchRow($productQuery, [$productId]);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    // Check if already in wishlist
    $existingQuery = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $existing = $db->fetchRow($existingQuery, [$userId, $productId]);
    
    if ($existing) {
        // Remove from wishlist
        $deleteQuery = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
        $db->execute($deleteQuery, [$userId, $productId]);
        
        $message = 'Product removed from wishlist';
        $action = 'removed';
        
        // Log activity
        logActivity($userId, 'wishlist_remove', ['product_id' => $productId]);
    } else {
        // Add to wishlist
        $insertQuery = "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
        $db->execute($insertQuery, [$userId, $productId]);
        
        $message = 'Product added to wishlist';
        $action = 'added';
        
        // Log activity
        logActivity($userId, 'wishlist_add', ['product_id' => $productId]);
    }
    
    // Get updated wishlist count
    $wishlistCount = getWishlistCount();
    
    return [
        'success' => true,
        'message' => $message,
        'action' => $action,
        'wishlist_count' => $wishlistCount
    ];
}

/**
 * Get wishlist count for current user
 */
function getWishlistCount() {
    global $db;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $result = $db->fetchRow($query, [$userId]);
    
    return (int)($result['count'] ?? 0);
}
?>
