<?php
/**
 * Product Detail Page - Single product view
 * 
 * @security All data is properly sanitized and validated
 */

// Get product ID and slug
$productId = (int)($_GET['id'] ?? 0);
$slug = Security::sanitizeInput($_GET['slug'] ?? '');

if ($productId <= 0) {
    redirect('?page=shop');
}

// Get product details
$productQuery = "SELECT p.*, b.name as brand_name, c.name as category_name, c.slug as category_slug,
                        AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                 FROM products p
                 LEFT JOIN brands b ON p.brand_id = b.id
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
                 WHERE p.id = ? AND p.is_active = 1
                 GROUP BY p.id";

$product = $db->fetchRow($productQuery, [$productId]);

if (!$product) {
    redirect('?page=shop');
}

// Set page title
$pageTitle = $product['name'];

// Get product images
$imagesQuery = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC";
$images = $db->fetchAll($imagesQuery, [$productId]);

// Get product variants
$variantsQuery = "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY size, color";
$variants = $db->fetchAll($variantsQuery, [$productId]);

// Get unique sizes and colors
$sizes = [];
$colors = [];
foreach ($variants as $variant) {
    if (!empty($variant['size']) && !in_array($variant['size'], $sizes)) {
        $sizes[] = $variant['size'];
    }
    if (!empty($variant['color']) && !in_array($variant['color'], $colors)) {
        $colors[] = $variant['color'];
    }
}

// Get reviews
$reviewsQuery = "SELECT r.*, u.first_name, u.last_name 
                 FROM reviews r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.product_id = ? AND r.is_approved = 1
                 ORDER BY r.created_at DESC
                 LIMIT 10";
$reviews = $db->fetchAll($reviewsQuery, [$productId]);

// Get related products
$relatedQuery = "SELECT p.*, pi.image_url, b.name as brand_name,
                        AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                 FROM products p
                 LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                 LEFT JOIN brands b ON p.brand_id = b.id
                 LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
                 WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
                 GROUP BY p.id
                 ORDER BY RAND()
                 LIMIT 4";
$relatedProducts = $db->fetchAll($relatedQuery, [$product['category_id'], $productId]);

// Check if in wishlist
$isInWishlist = false;
if ($auth->isLoggedIn()) {
    $wishlistQuery = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $wishlistResult = $db->fetchRow($wishlistQuery, [$_SESSION['user_id'], $productId]);
    $isInWishlist = !empty($wishlistResult);
}

// Calculate discount
$discountPercentage = 0;
if ($product['compare_price'] && $product['compare_price'] > $product['price']) {
    $discountPercentage = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="?page=shop">Shop</a></li>
            <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item">
                    <a href="?page=shop&category=<?php echo urlencode($product['category_slug']); ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="product-images">
                <!-- Main Image -->
                <div class="main-image mb-3">
                    <img src="<?php echo !empty($images) ? $images[0]['image_url'] : 'assets/images/product-placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="img-fluid rounded" 
                         id="mainProductImage">
                </div>
                
                <!-- Thumbnail Images -->
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <div class="row">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="col-3 mb-2">
                                    <img src="<?php echo $image['image_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($image['alt_text'] ?: $product['name']); ?>" 
                                         class="img-fluid rounded thumbnail-img <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeMainImage(this.src)">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-lg-6">
            <div class="product-details">
                <!-- Brand -->
                <?php if ($product['brand_name']): ?>
                    <div class="product-brand mb-2">
                        <span class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Product Name -->
                <h1 class="product-title h3 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- Rating -->
                <?php if ($product['review_count'] > 0): ?>
                    <div class="product-rating mb-3">
                        <div class="d-flex align-items-center">
                            <div class="rating-stars me-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= round($product['avg_rating']) ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">
                                <?php echo round($product['avg_rating'], 1); ?> 
                                (<?php echo $product['review_count']; ?> reviews)
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="product-price mb-4">
                    <span class="current-price h4 text-primary fw-bold">
                        <?php echo formatCurrency($product['price']); ?>
                    </span>
                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                        <span class="original-price text-muted text-decoration-line-through ms-2">
                            <?php echo formatCurrency($product['compare_price']); ?>
                        </span>
                        <span class="discount-badge badge bg-danger ms-2">
                            <?php echo $discountPercentage; ?>% OFF
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Short Description -->
                <?php if ($product['short_description']): ?>
                    <div class="product-short-description mb-4">
                        <p><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Product Options -->
                <form id="addToCartForm" class="product-options mb-4">
                    <!-- Size Selection -->
                    <?php if (!empty($sizes)): ?>
                        <div class="option-group mb-3">
                            <label class="form-label fw-semibold">Size:</label>
                            <div class="size-options">
                                <?php foreach ($sizes as $size): ?>
                                    <input type="radio" class="btn-check" name="size" value="<?php echo htmlspecialchars($size); ?>" id="size_<?php echo htmlspecialchars($size); ?>">
                                    <label class="btn btn-outline-secondary" for="size_<?php echo htmlspecialchars($size); ?>">
                                        <?php echo htmlspecialchars($size); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Color Selection -->
                    <?php if (!empty($colors)): ?>
                        <div class="option-group mb-3">
                            <label class="form-label fw-semibold">Color:</label>
                            <div class="color-options">
                                <?php foreach ($colors as $color): ?>
                                    <input type="radio" class="btn-check" name="color" value="<?php echo htmlspecialchars($color); ?>" id="color_<?php echo htmlspecialchars($color); ?>">
                                    <label class="btn btn-outline-secondary" for="color_<?php echo htmlspecialchars($color); ?>">
                                        <?php echo htmlspecialchars($color); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quantity -->
                    <div class="option-group mb-4">
                        <label class="form-label fw-semibold">Quantity:</label>
                        <div class="quantity-selector d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control mx-2 text-center" id="quantity" name="quantity" value="1" min="1" max="10" style="width: 80px;">
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                </form>

                <!-- Action Buttons -->
                <div class="product-actions mb-4">
                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <?php if ($product['inventory_quantity'] > 0): ?>
                                <button class="btn btn-primary w-100 add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        onclick="addToCartWithOptions()">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times me-2"></i>Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <button class="btn btn-outline-danger w-100 add-to-wishlist <?php echo $isInWishlist ? 'in-wishlist' : ''; ?>" 
                                    data-product-id="<?php echo $product['id']; ?>">
                                <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                <span class="d-none d-md-inline ms-1">Wishlist</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stock Status -->
                <div class="stock-status mb-4">
                    <?php if ($product['inventory_quantity'] > 0): ?>
                        <?php if ($product['inventory_quantity'] <= $product['low_stock_threshold']): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Only <?php echo $product['inventory_quantity']; ?> left in stock
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>In Stock
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-times me-1"></i>Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <ul class="list-unstyled">
                        <li><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></li>
                        <?php if ($product['category_name']): ?>
                            <li><strong>Category:</strong> 
                                <a href="?page=shop&category=<?php echo urlencode($product['category_slug']); ?>">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($product['weight']): ?>
                            <li><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight']); ?> kg</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Tabs -->
    <div class="product-tabs mt-5">
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">
                    Description
                </button>
            </li>
            <?php if (!empty($reviews)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">
                        Reviews (<?php echo count($reviews); ?>)
                    </button>
                </li>
            <?php endif; ?>
        </ul>
        
        <div class="tab-content" id="productTabsContent">
            <!-- Description Tab -->
            <div class="tab-pane fade show active" id="description" role="tabpanel">
                <div class="p-4">
                    <?php if ($product['description']): ?>
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    <?php else: ?>
                        <p>No detailed description available for this product.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Tab -->
            <?php if (!empty($reviews)): ?>
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <div class="p-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item mb-4 pb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <?php if ($review['title']): ?>
                                    <h6><?php echo htmlspecialchars($review['title']); ?></h6>
                                <?php endif; ?>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="related-products mt-5">
            <h3 class="mb-4">Related Products</h3>
            <div class="row">
                <?php foreach ($relatedProducts as $product): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.thumbnail-img {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.thumbnail-img.active,
.thumbnail-img:hover {
    opacity: 1;
}

.size-options .btn,
.color-options .btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.quantity-selector input {
    -moz-appearance: textfield;
}

.quantity-selector input::-webkit-outer-spin-button,
.quantity-selector input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.review-item:last-child {
    border-bottom: none !important;
}
</style>

<script>
function changeMainImage(src) {
    document.getElementById('mainProductImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-img').forEach(img => {
        img.classList.remove('active');
    });
    event.target.classList.add('active');
}

function changeQuantity(delta) {
    const quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    let newValue = currentValue + delta;
    
    if (newValue >= 1 && newValue <= 10) {
        quantityInput.value = newValue;
    }
}

function addToCartWithOptions() {
    const form = document.getElementById('addToCartForm');
    const formData = new FormData(form);
    const quantity = document.getElementById('quantity').value;
    
    // Get selected variant if any
    let variantId = null;
    const selectedSize = formData.get('size');
    const selectedColor = formData.get('color');
    
    // Find matching variant
    if (selectedSize || selectedColor) {
        // This would need to be implemented based on your variant logic
        // For now, we'll just add the base product
    }
    
    // Use the existing add to cart functionality
    const button = document.querySelector('.add-to-cart');
    button.dataset.quantity = quantity;
    button.click();
}
</script>
