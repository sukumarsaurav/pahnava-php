<?php
/**
 * Simplified Admin Entry Point
 * For troubleshooting 500 errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any errors
ob_start();

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if database config exists
    if (!file_exists('../config/database.php')) {
        throw new Exception('Database configuration missing. Please run deploy.php first.');
    }
    
    // Include database
    require_once '../config/database.php';
    
    // Test database connection
    $testQuery = "SELECT 1 as test";
    $result = $db->fetchRow($testQuery);
    if (!$result) {
        throw new Exception('Database connection failed');
    }
    
    // Check if admin tables exist
    $adminUserTable = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
    if (!$adminUserTable) {
        throw new Exception('Admin tables not found. Please run admin/setup.php first.');
    }
    
    // Include required files
    require_once '../includes/security.php';
    require_once '../includes/functions.php';
    require_once 'includes/admin-auth.php';
    require_once 'includes/admin-functions.php';
    
    // Initialize security
    Security::init();
    
    // Initialize admin auth
    $adminAuth = new AdminAuth($db);
    
    // Get current page
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    
    // Check if admin is logged in
    if (!$adminAuth->isLoggedIn()) {
        if ($page !== 'login') {
            header('Location: simple.php?page=login');
            exit;
        }
    } else {
        // Redirect to dashboard if trying to access login while logged in
        if ($page === 'login') {
            header('Location: simple.php');
            exit;
        }
    }
    
    // Set page title
    $pageTitle = ucfirst(str_replace('-', ' ', $page));
    
    // Get current admin if logged in
    $currentAdmin = $adminAuth->isLoggedIn() ? $adminAuth->getCurrentAdmin() : null;
    
} catch (Exception $e) {
    // Clear output buffer
    ob_clean();
    
    // Show error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Setup Required</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">⚠️ Setup Required</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                            </div>
                            
                            <h5>Setup Steps:</h5>
                            <ol>
                                <li><strong>Database Configuration:</strong> 
                                    <a href="../deploy.php" class="btn btn-primary btn-sm">Run Deploy Script</a>
                                </li>
                                <li><strong>Admin Tables:</strong> 
                                    <a href="setup.php" class="btn btn-info btn-sm">Run Admin Setup</a>
                                </li>
                                <li><strong>Test Components:</strong> 
                                    <a href="test.php" class="btn btn-secondary btn-sm">Run Tests</a>
                                </li>
                            </ol>
                            
                            <div class="mt-4">
                                <h6>Quick Diagnosis:</h6>
                                <ul>
                                    <li>Database Config: <?php echo file_exists('../config/database.php') ? '✅' : '❌'; ?></li>
                                    <li>Security File: <?php echo file_exists('../includes/security.php') ? '✅' : '❌'; ?></li>
                                    <li>Admin Auth: <?php echo file_exists('includes/admin-auth.php') ? '✅' : '❌'; ?></li>
                                </ul>
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

// If we get here, everything is working
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Pahnava Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php if ($page === 'login'): ?>
        <!-- Login Page -->
        <div class="login-page">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="login-card">
                            <div class="text-center mb-4">
                                <i class="fas fa-store fa-3x text-primary mb-3"></i>
                                <h2 class="card-title">Admin Login</h2>
                                <p class="text-muted">Sign in to access the admin panel</p>
                            </div>
                            
                            <form method="POST" action="index.php">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">Default: admin / admin123</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Admin Dashboard -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="simple.php">
                    <i class="fas fa-store me-2"></i>Pahnava Admin
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../" target="_blank">View Site</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Quick Links</h5>
                            <div class="list-group list-group-flush">
                                <a href="simple.php" class="list-group-item">Dashboard</a>
                                <a href="index.php?page=products" class="list-group-item">Products</a>
                                <a href="index.php?page=orders" class="list-group-item">Orders</a>
                                <a href="index.php?page=customers" class="list-group-item">Customers</a>
                                <a href="index.php?page=settings" class="list-group-item">Settings</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h4>✅ Admin Panel Working!</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <strong>Success!</strong> The admin panel is now working correctly.
                            </div>
                            
                            <p>Welcome, <strong><?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?></strong>!</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>System Status:</h6>
                                    <ul>
                                        <li>✅ Database Connected</li>
                                        <li>✅ Admin Tables Created</li>
                                        <li>✅ Authentication Working</li>
                                        <li>✅ Security Initialized</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Next Steps:</h6>
                                    <ul>
                                        <li>Access <a href="index.php">Full Admin Panel</a></li>
                                        <li>Change your password</li>
                                        <li>Configure store settings</li>
                                        <li>Add your first products</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Full Admin Panel
                                </a>
                                <a href="test.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-check me-2"></i>Run System Tests
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
