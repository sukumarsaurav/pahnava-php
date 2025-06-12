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
                        $currentStock = $db->fetchRow("SELECT stock_quantity, name FROM products WHERE id = ?", [$productId]);

                        if (!$currentStock) {
                            $errors[] = 'Product not found.';
                        } else {
                            // Update stock
                            $db->execute("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?", [$newStock, $productId]);

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
$sort = Security::sanitizeInput($_GET['sort'] ?? 'stock_quantity');
$order = Security::sanitizeInput($_GET['order'] ?? 'asc');
$page = (int)($_GET['page_num'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = ["p.status != 'deleted'"];
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
            $conditions[] = "p.stock_quantity <= 10";
            break;
        case 'out':
            $conditions[] = "p.stock_quantity = 0";
            break;
        case 'in_stock':
            $conditions[] = "p.stock_quantity > 10";
            break;
    }
}

$whereClause = implode(' AND ', $conditions);

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
    $totalProducts = $db->fetchRow($countQuery, $params)['total'];
    $totalPages = ceil($totalProducts / $perPage);

    // Get products
    $offset = ($page - 1) * $perPage;
    $allowedSorts = ['name', 'sku', 'stock_quantity', 'created_at'];
    $sortColumn = in_array($sort, $allowedSorts) ? $sort : 'stock_quantity';
    $sortOrder = $order === 'desc' ? 'DESC' : 'ASC';

    $productsQuery = "SELECT p.*, c.name as category_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE $whereClause
                      ORDER BY p.$sortColumn $sortOrder
                      LIMIT $perPage OFFSET $offset";
    $products = $db->fetchAll($productsQuery, $params);

    // Get categories for filter
    $categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

    // Get inventory statistics
    $statsQuery = "SELECT
                       COUNT(*) as total_products,
                       SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                       SUM(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                       SUM(stock_quantity) as total_stock_value
                   FROM products
                   WHERE status = 'active'";
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
