<?php
/**
 * Get Counts AJAX Handler
 * Returns cart and wishlist counts for header updates
 * 
 * @security CSRF protection and rate limiting
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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// Rate limiting
if (!Security::checkRateLimit('get_counts', 60, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

try {
    // Get cart count
    $cartCount = getCartCount();
    
    // Get wishlist count (only for logged-in users)
    $wishlistCount = 0;
    if ($auth->isLoggedIn()) {
        $wishlistCount = getWishlistCount();
    }
    
    echo json_encode([
        'success' => true,
        'cart_count' => $cartCount,
        'wishlist_count' => $wishlistCount
    ]);
    
} catch (Exception $e) {
    error_log("Get counts failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get counts']);
}

/**
 * Get cart count
 */
function getCartCount() {
    global $db, $auth;
    
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    if ($userId) {
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
        $params = [$userId];
    } else {
        $query = "SELECT SUM(quantity) as count FROM cart WHERE session_id = ?";
        $params = [$sessionId];
    }
    
    $result = $db->fetchRow($query, $params);
    return (int)($result['count'] ?? 0);
}

/**
 * Get wishlist count
 */
function getWishlistCount() {
    global $db;
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $result = $db->fetchRow($query, [$userId]);
    
    return (int)($result['count'] ?? 0);
}
?>
