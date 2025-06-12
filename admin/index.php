<?php
/**
 * Admin Panel - Main Entry Point
 *
 * @security Admin authentication required
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

} catch (Exception $e) {
    // Redirect to safe version if there are issues
    adminRedirect('index-safe.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $errors = [];

    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize input
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        // Validate input
        if (empty($username)) {
            $errors[] = 'Username is required.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        // Attempt login if no validation errors
        if (empty($errors)) {
            $loginResult = $adminAuth->login($username, $password, $rememberMe);

            if ($loginResult['success']) {
                // Redirect to dashboard
                adminRedirect('?page=dashboard');
            } else {
                $errors[] = $loginResult['error'];
            }
        }
    }

    // Store errors in session for display
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
    }
}

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
try {
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
} catch (Exception $e) {
    error_log("Admin page error: " . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred. Please try again.</div>';
}

// Include footer (only if not login page)
if ($page !== 'login') {
    include 'includes/footer.php';
}

?>
