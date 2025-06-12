<?php
/**
 * Inventory Management Page
 *
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_inventory')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    adminRedirect('?page=dashboard');
}

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_stock':
                $productId = (int)($_POST['product_id'] ?? 0);
                $newStock = (int)($_POST['new_stock'] ?? 0);
                $reason = Security::sanitizeInput($_POST['reason'] ?? '');

                if ($productId <= 0) {
                    $errors[] = 'Invalid product ID.';
                } elseif ($newStock < 0) {
                    $errors[] = 'Stock quantity cannot be negative.';
                } else {
                    try {
                        // Get current stock
                        $currentStock = $db->fetchRow("SELECT inventory_quantity, name FROM products WHERE id = ?", [$productId]);

                        if (!$currentStock) {
                            $errors[] = 'Product not found.';
                        } else {
                            // Update stock
                            $db->execute("UPDATE products SET inventory_quantity = ?, updated_at = NOW() WHERE id = ?", [$newStock, $productId]);

                            $success = 'Stock updated successfully for ' . htmlspecialchars($currentStock['name']);
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Error updating stock: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get filters
$search = Security::sanitizeInput($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$stockStatus = Security::sanitizeInput($_GET['stock_status'] ?? '');
$sort = Security::sanitizeInput($_GET['sort'] ?? 'inventory_quantity');
$order = Security::sanitizeInput($_GET['order'] ?? 'asc');
$page = (int)($_GET['page_num'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if (!empty($stockStatus)) {
    switch ($stockStatus) {
        case 'low':
            $conditions[] = "p.inventory_quantity <= p.low_stock_threshold";
            break;
        case 'out':
            $conditions[] = "p.inventory_quantity = 0";
            break;
        case 'in_stock':
            $conditions[] = "p.inventory_quantity > p.low_stock_threshold";
            break;
    }
}

$whereClause = implode(' AND ', $conditions);

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
    $totalProducts = $db->fetchRow($countQuery, $params)['total'];
    $totalPages = ceil($totalProducts / $perPage);

    // Get products with category and image info
    $offset = ($page - 1) * $perPage;
    $allowedSorts = ['name', 'sku', 'inventory_quantity', 'created_at'];
    $sortColumn = in_array($sort, $allowedSorts) ? $sort : 'inventory_quantity';
    $sortOrder = $order === 'desc' ? 'DESC' : 'ASC';

    $productsQuery = "SELECT p.*,
                             c.name as category_name,
                             pi.image_url as primary_image
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                      WHERE $whereClause
                      ORDER BY p.$sortColumn $sortOrder
                      LIMIT $perPage OFFSET $offset";
    $products = $db->fetchAll($productsQuery, $params);

    // Get categories for filter
    $categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

    // Get inventory statistics
    $statsQuery = "SELECT
                       COUNT(*) as total_products,
                       SUM(CASE WHEN inventory_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                       SUM(CASE WHEN inventory_quantity <= low_stock_threshold AND inventory_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                       SUM(inventory_quantity) as total_stock_value
                   FROM products
                   WHERE is_active = 1";
    $stats = $db->fetchRow($statsQuery);

} catch (Exception $e) {
    $products = [];
    $categories = [];
    $stats = ['total_products' => 0, 'out_of_stock' => 0, 'low_stock' => 0, 'total_stock_value' => 0];
    $totalProducts = 0;
    $totalPages = 0;
    $errors[] = 'Error loading inventory data: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Inventory Management</h1>
            <p class="text-muted">Monitor and manage product stock levels</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="exportInventory()">
                <i class="fas fa-download me-2"></i>Export Inventory
            </button>
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

    <!-- Inventory Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stats-label">Total Products</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['out_of_stock']); ?></div>
                        <div class="stats-label">Out of Stock</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['low_stock']); ?></div>
                        <div class="stats-label">Low Stock</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['total_stock_value']); ?></div>
                        <div class="stats-label">Total Stock Units</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="inventory">

                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search"
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Product name or SKU...">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select class="form-select" name="stock_status">
                        <option value="">All Stock</option>
                        <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="low" <?php echo $stockStatus === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out" <?php echo $stockStatus === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="inventory_quantity" <?php echo $sort === 'inventory_quantity' ? 'selected' : ''; ?>>Stock Level</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Product Name</option>
                        <option value="sku" <?php echo $sort === 'sku' ? 'selected' : ''; ?>>SKU</option>
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Order</label>
                    <select class="form-select" name="order">
                        <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>ASC</option>
                        <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>DESC</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="?page=inventory" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products (<?php echo number_format($totalProducts); ?>)</h5>

            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-primary me-2" onclick="bulkUpdateMode()">
                    <i class="fas fa-edit me-1"></i>Bulk Update
                </button>
                <button class="btn btn-sm btn-success" id="saveBulkBtn" style="display: none;" onclick="saveBulkUpdates()">
                    <i class="fas fa-save me-1"></i>Save Changes
                </button>
                <button class="btn btn-sm btn-secondary" id="cancelBulkBtn" style="display: none;" onclick="cancelBulkUpdate()">
                    Cancel
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($products)): ?>
                <form id="bulkUpdateForm" method="POST" style="display: none;">
                    <input type="hidden" name="action" value="bulk_update">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                </form>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr data-product-id="<?php echo $product['id']; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['primary_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['primary_image']); ?>"
                                                     alt="Product" class="product-thumb me-3">
                                            <?php else: ?>
                                                <div class="product-thumb-placeholder me-3">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                                <?php if ($product['is_featured']): ?>
                                                    <br><span class="badge bg-warning text-dark">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($product['sku']); ?></code>
                                        <?php if (!$product['track_inventory']): ?>
                                            <br><small class="text-muted">Not tracked</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['category_name']): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="stock-display">
                                            <?php
                                            $stock = $product['inventory_quantity'];
                                            $threshold = $product['low_stock_threshold'];
                                            $stockClass = 'success';
                                            if ($stock == 0) {
                                                $stockClass = 'danger';
                                            } elseif ($stock <= $threshold) {
                                                $stockClass = 'warning';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $stockClass; ?> stock-badge">
                                                <?php echo number_format($stock); ?>
                                            </span>
                                            <?php if ($threshold > 0): ?>
                                                <br><small class="text-muted">Threshold: <?php echo $threshold; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="stock-edit" style="display: none;">
                                            <input type="number" class="form-control form-control-sm stock-input"
                                                   value="<?php echo $stock; ?>"
                                                   min="0" style="width: 80px;">
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($stock == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($stock <= $threshold): ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('M j, Y', strtotime($product['updated_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($product['updated_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="updateStock(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['inventory_quantity']; ?>)"
                                                    title="Update Stock">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?page=edit-product&id=<?php echo $product['id']; ?>"
                                               class="btn btn-outline-info" title="Edit Product">
                                                <i class="fas fa-cog"></i>
                                            </a>
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
                        <?php
                        $baseUrl = "?page=inventory";
                        if ($search) $baseUrl .= "&search=" . urlencode($search);
                        if ($category) $baseUrl .= "&category=" . $category;
                        if ($stockStatus) $baseUrl .= "&stock_status=" . urlencode($stockStatus);
                        if ($sort !== 'inventory_quantity') $baseUrl .= "&sort=" . urlencode($sort);
                        if ($order !== 'asc') $baseUrl .= "&order=" . urlencode($order);

                        echo generateAdminPagination($page, $totalPages, $baseUrl);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                    <h5>No products found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || $category || !empty($stockStatus)): ?>
                            Try adjusting your filters or <a href="?page=inventory">clear all filters</a>.
                        <?php else: ?>
                            <a href="?page=add-product">Add your first product</a> to start managing inventory.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="product_id" id="update_product_id">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <div id="update_product_name" class="form-control-plaintext"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <div id="update_current_stock" class="form-control-plaintext"></div>
                    </div>

                    <div class="mb-3">
                        <label for="new_stock" class="form-label">New Stock Quantity *</label>
                        <input type="number" class="form-control" id="new_stock" name="new_stock" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Change</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Optional reason for stock adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.product-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.product-thumb-placeholder {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.stock-badge {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>

<script>
let bulkMode = false;

function updateStock(productId, productName, currentStock) {
    document.getElementById('update_product_id').value = productId;
    document.getElementById('update_product_name').textContent = productName;
    document.getElementById('update_current_stock').textContent = currentStock + ' units';
    document.getElementById('new_stock').value = currentStock;

    const modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
    modal.show();
}

function bulkUpdateMode() {
    bulkMode = true;

    // Show bulk update controls
    document.getElementById('saveBulkBtn').style.display = 'inline-block';
    document.getElementById('cancelBulkBtn').style.display = 'inline-block';

    // Hide stock display, show stock inputs
    document.querySelectorAll('.stock-display').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.stock-edit').forEach(el => el.style.display = 'block');

    // Hide regular action buttons
    document.querySelectorAll('.btn-group').forEach(el => el.style.display = 'none');
}

function cancelBulkUpdate() {
    bulkMode = false;

    // Hide bulk update controls
    document.getElementById('saveBulkBtn').style.display = 'none';
    document.getElementById('cancelBulkBtn').style.display = 'none';

    // Show stock display, hide stock inputs
    document.querySelectorAll('.stock-display').forEach(el => el.style.display = 'block');
    document.querySelectorAll('.stock-edit').forEach(el => el.style.display = 'none');

    // Show regular action buttons
    document.querySelectorAll('.btn-group').forEach(el => el.style.display = 'flex');

    // Reset input values
    document.querySelectorAll('.stock-input').forEach(input => {
        const row = input.closest('tr');
        const originalValue = row.querySelector('.stock-badge').textContent.replace(/,/g, '');
        input.value = originalValue;
    });
}

function saveBulkUpdates() {
    const form = document.getElementById('bulkUpdateForm');
    const updates = {};

    document.querySelectorAll('.stock-input').forEach(input => {
        const row = input.closest('tr');
        const productId = row.dataset.productId;
        const newStock = parseInt(input.value) || 0;
        const originalStock = parseInt(row.querySelector('.stock-badge').textContent.replace(/,/g, '')) || 0;

        if (newStock !== originalStock) {
            updates[productId] = { stock: newStock };
        }
    });

    if (Object.keys(updates).length === 0) {
        alert('No changes detected.');
        return;
    }

    // Add updates to form
    Object.keys(updates).forEach(productId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `bulk_updates[${productId}][stock]`;
        input.value = updates[productId].stock;
        form.appendChild(input);
    });

    form.submit();
}

function exportInventory() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('ajax/export-inventory.php?' + params.toString(), '_blank');
}
</script>
