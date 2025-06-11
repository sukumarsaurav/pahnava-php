<?php
/**
 * Orders Page - User order history
 * 
 * @security Requires authentication
 */

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage('Please login to view your orders.', 'warning');
    redirect('?page=login&redirect=' . urlencode('?page=orders'));
}

// Set page title
$pageTitle = 'My Orders';

// Get user orders
$orders = getUserOrders($_SESSION['user_id']);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">My Orders</h1>
            
            <?php if (!empty($orders)): ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card card mb-4">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="badge bg-<?php echo getStatusColor($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6>Items Ordered:</h6>
                                        <div class="order-items">
                                            <!-- Order items would be loaded here -->
                                            <p class="text-muted">Order details will be implemented</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="order-actions">
                                            <a href="?page=order-details&id=<?php echo $order['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                View Details
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-danger btn-sm ms-2" 
                                                        onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Orders -->
                <div class="no-orders text-center py-5">
                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                    <h4>No orders yet</h4>
                    <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                    <a href="?page=shop" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.order-card {
    border: 1px solid #e9ecef;
    transition: box-shadow 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.no-orders {
    background: #f8f9fa;
    border-radius: 1rem;
    margin: 2rem 0;
}

.order-actions .btn {
    margin-bottom: 0.5rem;
}

@media (max-width: 767.98px) {
    .card-header .row > div {
        margin-bottom: 0.5rem;
    }
    
    .order-actions {
        margin-top: 1rem;
    }
}
</style>

<script>
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    // Implement order cancellation
    showNotification('Order cancellation functionality will be implemented', 'info');
}
</script>

<?php
/**
 * Get user orders
 */
function getUserOrders($userId) {
    global $db;
    
    $query = "SELECT * FROM orders 
              WHERE user_id = ? 
              ORDER BY created_at DESC";
    
    return $db->fetchAll($query, [$userId]);
}

/**
 * Get status color for badge
 */
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'secondary'
    ];
    
    return $colors[$status] ?? 'secondary';
}
?>
