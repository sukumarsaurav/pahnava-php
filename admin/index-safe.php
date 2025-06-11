<?php
/**
 * Safe Admin Panel Entry Point
 * Handles missing tables and other issues gracefully
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for redirects
function redirect($url) {
    header("Location: $url");
    exit;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if setup is needed
function checkSetupRequired() {
    // Check if database config exists
    if (!file_exists('../config/database.php')) {
        return 'database_config';
    }
    
    try {
        require_once '../config/database.php';
        
        // Check if admin tables exist
        $adminUserTable = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
        if (!$adminUserTable) {
            return 'admin_tables';
        }
        
        // Check if admin user exists
        $adminUser = $db->fetchRow("SELECT id FROM admin_users LIMIT 1");
        if (!$adminUser) {
            return 'admin_user';
        }
        
        return false; // No setup required
        
    } catch (Exception $e) {
        return 'database_error';
    }
}

// Check setup status
$setupRequired = checkSetupRequired();

if ($setupRequired) {
    // Show setup page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Setup Required - Pahnava</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($setupRequired === 'database_config'): ?>
                                <div class="alert alert-danger">
                                    <strong>Database Configuration Missing</strong><br>
                                    The database configuration file is missing.
                                </div>
                                <p><strong>Solution:</strong> Run the deployment script to create the database configuration.</p>
                                <a href="../deploy.php" class="btn btn-primary">
                                    <i class="fas fa-rocket me-2"></i>Run Deploy Script
                                </a>
                                
                            <?php elseif ($setupRequired === 'admin_tables'): ?>
                                <div class="alert alert-warning">
                                    <strong>Admin Tables Missing</strong><br>
                                    The admin tables have not been created yet.
                                </div>
                                <p><strong>Solution:</strong> Run the admin setup script to create the necessary tables.</p>
                                <a href="setup.php" class="btn btn-primary">
                                    <i class="fas fa-database me-2"></i>Run Admin Setup
                                </a>
                                
                            <?php elseif ($setupRequired === 'admin_user'): ?>
                                <div class="alert alert-info">
                                    <strong>Admin User Missing</strong><br>
                                    No admin users found in the database.
                                </div>
                                <p><strong>Solution:</strong> Run the admin setup script to create the default admin user.</p>
                                <a href="setup.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create Admin User
                                </a>
                                
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <strong>Database Error</strong><br>
                                    There was an error connecting to the database.
                                </div>
                                <p><strong>Solution:</strong> Check your database configuration and try again.</p>
                                <a href="test.php" class="btn btn-secondary">
                                    <i class="fas fa-bug me-2"></i>Run Diagnostics
                                </a>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <h6>Setup Progress:</h6>
                            <div class="progress mb-3">
                                <?php
                                $progress = 0;
                                if (file_exists('../config/database.php')) $progress += 33;
                                if ($setupRequired !== 'admin_tables' && $setupRequired !== 'database_config') $progress += 33;
                                if ($setupRequired === false) $progress = 100;
                                ?>
                                <div class="progress-bar" style="width: <?php echo $progress; ?>%"><?php echo $progress; ?>%</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-database fa-2x <?php echo file_exists('../config/database.php') ? 'text-success' : 'text-muted'; ?>"></i>
                                        <p class="small mt-1">Database Config</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-table fa-2x <?php echo ($setupRequired !== 'admin_tables' && $setupRequired !== 'database_config') ? 'text-success' : 'text-muted'; ?>"></i>
                                        <p class="small mt-1">Admin Tables</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-user-shield fa-2x <?php echo $setupRequired === false ? 'text-success' : 'text-muted'; ?>"></i>
                                        <p class="small mt-1">Admin User</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Helpful Links:</h6>
                                <div class="btn-group-vertical w-100">
                                    <a href="../deploy.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-rocket me-2"></i>Deploy Script
                                    </a>
                                    <a href="setup.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-cogs me-2"></i>Admin Setup
                                    </a>
                                    <a href="test.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-check-circle me-2"></i>Run Tests
                                    </a>
                                    <a href="simple.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-play me-2"></i>Simple Admin
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If we get here, setup is complete - proceed with normal admin panel
try {
    // Include required files
    require_once '../config/database.php';
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
            redirect('?page=login');
        }
    } else {
        // Redirect to dashboard if trying to access login while logged in
        if ($page === 'login') {
            redirect('?page=dashboard');
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
    error_log("Admin panel error: " . $e->getMessage());
    
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
