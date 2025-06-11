<?php
/**
 * Admin Header Template
 */

// Get current admin
$currentAdmin = $adminAuth->getCurrentAdmin();

// Get flash message
$flashMessage = getAdminFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Pahnava Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- CSRF Token -->
    <script>
        window.csrfToken = '<?php echo Security::getCSRFToken(); ?>';
    </script>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="admin/">
                <i class="fas fa-store me-2"></i>Pahnava Admin
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>View Site
                        </a>
                    </li>
                </ul>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?page=profile">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="?page=settings">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-content">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                
                <!-- Products Section -->
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#productsMenu">
                        <i class="fas fa-box me-2"></i>Products
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo in_array($page, ['products', 'add-product', 'edit-product', 'categories', 'brands']) ? 'show' : ''; ?>" id="productsMenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'products' ? 'active' : ''; ?>" href="?page=products">
                                    <i class="fas fa-list me-2"></i>All Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'add-product' ? 'active' : ''; ?>" href="?page=add-product">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'categories' ? 'active' : ''; ?>" href="?page=categories">
                                    <i class="fas fa-tags me-2"></i>Categories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'brands' ? 'active' : ''; ?>" href="?page=brands">
                                    <i class="fas fa-trademark me-2"></i>Brands
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- Orders -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'orders' ? 'active' : ''; ?>" href="?page=orders">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                </li>
                
                <!-- Customers -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'customers' ? 'active' : ''; ?>" href="?page=customers">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                
                <!-- Marketing Section -->
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#marketingMenu">
                        <i class="fas fa-bullhorn me-2"></i>Marketing
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo in_array($page, ['coupons', 'reviews']) ? 'show' : ''; ?>" id="marketingMenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'coupons' ? 'active' : ''; ?>" href="?page=coupons">
                                    <i class="fas fa-ticket-alt me-2"></i>Coupons
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'reviews' ? 'active' : ''; ?>" href="?page=reviews">
                                    <i class="fas fa-star me-2"></i>Reviews
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- Inventory -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'inventory' ? 'active' : ''; ?>" href="?page=inventory">
                        <i class="fas fa-warehouse me-2"></i>Inventory
                    </a>
                </li>
                
                <!-- Reports -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'reports' ? 'active' : ''; ?>" href="?page=reports">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                
                <!-- Settings Section -->
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#settingsMenu">
                        <i class="fas fa-cog me-2"></i>Settings
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo in_array($page, ['settings', 'shipping', 'taxes']) ? 'show' : ''; ?>" id="settingsMenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>" href="?page=settings">
                                    <i class="fas fa-sliders-h me-2"></i>General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'shipping' ? 'active' : ''; ?>" href="?page=shipping">
                                    <i class="fas fa-shipping-fast me-2"></i>Shipping
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'taxes' ? 'active' : ''; ?>" href="?page=taxes">
                                    <i class="fas fa-calculator me-2"></i>Taxes
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Flash Messages -->
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flashMessage['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
