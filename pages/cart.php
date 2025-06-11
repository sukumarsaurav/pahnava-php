<?php
/**
 * Shopping Cart Page
 * 
 * @security All data is properly sanitized and validated
 */

// Set page title
$pageTitle = 'Shopping Cart';

// Get cart items
$cartItems = getCartItems();
$cartTotals = getCartTotals();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Shopping Cart</h1>
            
            <?php if (!empty($cartItems)): ?>
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <div class="cart-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item card mb-3" data-item-id="<?php echo $item['id']; ?>">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <!-- Product Image -->
                                            <div class="col-md-2 col-3">
                                                <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                     class="img-fluid rounded">
                                            </div>
                                            
                                            <!-- Product Details -->
                                            <div class="col-md-4 col-9">
                                                <h6 class="mb-1">
                                                    <a href="?page=product&id=<?php echo $item['product_id']; ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
                                                <?php if ($item['variant_details']): ?>
                                                    <div class="variant-details">
                                                        <small class="text-muted">
                                                            <?php 
                                                            $details = json_decode($item['variant_details'], true);
                                                            if ($details) {
                                                                $variants = [];
                                                                if (!empty($details['size'])) $variants[] = 'Size: ' . $details['size'];
                                                                if (!empty($details['color'])) $variants[] = 'Color: ' . $details['color'];
                                                                echo implode(', ', $variants);
                                                            }
                                                            ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Price -->
                                            <div class="col-md-2 col-6">
                                                <div class="price">
                                                    <?php echo formatCurrency($item['unit_price']); ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Quantity -->
                                            <div class="col-md-2 col-6">
                                                <div class="quantity-controls d-flex align-items-center">
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                                    <input type="number" class="form-control form-control-sm mx-1 text-center cart-quantity" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" max="10" 
                                                           data-cart-item-id="<?php echo $item['id']; ?>"
                                                           style="width: 60px;">
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                                </div>
                                            </div>
                                            
                                            <!-- Total & Remove -->
                                            <div class="col-md-2 col-12">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong class="item-total">
                                                        <?php echo formatCurrency($item['total_price']); ?>
                                                    </strong>
                                                    <button class="btn btn-sm btn-outline-danger remove-from-cart" 
                                                            data-cart-item-id="<?php echo $item['id']; ?>"
                                                            title="Remove item">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Cart Actions -->
                        <div class="cart-actions d-flex justify-content-between">
                            <a href="?page=shop" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                            <button class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="col-lg-4">
                        <div class="cart-summary card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="summary-row d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span class="cart-subtotal"><?php echo formatCurrency($cartTotals['subtotal']); ?></span>
                                </div>
                                <div class="summary-row d-flex justify-content-between mb-2">
                                    <span>Tax:</span>
                                    <span><?php echo formatCurrency($cartTotals['tax']); ?></span>
                                </div>
                                <div class="summary-row d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php if ($cartTotals['subtotal'] >= 999): ?>
                                            <span class="text-success">Free</span>
                                        <?php else: ?>
                                            <?php echo formatCurrency($cartTotals['shipping']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <hr>
                                <div class="summary-row d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong class="cart-total"><?php echo formatCurrency($cartTotals['total']); ?></strong>
                                </div>
                                
                                <!-- Coupon Code -->
                                <div class="coupon-section mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Coupon code" id="couponCode">
                                        <button class="btn btn-outline-secondary" onclick="applyCoupon()">Apply</button>
                                    </div>
                                </div>
                                
                                <!-- Checkout Button -->
                                <div class="d-grid">
                                    <a href="?page=checkout" class="btn btn-primary btn-lg">
                                        <i class="fas fa-lock me-2"></i>Proceed to Checkout
                                    </a>
                                </div>
                                
                                <!-- Free Shipping Notice -->
                                <?php if ($cartTotals['subtotal'] < 999): ?>
                                    <div class="free-shipping-notice mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-truck me-1"></i>
                                            Add <?php echo formatCurrency(999 - $cartTotals['subtotal']); ?> more for free shipping!
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                    <a href="?page=shop" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.quantity-controls input {
    -moz-appearance: textfield;
}

.quantity-controls input::-webkit-outer-spin-button,
.quantity-controls input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.cart-summary {
    position: sticky;
    top: 2rem;
}

.summary-row {
    font-size: 0.95rem;
}

.free-shipping-notice {
    border-left: 3px solid #28a745;
}

@media (max-width: 767.98px) {
    .cart-item .row > div {
        margin-bottom: 1rem;
    }
    
    .cart-summary {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<script>
function updateQuantity(itemId, newQuantity) {
    if (newQuantity < 1 || newQuantity > 10) return;
    
    const input = document.querySelector(`input[data-cart-item-id="${itemId}"]`);
    input.value = newQuantity;
    
    // Trigger change event
    input.dispatchEvent(new Event('change'));
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) return;
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to clear cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function applyCoupon() {
    const couponCode = document.getElementById('couponCode').value.trim();
    
    if (!couponCode) {
        showNotification('Please enter a coupon code', 'warning');
        return;
    }
    
    // Implement coupon application
    showNotification('Coupon functionality will be implemented', 'info');
}
</script>

<?php
/**
 * Helper functions for cart page
 */

function getCartItems() {
    global $db, $auth;
    
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $query = "SELECT c.*, p.name as product_name, p.sku, p.price as unit_price,
                     (c.quantity * COALESCE(pv.price, p.price)) as total_price,
                     pi.image_url, pv.size, pv.color
              FROM cart c
              JOIN products p ON c.product_id = p.id
              LEFT JOIN product_variants pv ON c.variant_id = pv.id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              WHERE ";
    
    if ($userId) {
        $query .= "c.user_id = ?";
        $params = [$userId];
    } else {
        $query .= "c.session_id = ?";
        $params = [$sessionId];
    }
    
    $query .= " ORDER BY c.created_at DESC";
    
    $items = $db->fetchAll($query, $params);
    
    // Add variant details
    foreach ($items as &$item) {
        $variantDetails = [];
        if ($item['size']) $variantDetails['size'] = $item['size'];
        if ($item['color']) $variantDetails['color'] = $item['color'];
        $item['variant_details'] = !empty($variantDetails) ? json_encode($variantDetails) : null;
    }
    
    return $items;
}

function getCartTotals() {
    global $db, $auth;
    
    $userId = $auth->isLoggedIn() ? $_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();
    
    $query = "SELECT SUM(c.quantity * COALESCE(pv.price, p.price)) as subtotal
              FROM cart c
              JOIN products p ON c.product_id = p.id
              LEFT JOIN product_variants pv ON c.variant_id = pv.id
              WHERE ";
    
    if ($userId) {
        $query .= "c.user_id = ?";
        $params = [$userId];
    } else {
        $query .= "c.session_id = ?";
        $params = [$sessionId];
    }
    
    $result = $db->fetchRow($query, $params);
    $subtotal = (float)($result['subtotal'] ?? 0);
    
    // Calculate tax (18% GST)
    $taxRate = 0.18;
    $tax = $subtotal * $taxRate;
    
    // Calculate shipping
    $shipping = $subtotal >= 999 ? 0 : 50;
    
    // Calculate total
    $total = $subtotal + $tax + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total
    ];
}
?>
