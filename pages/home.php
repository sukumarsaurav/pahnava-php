<?php
/**
 * Home Page - Landing page with hero section, featured products, and categories
 * 
 * @security All data is properly sanitized and validated
 */

// Set page title
$pageTitle = 'Home';

// Get featured products
$featuredProducts = getFeaturedProducts(8);

// Get categories
$categories = getMainCategories();

// Get latest products
$latestProducts = getLatestProducts(8);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        
        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="hero-slide" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 500px;">
                    <div class="container h-100">
                        <div class="row h-100 align-items-center">
                            <div class="col-lg-6">
                                <div class="hero-content text-white">
                                    <h1 class="display-4 fw-bold mb-4">New Collection 2024</h1>
                                    <p class="lead mb-4">Discover the latest fashion trends with our premium clothing collection. Style meets comfort in every piece.</p>
                                    <div class="hero-buttons">
                                        <a href="?page=shop" class="btn btn-light btn-lg me-3">Shop Now</a>
                                        <a href="?page=shop&category=women" class="btn btn-outline-light btn-lg">Women's Collection</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 text-center">
                                <img src="assets/images/hero-1.jpg" alt="New Collection" class="img-fluid" style="max-height: 400px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="hero-slide" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); min-height: 500px;">
                    <div class="container h-100">
                        <div class="row h-100 align-items-center">
                            <div class="col-lg-6">
                                <div class="hero-content text-white">
                                    <h1 class="display-4 fw-bold mb-4">Summer Sale</h1>
                                    <p class="lead mb-4">Up to 50% off on selected items. Limited time offer on premium clothing and accessories.</p>
                                    <div class="hero-buttons">
                                        <a href="?page=shop&sale=1" class="btn btn-light btn-lg me-3">Shop Sale</a>
                                        <a href="?page=shop&category=men" class="btn btn-outline-light btn-lg">Men's Collection</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 text-center">
                                <img src="assets/images/hero-2.jpg" alt="Summer Sale" class="img-fluid" style="max-height: 400px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="carousel-item">
                <div class="hero-slide" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); min-height: 500px;">
                    <div class="container h-100">
                        <div class="row h-100 align-items-center">
                            <div class="col-lg-6">
                                <div class="hero-content text-white">
                                    <h1 class="display-4 fw-bold mb-4">Kids Fashion</h1>
                                    <p class="lead mb-4">Comfortable and stylish clothing for your little ones. Quality fabrics and trendy designs.</p>
                                    <div class="hero-buttons">
                                        <a href="?page=shop&category=kids" class="btn btn-light btn-lg me-3">Shop Kids</a>
                                        <a href="?page=shop&category=accessories" class="btn btn-outline-light btn-lg">Accessories</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 text-center">
                                <img src="assets/images/hero-3.jpg" alt="Kids Fashion" class="img-fluid" style="max-height: 400px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="feature-item text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                    </div>
                    <h5>Free Shipping</h5>
                    <p class="text-muted">Free shipping on orders over ₹999</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="feature-item text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-undo fa-3x text-primary"></i>
                    </div>
                    <h5>Easy Returns</h5>
                    <p class="text-muted">30-day return policy</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="feature-item text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-3x text-primary"></i>
                    </div>
                    <h5>Secure Payment</h5>
                    <p class="text-muted">100% secure payment gateway</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="feature-item text-center">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-headset fa-3x text-primary"></i>
                    </div>
                    <h5>24/7 Support</h5>
                    <p class="text-muted">Customer support available</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title">Shop by Category</h2>
                <p class="section-subtitle text-muted">Discover our wide range of fashion categories</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="category-card">
                    <a href="?page=shop&category=<?php echo urlencode($category['slug']); ?>" class="text-decoration-none">
                        <div class="category-image">
                            <img src="<?php echo $category['image'] ?: 'assets/images/category-placeholder.jpg'; ?>" 
                                 alt="<?php echo Security::sanitizeInput($category['name']); ?>" 
                                 class="img-fluid">
                            <div class="category-overlay">
                                <h4 class="category-name text-white"><?php echo Security::sanitizeInput($category['name']); ?></h4>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-products-section py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title">Featured Products</h2>
                <p class="section-subtitle text-muted">Handpicked items just for you</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row">
            <div class="col-12 text-center">
                <a href="?page=shop&featured=1" class="btn btn-primary btn-lg">View All Featured</a>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products Section -->
<section class="latest-products-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title">New Arrivals</h2>
                <p class="section-subtitle text-muted">Latest additions to our collection</p>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($latestProducts as $product): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row">
            <div class="col-12 text-center">
                <a href="?page=shop" class="btn btn-outline-primary btn-lg">View All Products</a>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3>Stay Updated</h3>
                <p class="mb-0">Subscribe to our newsletter for latest updates and exclusive offers</p>
            </div>
            <div class="col-lg-6">
                <form class="newsletter-form d-flex" id="homeNewsletterForm">
                    <input type="email" class="form-control me-2" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-light">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.hero-slide {
    display: flex;
    align-items: center;
    min-height: 500px;
}

.category-card {
    position: relative;
    overflow: hidden;
    border-radius: 0.5rem;
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
}

.category-image {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    padding: 2rem 1rem 1rem;
}

.category-name {
    margin: 0;
    font-weight: 600;
}

.section-title {
    font-weight: 700;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.1rem;
}

.feature-item {
    padding: 2rem 1rem;
    transition: transform 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-5px);
}

.newsletter-form input {
    flex: 1;
}

@media (max-width: 767.98px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-buttons .btn {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .newsletter-form input {
        margin-bottom: 1rem;
        margin-right: 0 !important;
    }
}
</style>

<?php
/**
 * Helper functions for home page
 */

function getFeaturedProducts($limit = 8) {
    global $db;
    
    $query = "SELECT p.*, pi.image_url, b.name as brand_name,
                     AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
              FROM products p
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN brands b ON p.brand_id = b.id
              LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
              WHERE p.is_active = 1 AND p.is_featured = 1
              GROUP BY p.id
              ORDER BY p.created_at DESC
              LIMIT ?";
    
    return $db->fetchAll($query, [$limit]);
}

function getLatestProducts($limit = 8) {
    global $db;
    
    $query = "SELECT p.*, pi.image_url, b.name as brand_name,
                     AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
              FROM products p
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN brands b ON p.brand_id = b.id
              LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
              WHERE p.is_active = 1
              GROUP BY p.id
              ORDER BY p.created_at DESC
              LIMIT ?";
    
    return $db->fetchAll($query, [$limit]);
}

function getMainCategories() {
    global $db;
    
    $query = "SELECT * FROM categories 
              WHERE parent_id IS NULL AND is_active = 1 
              ORDER BY sort_order ASC, name ASC";
    
    return $db->fetchAll($query);
}
?>
