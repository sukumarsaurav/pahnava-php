<?php
/**
 * Edit Product Page
 * 
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_products')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}

// Get product ID
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    setAdminFlashMessage('Invalid product ID.', 'danger');
    redirect('admin/?page=products');
}

// Get product details
$productQuery = "SELECT p.*, c.name as category_name, b.name as brand_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 LEFT JOIN brands b ON p.brand_id = b.id 
                 WHERE p.id = ?";
$product = $db->fetchRow($productQuery, [$productId]);

if (!$product) {
    setAdminFlashMessage('Product not found.', 'danger');
    redirect('admin/?page=products');
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Product</h1>
            <p class="text-muted">Update product information</p>
        </div>
        <div>
            <a href="?page=products" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>
    
    <!-- Coming Soon Notice -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-tools fa-3x text-primary mb-3"></i>
            <h4>Edit Product Feature</h4>
            <p class="text-muted">This feature is currently under development.</p>
            <p class="text-muted">Product: <strong><?php echo htmlspecialchars($product['name']); ?></strong></p>
            <p class="text-muted">SKU: <strong><?php echo htmlspecialchars($product['sku']); ?></strong></p>
            
            <div class="mt-4">
                <a href="?page=products" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>View All Products
                </a>
            </div>
        </div>
    </div>
</div>
