<?php
/**
 * Shop Page - Product catalog with filtering and search
 * 
 * @security All data is properly sanitized and validated
 */

// Set page title
$pageTitle = 'Shop';

// Get filters from URL
$category = Security::sanitizeInput($_GET['category'] ?? '');
$search = Security::sanitizeInput($_GET['search'] ?? '');
$sort = Security::sanitizeInput($_GET['sort'] ?? 'newest');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$page = (int)($_GET['page'] ?? 1);
$perPage = 12;

// Build query conditions
$conditions = ['p.is_active = 1'];
$params = [];

// Category filter
if (!empty($category)) {
    $categoryData = getCategoryBySlug($category);
    if ($categoryData) {
        $conditions[] = 'p.category_id = ?';
        $params[] = $categoryData['id'];
        $pageTitle = 'Shop - ' . $categoryData['name'];
    }
}

// Search filter
if (!empty($search)) {
    $conditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $pageTitle = 'Search Results for "' . $search . '"';
}

// Price filter
if ($minPrice > 0) {
    $conditions[] = 'p.price >= ?';
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $conditions[] = 'p.price <= ?';
    $params[] = $maxPrice;
}

// Featured filter
if (isset($_GET['featured'])) {
    $conditions[] = 'p.is_featured = 1';
    $pageTitle = 'Featured Products';
}

// Sale filter
if (isset($_GET['sale'])) {
    $conditions[] = 'p.compare_price > p.price';
    $pageTitle = 'Sale Products';
}

// Get total count
$countQuery = "SELECT COUNT(DISTINCT p.id) as total 
               FROM products p 
               LEFT JOIN brands b ON p.brand_id = b.id 
               WHERE " . implode(' AND ', $conditions);

$totalResult = $db->fetchRow($countQuery, $params);
$totalProducts = $totalResult['total'];
$totalPages = ceil($totalProducts / $perPage);

// Sort options
$sortOptions = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_az' => 'p.name ASC',
    'name_za' => 'p.name DESC',
    'featured' => 'p.is_featured DESC, p.created_at DESC'
];

$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

// Get products
$offset = ($page - 1) * $perPage;
$productsQuery = "SELECT p.*, pi.image_url, b.name as brand_name,
                         AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                  FROM products p
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                  LEFT JOIN brands b ON p.brand_id = b.id
                  LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = 1
                  WHERE " . implode(' AND ', $conditions) . "
                  GROUP BY p.id
                  ORDER BY {$orderBy}
                  LIMIT {$perPage} OFFSET {$offset}";

$products = $db->fetchAll($productsQuery, $params);

// Get categories for sidebar
$categories = getMainCategories();

// Get price range
$priceRangeQuery = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1";
$priceRange = $db->fetchRow($priceRangeQuery);
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="shop-sidebar">
                <!-- Search -->
                <div class="filter-section mb-4">
                    <h5>Search Products</h5>
                    <form method="GET" action="?page=shop">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <!-- Preserve other filters -->
                        <?php if ($category): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Categories -->
                <div class="filter-section mb-4">
                    <h5>Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="?page=shop" class="text-decoration-none <?php echo empty($category) ? 'fw-bold text-primary' : ''; ?>">
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li class="mb-2">
                                <a href="?page=shop&category=<?php echo urlencode($cat['slug']); ?>" 
                                   class="text-decoration-none <?php echo $category === $cat['slug'] ? 'fw-bold text-primary' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price Range -->
                <div class="filter-section mb-4">
                    <h5>Price Range</h5>
                    <form method="GET" action="?page=shop" id="priceFilterForm">
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="min_price" placeholder="Min" 
                                       value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>"
                                       min="0" max="<?php echo $priceRange['max_price']; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control form-control-sm" 
                                       name="max_price" placeholder="Max" 
                                       value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>"
                                       min="0" max="<?php echo $priceRange['max_price']; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-2 w-100">Apply</button>
                        
                        <!-- Preserve other filters -->
                        <?php if ($category): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Quick Filters -->
                <div class="filter-section mb-4">
                    <h5>Quick Filters</h5>
                    <div class="d-grid gap-2">
                        <a href="?page=shop&featured=1" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-star me-1"></i>Featured
                        </a>
                        <a href="?page=shop&sale=1" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-tag me-1"></i>On Sale
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p class="text-muted mb-0"><?php echo $totalProducts; ?> products found</p>
                </div>
                
                <!-- Sort Options -->
                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">Sort by:</label>
                    <select class="form-select form-select-sm" id="sortSelect" style="width: auto;">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_az" <?php echo $sort === 'name_az' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_za" <?php echo $sort === 'name_za' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters -->
            <?php if ($category || $search || $minPrice > 0 || $maxPrice > 0 || isset($_GET['featured']) || isset($_GET['sale'])): ?>
                <div class="active-filters mb-4">
                    <h6>Active Filters:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($category): ?>
                            <span class="badge bg-primary">
                                Category: <?php echo htmlspecialchars($categoryData['name'] ?? $category); ?>
                                <a href="?page=shop" class="text-white ms-1">×</a>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($search): ?>
                            <span class="badge bg-primary">
                                Search: "<?php echo htmlspecialchars($search); ?>"
                                <a href="?page=shop" class="text-white ms-1">×</a>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($minPrice > 0 || $maxPrice > 0): ?>
                            <span class="badge bg-primary">
                                Price: ₹<?php echo $minPrice; ?> - ₹<?php echo $maxPrice ?: 'Max'; ?>
                                <a href="?page=shop" class="text-white ms-1">×</a>
                            </span>
                        <?php endif; ?>
                        
                        <a href="?page=shop" class="btn btn-sm btn-outline-secondary">Clear All</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <?php include 'includes/product-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-5">
                        <?php
                        $baseUrl = '?page=shop';
                        if ($category) $baseUrl .= '&category=' . urlencode($category);
                        if ($search) $baseUrl .= '&search=' . urlencode($search);
                        if ($sort !== 'newest') $baseUrl .= '&sort=' . urlencode($sort);
                        if ($minPrice > 0) $baseUrl .= '&min_price=' . $minPrice;
                        if ($maxPrice > 0) $baseUrl .= '&max_price=' . $maxPrice;
                        if (isset($_GET['featured'])) $baseUrl .= '&featured=1';
                        if (isset($_GET['sale'])) $baseUrl .= '&sale=1';
                        
                        echo generatePagination($page, $totalPages, $baseUrl);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- No Products Found -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No products found</h4>
                    <p class="text-muted">Try adjusting your search criteria or browse our categories.</p>
                    <a href="?page=shop" class="btn btn-primary">View All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.shop-sidebar {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.filter-section h5 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
}

.active-filters .badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.active-filters .badge a {
    text-decoration: none;
    font-weight: bold;
}

@media (max-width: 991.98px) {
    .shop-sidebar {
        margin-bottom: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sort functionality
    const sortSelect = document.getElementById('sortSelect');
    sortSelect.addEventListener('change', function() {
        const url = new URL(window.location);
        url.searchParams.set('sort', this.value);
        url.searchParams.delete('page'); // Reset to first page
        window.location.href = url.toString();
    });
});
</script>

<?php
/**
 * Helper functions for shop page
 */

function getCategoryBySlug($slug) {
    global $db;
    
    $query = "SELECT * FROM categories WHERE slug = ? AND is_active = 1";
    return $db->fetchRow($query, [$slug]);
}

function getMainCategories() {
    global $db;
    
    $query = "SELECT * FROM categories 
              WHERE parent_id IS NULL AND is_active = 1 
              ORDER BY sort_order ASC, name ASC";
    
    return $db->fetchAll($query);
}
?>
