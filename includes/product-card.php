<?php
/**
 * Product Card Component
 * Reusable product card for displaying products in grids
 * 
 * @param array $product Product data
 * @security All output is properly sanitized
 */

// Calculate discount percentage
$discountPercentage = 0;
if ($product['compare_price'] && $product['compare_price'] > $product['price']) {
    $discountPercentage = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
}

// Check if product is in wishlist (for logged-in users)
$isInWishlist = false;
if ($auth->isLoggedIn()) {
    $wishlistQuery = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $wishlistResult = $db->fetchRow($wishlistQuery, [$_SESSION['user_id'], $product['id']]);
    $isInWishlist = !empty($wishlistResult);
}

// Format rating
$avgRating = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
$reviewCount = $product['review_count'] ?: 0;
?>

<div class="product-card">
    <div class="product-image">
        <a href="?page=product&id=<?php echo $product['id']; ?>&slug=<?php echo urlencode($product['slug']); ?>">
            <img src="<?php echo $product['image_url'] ?: 'assets/images/product-placeholder.jpg'; ?>" 
                 alt="<?php echo Security::sanitizeInput($product['name']); ?>" 
                 class="img-fluid">
        </a>
        
        <!-- Product Badges -->
        <div class="product-badges">
            <?php if ($product['is_featured']): ?>
                <span class="badge bg-warning text-dark">Featured</span>
            <?php endif; ?>
            
            <?php if ($discountPercentage > 0): ?>
                <span class="badge bg-danger"><?php echo $discountPercentage; ?>% OFF</span>
            <?php endif; ?>
            
            <?php if ($product['inventory_quantity'] <= $product['low_stock_threshold']): ?>
                <span class="badge bg-warning text-dark">Low Stock</span>
            <?php endif; ?>
            
            <?php if ($product['inventory_quantity'] == 0): ?>
                <span class="badge bg-secondary">Out of Stock</span>
            <?php endif; ?>
        </div>
        
        <!-- Product Actions Overlay -->
        <div class="product-overlay">
            <div class="product-actions">
                <!-- Quick View -->
                <button class="btn btn-light quick-view" 
                        data-product-id="<?php echo $product['id']; ?>"
                        data-bs-toggle="tooltip" 
                        title="Quick View">
                    <i class="fas fa-eye"></i>
                </button>
                
                <!-- Add to Wishlist -->
                <button class="btn btn-light add-to-wishlist <?php echo $isInWishlist ? 'in-wishlist' : ''; ?>" 
                        data-product-id="<?php echo $product['id']; ?>"
                        data-bs-toggle="tooltip" 
                        title="<?php echo $isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                    <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
                
                <!-- Add to Cart -->
                <?php if ($product['inventory_quantity'] > 0): ?>
                    <button class="btn btn-primary add-to-cart" 
                            data-product-id="<?php echo $product['id']; ?>"
                            data-quantity="1"
                            data-bs-toggle="tooltip" 
                            title="Add to Cart">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled
                            data-bs-toggle="tooltip" 
                            title="Out of Stock">
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="product-info">
        <!-- Brand Name -->
        <?php if (!empty($product['brand_name'])): ?>
            <div class="product-brand">
                <small class="text-muted"><?php echo Security::sanitizeInput($product['brand_name']); ?></small>
            </div>
        <?php endif; ?>
        
        <!-- Product Title -->
        <h5 class="product-title">
            <a href="?page=product&id=<?php echo $product['id']; ?>&slug=<?php echo urlencode($product['slug']); ?>">
                <?php echo Security::sanitizeInput($product['name']); ?>
            </a>
        </h5>
        
        <!-- Product Rating -->
        <?php if ($reviewCount > 0): ?>
            <div class="product-rating">
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="<?php echo $i <= $avgRating ? 'fas' : 'far'; ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
                <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
            </div>
        <?php endif; ?>
        
        <!-- Product Price -->
        <div class="product-price">
            <?php echo formatCurrency($product['price']); ?>
            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                <span class="original-price"><?php echo formatCurrency($product['compare_price']); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Product Short Description -->
        <?php if (!empty($product['short_description'])): ?>
            <p class="product-description text-muted">
                <?php echo Security::sanitizeInput(substr($product['short_description'], 0, 100)); ?>
                <?php if (strlen($product['short_description']) > 100): ?>...<?php endif; ?>
            </p>
        <?php endif; ?>
        
        <!-- Size Options (if available) -->
        <?php
        $sizesQuery = "SELECT DISTINCT size FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY size";
        $sizes = $db->fetchAll($sizesQuery, [$product['id']]);
        ?>
        
        <?php if (!empty($sizes)): ?>
            <div class="product-sizes">
                <small class="text-muted">Available sizes:</small>
                <div class="size-options">
                    <?php foreach ($sizes as $size): ?>
                        <?php if (!empty($size['size'])): ?>
                            <span class="size-option"><?php echo Security::sanitizeInput($size['size']); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Color Options (if available) -->
        <?php
        $colorsQuery = "SELECT DISTINCT color FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY color";
        $colors = $db->fetchAll($colorsQuery, [$product['id']]);
        ?>
        
        <?php if (!empty($colors)): ?>
            <div class="product-colors">
                <small class="text-muted">Available colors:</small>
                <div class="color-options">
                    <?php foreach ($colors as $color): ?>
                        <?php if (!empty($color['color'])): ?>
                            <span class="color-option" 
                                  style="background-color: <?php echo Security::sanitizeInput(strtolower($color['color'])); ?>;"
                                  title="<?php echo Security::sanitizeInput($color['color']); ?>"></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Add to Cart Button (Mobile) -->
        <div class="product-actions-mobile d-md-none mt-3">
            <?php if ($product['inventory_quantity'] > 0): ?>
                <button class="btn btn-primary w-100 add-to-cart" 
                        data-product-id="<?php echo $product['id']; ?>"
                        data-quantity="1">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </button>
            <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>
                    <i class="fas fa-times me-2"></i>Out of Stock
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-badges {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 2;
}

.product-badges .badge {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.7rem;
}

.product-brand {
    margin-bottom: 0.25rem;
}

.product-description {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.product-sizes,
.product-colors {
    margin-bottom: 0.5rem;
}

.size-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.25rem;
}

.size-option {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.color-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.25rem;
}

.color-option {
    display: inline-block;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #dee2e6;
    cursor: pointer;
}

.product-actions-mobile {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

/* Responsive adjustments */
@media (max-width: 575.98px) {
    .product-info {
        padding: 0.75rem;
    }
    
    .product-title {
        font-size: 0.875rem;
    }
    
    .product-price {
        font-size: 1rem;
    }
    
    .product-rating {
        font-size: 0.75rem;
    }
    
    .size-option {
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
    }
    
    .color-option {
        width: 1rem;
        height: 1rem;
    }
}

/* Hover effects for desktop */
@media (min-width: 768px) {
    .product-actions-mobile {
        display: none !important;
    }
}
</style>
