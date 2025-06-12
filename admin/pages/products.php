<?php
/**
 * Products Management Page
 *
 * @security Admin authentication required
 */

$errors = [];
$success = '';

// Get filters
$search = $_GET['search'] ?? '';
$category = (int)($_GET['category'] ?? 0);
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'desc';
$page = (int)($_GET['page_num'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if (!empty($status)) {
    if ($status === 'active') {
        $conditions[] = "p.is_active = 1";
    } elseif ($status === 'inactive') {
        $conditions[] = "p.is_active = 0";
    } elseif ($status === 'low_stock') {
        $conditions[] = "p.inventory_quantity <= p.low_stock_threshold";
    }
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
    $totalResult = $db->fetchRow($countQuery, $params);
    $totalProducts = $totalResult['total'] ?? 0;
    $totalPages = ceil($totalProducts / $perPage);

    // Get products with category and brand info
    $offset = ($page - 1) * $perPage;
    $allowedSorts = ['name', 'sku', 'price', 'inventory_quantity', 'created_at'];
    $sortColumn = in_array($sort, $allowedSorts) ? $sort : 'created_at';
    $sortOrder = $order === 'asc' ? 'ASC' : 'DESC';

    $productsQuery = "SELECT p.*,
                             c.name as category_name,
                             b.name as brand_name,
                             pi.image_url as primary_image,
                             (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as total_sold
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                      $whereClause
                      ORDER BY p.$sortColumn $sortOrder
                      LIMIT $perPage OFFSET $offset";

    $products = $db->fetchAll($productsQuery, $params);

    // Get categories for filter
    $categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

} catch (Exception $e) {
    $products = [];
    $categories = [];
    $totalProducts = 0;
    $totalPages = 0;
    $errors[] = 'Error loading products: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Products</h1>
            <p class="text-muted">Manage your product catalog</p>
        </div>
        <div>
            <a href="?page=add-product" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Product
            </a>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="products">
                
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search products...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="low_stock" <?php echo $status === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
                        <option value="inventory_quantity" <?php echo $sort === 'inventory_quantity' ? 'selected' : ''; ?>>Stock</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">Order</label>
                    <select class="form-select" name="order">
                        <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Desc</option>
                        <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Asc</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="?page=products" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products (<?php echo number_format($totalProducts); ?>)</h5>
            
            <!-- Bulk Actions -->
            <div class="d-flex align-items-center">
                <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-sm btn-outline-primary" id="bulkActionBtn" disabled onclick="handleBulkActions()">
                    Apply
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($products)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" class="form-check-input" onchange="toggleSelectAll(this)">
                                </th>
                                <th width="80">Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Sales</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input"
                                               name="selected_items[]" value="<?php echo $product['id']; ?>">
                                    </td>
                                    <td>
                                        <?php if (!empty($product['primary_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['primary_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                            <?php if ($product['is_featured']): ?>
                                                <br><span class="badge bg-warning text-dark">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['category_name'])): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No Category</span>
                                        <?php endif; ?>
                                        <?php if (!empty($product['brand_name'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($product['price'], 2); ?></strong>
                                        <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                            <br><small class="text-muted text-decoration-line-through">₹<?php echo number_format($product['compare_price'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stock = $product['inventory_quantity'];
                                        $threshold = $product['low_stock_threshold'];
                                        $stockClass = $stock <= $threshold ? 'text-danger' : ($stock <= ($threshold * 2) ? 'text-warning' : 'text-success');
                                        ?>
                                        <span class="<?php echo $stockClass; ?>">
                                            <?php echo number_format($stock); ?>
                                        </span>
                                        <?php if ($stock <= $threshold): ?>
                                            <i class="fas fa-exclamation-triangle text-danger ms-1" title="Low Stock Alert"></i>
                                        <?php endif; ?>
                                        <?php if (!$product['track_inventory']): ?>
                                            <br><small class="text-muted">Not tracked</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo number_format($product['total_sold'] ?? 0); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=edit-product&id=<?php echo $product['id']; ?>"
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-info" title="View"
                                                    onclick="viewProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Products pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=products&page_num=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=products&page_num=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=products&page_num=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-box fa-3x text-muted mb-3"></i>
                    <h5>No products found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || $category > 0 || !empty($status)): ?>
                            Try adjusting your filters or <a href="?page=products">clear all filters</a>.
                        <?php else: ?>
                            Start by <a href="?page=add-product">adding your first product</a>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        return;
    }

    // Simple form submission for now
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="product_id" value="${productId}">
        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewProduct(productId) {
    alert('Product view functionality will be implemented soon.');
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);

    const bulkBtn = document.getElementById('bulkActionBtn');
    bulkBtn.disabled = !checkbox.checked;
}

function handleBulkActions() {
    const action = document.getElementById('bulkAction').value;
    const selected = document.querySelectorAll('input[name="selected_items[]"]:checked');

    if (!action) {
        alert('Please select an action.');
        return;
    }

    if (selected.length === 0) {
        alert('Please select at least one product.');
        return;
    }

    if (!confirm(`Are you sure you want to ${action} ${selected.length} product(s)?`)) {
        return;
    }

    alert('Bulk actions functionality will be implemented soon.');
}

// Enable/disable bulk action button based on selection
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const bulkBtn = document.getElementById('bulkActionBtn');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const anyChecked = document.querySelectorAll('input[name="selected_items[]"]:checked').length > 0;
            bulkBtn.disabled = !anyChecked;
        });
    });
});
</script>
