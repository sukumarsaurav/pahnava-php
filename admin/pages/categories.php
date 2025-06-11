<?php
/**
 * Categories Management Page
 * 
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_products')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Categories</h1>
            <p class="text-muted">Manage product categories</p>
        </div>
        <div>
            <button class="btn btn-primary" disabled>
                <i class="fas fa-plus me-2"></i>Add Category
            </button>
        </div>
    </div>
    
    <!-- Coming Soon Notice -->
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-tags fa-3x text-primary mb-3"></i>
            <h4>Categories Management</h4>
            <p class="text-muted">This feature is currently under development.</p>
            <p class="text-muted">You will be able to manage product categories, create hierarchies, and organize your catalog.</p>
            
            <div class="mt-4">
                <a href="?page=products" class="btn btn-primary">
                    <i class="fas fa-box me-2"></i>Manage Products
                </a>
            </div>
        </div>
    </div>
</div>
