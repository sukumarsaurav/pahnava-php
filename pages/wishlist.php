<?php
/**
 * Wishlist Page
 * 
 * @security Requires authentication
 */

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage('Please login to view your wishlist.', 'warning');
    redirect('?page=login&redirect=' . urlencode('?page=wishlist'));
}

// Set page title
$pageTitle = 'My Wishlist';

// Get wishlist items
$wishlistItems = getWishlistItems($_SESSION['user_id']);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">My Wishlist</h1>
                <span class="text-muted"><?php echo count($wishlistItems); ?> items</span>
            </div>
            
            <?php if (!empty($wishlistItems)): ?>
                <div class="row">
                    <?php foreach ($wishlistItems as $product): ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="wishlist-item">
                                <?php include 'includes/product-card.php'; ?>
                                
                                <!-- Remove from Wishlist Button -->
                                <div class="wishlist-actions mt-2">
                                    <button class="btn btn-outline-danger btn-sm w-100 add-to-wishlist in-wishlist" 
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-heart me-2"></i>Remove from Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Wishlist Actions -->
                <div class="wishlist-bulk-actions mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-primary" onclick="addAllToCart()">
                                <i class="fas fa-shopping-cart me-2"></i>Add All to Cart
                            </button>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button class="btn btn-outline-secondary" onclick="clearWishlist()">
                                <i class="fas fa-trash me-2"></i>Clear Wishlist
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Wishlist -->
                <div class="empty-wishlist text-center py-5">
                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                    <h4>Your wishlist is empty</h4>
                    <p class="text-muted mb-4">Save items you love to your wishlist and shop them later.</p>
                    <a href="?page=shop" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wishlist-item {
    position: relative;
}

.wishlist-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.wishlist-item:hover .wishlist-actions {
    opacity: 1;
}

.empty-wishlist {
    background: #f8f9fa;
    border-radius: 1rem;
    margin: 2rem 0;
}

@media (max-width: 767.98px) {
    .wishlist-actions {
        opacity: 1;
    }
}
</style>

<script>
function addAllToCart() {
    const wishlistItems = document.querySelectorAll('.wishlist-item');
    let addedCount = 0;
    
    wishlistItems.forEach(item => {
        const addToCartBtn = item.querySelector('.add-to-cart');
        if (addToCartBtn && !addToCartBtn.disabled) {
            addToCartBtn.click();
            addedCount++;
        }
    });
    
    if (addedCount > 0) {
        showNotification(`${addedCount} items added to cart`, 'success');
    } else {
        showNotification('No items available to add to cart', 'warning');
    }
}

function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist?')) {
        return;
    }
    
    const wishlistItems = document.querySelectorAll('.add-to-wishlist.in-wishlist');
    
    wishlistItems.forEach(btn => {
        btn.click();
    });
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}
</script>

<?php
/**
 * Get wishlist items for user
 */
function getWishlistItems($userId) {
    global $db;
    
    $query = "SELECT p.*, pi.image_url, b.name as brand_name,
                     AVG(r.rating) as avg_rating, COUNT(r.id) as review_count,
                     w.created_at as added_to_wishlist
              FROM wishlist w
              JOIN products p ON w.product_id = p.id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN brands b ON p.brand_id = b.id
              LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
              WHERE w.user_id = ? AND p.is_active = 1
              GROUP BY p.id
              ORDER BY w.created_at DESC";
    
    return $db->fetchAll($query, [$userId]);
}
?>
