<?php
/**
 * Clean Admin Panel Entry Point
 * No function conflicts, minimal dependencies
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple redirect function with unique name
function adminRedirect($url) {
    header("Location: $url");
    exit;
}

// Check if setup is required
if (!file_exists('../config/database.php')) {
    adminRedirect('../deploy.php');
}

try {
    // Include database first
    require_once '../config/database.php';
    
    // Check if admin tables exist
    $adminUserTable = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
    if (!$adminUserTable) {
        adminRedirect('setup.php');
    }
    
    // Include other required files
    require_once '../includes/security.php';
    require_once '../includes/functions.php';
    require_once 'includes/admin-auth.php';
    require_once 'includes/admin-functions.php';
    
    // Initialize security
    Security::init();
    
    // Initialize admin auth
    $adminAuth = new AdminAuth($db);
    
    // Get current page
    $page = Security::sanitizeInput($_GET['page'] ?? 'dashboard');
    
    // Check if admin is logged in
    if (!$adminAuth->isLoggedIn()) {
        if ($page !== 'login') {
            adminRedirect('?page=login');
        }
    } else {
        // Redirect to dashboard if trying to access login while logged in
        if ($page === 'login') {
            adminRedirect('?page=dashboard');
        }
    }
    
    // Define allowed pages
    $allowedPages = [
        'dashboard', 'login', 'products', 'add-product', 'edit-product', 'categories', 
        'orders', 'customers', 'settings', 'reports', 'coupons', 'brands',
        'reviews', 'inventory', 'shipping', 'taxes', 'profile'
    ];
    
    // Validate page
    if (!in_array($page, $allowedPages)) {
        $page = 'dashboard';
    }
    
    // Set page title
    $pageTitle = ucfirst(str_replace('-', ' ', $page));
    
    // Include header (only if not login page)
    if ($page !== 'login') {
        include 'includes/header.php';
    }
    
    // Route to appropriate page
    switch ($page) {
        case 'login':
            include 'pages/login.php';
            break;
        case 'dashboard':
            include 'pages/dashboard.php';
            break;
        case 'products':
            include 'pages/products.php';
            break;
        case 'add-product':
            include 'pages/add-product.php';
            break;
        case 'edit-product':
            include 'pages/edit-product.php';
            break;
        case 'categories':
            include 'pages/categories.php';
            break;
        case 'orders':
            include 'pages/orders.php';
            break;
        case 'customers':
            include 'pages/customers.php';
            break;
        case 'settings':
            include 'pages/settings.php';
            break;
        case 'reports':
            include 'pages/reports.php';
            break;
        case 'coupons':
            include 'pages/coupons.php';
            break;
        case 'brands':
            include 'pages/brands.php';
            break;
        case 'reviews':
            include 'pages/reviews.php';
            break;
        case 'inventory':
            include 'pages/inventory.php';
            break;
        case 'shipping':
            include 'pages/shipping.php';
            break;
        case 'taxes':
            include 'pages/taxes.php';
            break;
        case 'profile':
            include 'pages/profile.php';
            break;
        default:
            include 'pages/404.php';
            break;
    }
    
    // Include footer (only if not login page)
    if ($page !== 'login') {
        include 'includes/footer.php';
    }
    
} catch (Exception $e) {
    // Show error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Error - Pahnava</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">Admin Panel Error</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                            </div>
                            
                            <p>There was an error loading the admin panel. Please try the following:</p>
                            
                            <div class="d-grid gap-2">
                                <a href="simple.php" class="btn btn-primary">Try Simple Admin</a>
                                <a href="test.php" class="btn btn-outline-secondary">Run Diagnostics</a>
                                <a href="setup.php" class="btn btn-outline-info">Run Setup</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
