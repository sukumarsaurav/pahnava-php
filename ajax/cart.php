<?php
/**
 * Cart AJAX Handler
 * Handles add, update, remove cart operations
 * 
 * @security CSRF protection, input validation, and rate limiting
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

// Rate limiting
if (!Security::checkRateLimit('cart_action', 30, 60)) {
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
            $result = addToCart($input);
            break;
        case 'update':
            $result = updateCartItem($input);
            break;
        case 'remove':
            $result = removeFromCart($input);
            break;
        case 'clear':
            $result = clearCart();
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Cart operation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed. Please try again.']);
}

/**
 * Add product to cart
 */
function addToCart($input) {
    global $db, $auth;
    
    $productId = (int)($input['product_id'] ?? 0);
    $variantId = !empty($input['variant_id']) ? (int)$input['variant_id'] : null;
    $quantity = (int)($input['quantity'] ?? 1);
    
    // Validate input
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID'];
    }
    
    if ($quantity <= 0 || $quantity > 10) {
        return ['success' => false, 'message' => 'Invalid quantity (1-10 allowed)'];
    }
    
    // Check if product exists and is active
    $productQuery = "SELECT id, name, price, inventory_quantity, track_inventory 
                     FROM products 
                     WHERE id = ? AND is_active = 1";
    $product = $db->fetchRow($productQuery, [$productId]);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    // Check variant if specified
    $variant = null;
    if ($variantId) {
        $variantQuery = "SELECT id, price, inventory_quantity 
                         FROM product_variants 
                         WHERE id = ? AND product_id = ? AND is_active = 1";
        $variant = $db->fetchRow($variantQuery, [$variantId, $productId]);
        
        if (!$variant) {
            return ['success' => false, 'message' => 'Product variant not found'];
        }
    }
    
    // Check inventory
    $availableQuantity = $variant ? $variant['inventory_quantity'] : $product['inventory_quantity'];
    $trackInventory = $product['track_inventory'];
    
    if ($trackInventory && $availableQuantity < $quantity) {
        return ['success' => false, 'message' => 'Insufficient stock available'];
    }
    
    // Get user ID or session ID
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    // Check if item already exists in cart
    $existingQuery = "SELECT id, quantity FROM cart 
                      WHERE product_id = ? AND variant_id " . ($variantId ? "= ?" : "IS NULL");
    $params = [$productId];
    
    if ($variantId) {
        $params[] = $variantId;
    }
    
    if ($userId) {
        $existingQuery .= " AND user_id = ?";
        $params[] = $userId;
    } else {
        $existingQuery .= " AND session_id = ?";
        $params[] = $sessionId;
    }
    
    $existingItem = $db->fetchRow($existingQuery, $params);
    
    if ($existingItem) {
        // Update existing item
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        // Check inventory for new quantity
        if ($trackInventory && $availableQuantity < $newQuantity) {
            return ['success' => false, 'message' => 'Cannot add more items. Insufficient stock.'];
        }
        
        $updateQuery = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $db->execute($updateQuery, [$newQuantity, $existingItem['id']]);
    } else {
        // Add new item
        $insertQuery = "INSERT INTO cart (user_id, session_id, product_id, variant_id, quantity, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
        $db->execute($insertQuery, [$userId, $sessionId, $productId, $variantId, $quantity]);
    }
    
    // Get updated cart count
    $cartCount = getCartCount();
    
    // Log activity
    if ($userId) {
        logActivity($userId, 'cart_add', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity
        ]);
    }
    
    return [
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => $cartCount
    ];
}

/**
 * Update cart item quantity
 */
function updateCartItem($input) {
    global $db, $auth;
    
    $cartItemId = (int)($input['cart_item_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    
    // Validate input
    if ($cartItemId <= 0) {
        return ['success' => false, 'message' => 'Invalid cart item ID'];
    }
    
    if ($quantity <= 0 || $quantity > 10) {
        return ['success' => false, 'message' => 'Invalid quantity (1-10 allowed)'];
    }
    
    // Get cart item with product info
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $cartQuery = "SELECT c.*, p.inventory_quantity, p.track_inventory, pv.inventory_quantity as variant_inventory
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  LEFT JOIN product_variants pv ON c.variant_id = pv.id
                  WHERE c.id = ?";
    $params = [$cartItemId];
    
    if ($userId) {
        $cartQuery .= " AND c.user_id = ?";
        $params[] = $userId;
    } else {
        $cartQuery .= " AND c.session_id = ?";
        $params[] = $sessionId;
    }
    
    $cartItem = $db->fetchRow($cartQuery, $params);
    
    if (!$cartItem) {
        return ['success' => false, 'message' => 'Cart item not found'];
    }
    
    // Check inventory
    $availableQuantity = $cartItem['variant_inventory'] ?: $cartItem['inventory_quantity'];
    
    if ($cartItem['track_inventory'] && $availableQuantity < $quantity) {
        return ['success' => false, 'message' => 'Insufficient stock available'];
    }
    
    // Update quantity
    $updateQuery = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
    $db->execute($updateQuery, [$quantity, $cartItemId]);
    
    // Get updated cart data
    $cartData = getCartData();
    $cartCount = getCartCount();
    
    // Log activity
    if ($userId) {
        logActivity($userId, 'cart_update', [
            'cart_item_id' => $cartItemId,
            'quantity' => $quantity
        ]);
    }
    
    return [
        'success' => true,
        'message' => 'Cart updated',
        'cart_data' => $cartData,
        'cart_count' => $cartCount
    ];
}

/**
 * Remove item from cart
 */
function removeFromCart($input) {
    global $db, $auth;
    
    $cartItemId = (int)($input['cart_item_id'] ?? 0);
    
    // Validate input
    if ($cartItemId <= 0) {
        return ['success' => false, 'message' => 'Invalid cart item ID'];
    }
    
    // Verify ownership
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $deleteQuery = "DELETE FROM cart WHERE id = ?";
    $params = [$cartItemId];
    
    if ($userId) {
        $deleteQuery .= " AND user_id = ?";
        $params[] = $userId;
    } else {
        $deleteQuery .= " AND session_id = ?";
        $params[] = $sessionId;
    }
    
    $stmt = $db->execute($deleteQuery, $params);
    
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Cart item not found'];
    }
    
    // Get updated cart data
    $cartData = getCartData();
    $cartCount = getCartCount();
    
    // Log activity
    if ($userId) {
        logActivity($userId, 'cart_remove', ['cart_item_id' => $cartItemId]);
    }
    
    return [
        'success' => true,
        'message' => 'Item removed from cart',
        'cart_data' => $cartData,
        'cart_count' => $cartCount
    ];
}

/**
 * Clear entire cart
 */
function clearCart() {
    global $db, $auth;
    
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    if ($userId) {
        $deleteQuery = "DELETE FROM cart WHERE user_id = ?";
        $params = [$userId];
    } else {
        $deleteQuery = "DELETE FROM cart WHERE session_id = ?";
        $params = [$sessionId];
    }
    
    $db->execute($deleteQuery, $params);
    
    // Log activity
    if ($userId) {
        logActivity($userId, 'cart_clear');
    }
    
    return [
        'success' => true,
        'message' => 'Cart cleared',
        'cart_count' => 0
    ];
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
 * Get cart data for totals
 */
function getCartData() {
    global $db, $auth;
    
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    if ($userId) {
        $query = "SELECT SUM(c.quantity * COALESCE(pv.price, p.price)) as subtotal
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  LEFT JOIN product_variants pv ON c.variant_id = pv.id
                  WHERE c.user_id = ?";
        $params = [$userId];
    } else {
        $query = "SELECT SUM(c.quantity * COALESCE(pv.price, p.price)) as subtotal
                  FROM cart c
                  JOIN products p ON c.product_id = p.id
                  LEFT JOIN product_variants pv ON c.variant_id = pv.id
                  WHERE c.session_id = ?";
        $params = [$sessionId];
    }
    
    $result = $db->fetchRow($query, $params);
    $subtotal = (float)($result['subtotal'] ?? 0);
    
    // Calculate tax and total (you can customize this logic)
    $taxRate = 0.18; // 18% GST
    $taxAmount = $subtotal * $taxRate;
    $total = $subtotal + $taxAmount;
    
    return [
        'subtotal' => number_format($subtotal, 2),
        'tax_amount' => number_format($taxAmount, 2),
        'total' => number_format($total, 2)
    ];
}
?>
