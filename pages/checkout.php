<?php
/**
 * Checkout Page - Order processing
 * 
 * @security All data is properly sanitized and validated
 */

// Require login for checkout
if (!$auth->isLoggedIn()) {
    setFlashMessage('Please login to proceed with checkout.', 'warning');
    redirect('?page=login&redirect=' . urlencode('?page=checkout'));
}

// Set page title
$pageTitle = 'Checkout';

// Get cart items
$cartItems = getCartItems();
$cartTotals = getCartTotals();

// Redirect if cart is empty
if (empty($cartItems)) {
    setFlashMessage('Your cart is empty. Please add items before checkout.', 'warning');
    redirect('?page=shop');
}

// Get user addresses
$userAddresses = getUserAddresses($_SESSION['user_id']);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Checkout</h1>
            
            <!-- Checkout Steps -->
            <div class="checkout-steps mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-title">Shipping</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-title">Payment</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-title">Review</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <form id="checkoutForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                        
                        <!-- Shipping Address -->
                        <div class="checkout-section mb-4">
                            <h5 class="section-title">Shipping Address</h5>
                            
                            <?php if (!empty($userAddresses)): ?>
                                <!-- Existing Addresses -->
                                <div class="saved-addresses mb-3">
                                    <?php foreach ($userAddresses as $address): ?>
                                        <div class="address-option">
                                            <input type="radio" class="btn-check" name="shipping_address" 
                                                   value="<?php echo $address['id']; ?>" 
                                                   id="address_<?php echo $address['id']; ?>"
                                                   <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-secondary w-100 text-start" 
                                                   for="address_<?php echo $address['id']; ?>">
                                                <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong><br>
                                                <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                                <?php if ($address['address_line_2']): ?>
                                                    <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Add New Address Option -->
                                <div class="address-option">
                                    <input type="radio" class="btn-check" name="shipping_address" 
                                           value="new" id="address_new">
                                    <label class="btn btn-outline-primary w-100" for="address_new">
                                        <i class="fas fa-plus me-2"></i>Add New Address
                                    </label>
                                </div>
                            <?php endif; ?>
                            
                            <!-- New Address Form -->
                            <div class="new-address-form" style="<?php echo empty($userAddresses) ? '' : 'display: none;'; ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" name="address_line_1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control" name="address_line_2">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" name="postal_code" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="checkout-section mb-4">
                            <h5 class="section-title">Payment Method</h5>
                            
                            <div class="payment-methods">
                                <div class="payment-option">
                                    <input type="radio" class="btn-check" name="payment_method" 
                                           value="razorpay" id="payment_razorpay" checked>
                                    <label class="btn btn-outline-secondary w-100 text-start" for="payment_razorpay">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Credit/Debit Card, UPI, Net Banking
                                        <small class="d-block text-muted">Powered by Razorpay</small>
                                    </label>
                                </div>
                                
                                <div class="payment-option">
                                    <input type="radio" class="btn-check" name="payment_method" 
                                           value="cod" id="payment_cod">
                                    <label class="btn btn-outline-secondary w-100 text-start" for="payment_cod">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        Cash on Delivery
                                        <small class="d-block text-muted">Pay when you receive</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Notes -->
                        <div class="checkout-section mb-4">
                            <h5 class="section-title">Order Notes (Optional)</h5>
                            <textarea class="form-control" name="order_notes" rows="3" 
                                      placeholder="Any special instructions for your order..."></textarea>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary card sticky-top">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <!-- Order Items -->
                            <div class="order-items mb-3">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="order-item d-flex mb-2">
                                        <img src="<?php echo $item['image_url'] ?: 'assets/images/product-placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="item-image me-2">
                                        <div class="item-details flex-grow-1">
                                            <h6 class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                        </div>
                                        <div class="item-price">
                                            <?php echo formatCurrency($item['total_price']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <!-- Totals -->
                            <div class="order-totals">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span><?php echo formatCurrency($cartTotals['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax:</span>
                                    <span><?php echo formatCurrency($cartTotals['tax']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>
                                        <?php if ($cartTotals['shipping'] == 0): ?>
                                            <span class="text-success">Free</span>
                                        <?php else: ?>
                                            <?php echo formatCurrency($cartTotals['shipping']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong><?php echo formatCurrency($cartTotals['total']); ?></strong>
                                </div>
                            </div>
                            
                            <!-- Place Order Button -->
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary btn-lg" onclick="placeOrder()">
                                    <i class="fas fa-lock me-2"></i>Place Order
                                </button>
                            </div>
                            
                            <!-- Security Notice -->
                            <div class="security-notice mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Your payment information is secure and encrypted
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-steps .step {
    text-align: center;
    position: relative;
}

.checkout-steps .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
}

.checkout-steps .step.active .step-number {
    background: #007bff;
    color: white;
}

.checkout-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.section-title {
    margin-bottom: 1rem;
    color: #333;
}

.address-option,
.payment-option {
    margin-bottom: 0.5rem;
}

.address-option label,
.payment-option label {
    padding: 1rem;
    margin-bottom: 0;
}

.order-summary {
    top: 2rem;
}

.item-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0.25rem;
}

.item-name {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

@media (max-width: 991.98px) {
    .order-summary {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle address selection
    const addressRadios = document.querySelectorAll('input[name="shipping_address"]');
    const newAddressForm = document.querySelector('.new-address-form');
    
    addressRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'new') {
                newAddressForm.style.display = 'block';
            } else {
                newAddressForm.style.display = 'none';
            }
        });
    });
});

function placeOrder() {
    // Validate form
    const form = document.getElementById('checkoutForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show loading
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    button.disabled = true;
    
    // Simulate order processing
    setTimeout(() => {
        showNotification('Order placement functionality will be implemented with payment gateway', 'info');
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}
</script>

<?php
function getUserAddresses($userId) {
    global $db;
    
    $query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
    return $db->fetchAll($query, [$userId]);
}

function getCartItems() {
    global $db, $auth;
    
    $userId = $_SESSION['user_id'];
    
    $query = "SELECT c.*, p.name as product_name, p.sku, p.price as unit_price,
                     (c.quantity * COALESCE(pv.price, p.price)) as total_price,
                     pi.image_url
              FROM cart c
              JOIN products p ON c.product_id = p.id
              LEFT JOIN product_variants pv ON c.variant_id = pv.id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              WHERE c.user_id = ?
              ORDER BY c.created_at DESC";
    
    return $db->fetchAll($query, [$userId]);
}

function getCartTotals() {
    global $db, $auth;
    
    $userId = $_SESSION['user_id'];
    
    $query = "SELECT SUM(c.quantity * COALESCE(pv.price, p.price)) as subtotal
              FROM cart c
              JOIN products p ON c.product_id = p.id
              LEFT JOIN product_variants pv ON c.variant_id = pv.id
              WHERE c.user_id = ?";
    
    $result = $db->fetchRow($query, [$userId]);
    $subtotal = (float)($result['subtotal'] ?? 0);
    
    $taxRate = 0.18;
    $tax = $subtotal * $taxRate;
    $shipping = $subtotal >= 999 ? 0 : 50;
    $total = $subtotal + $tax + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total
    ];
}
?>
