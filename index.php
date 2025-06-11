<?php
/**
 * Pahnava - Single Vendor Clothing Ecommerce Platform
 * Main Entry Point
 * 
 * @author Pahnava Development Team
 * @version 1.0.0
 * @security Production-ready with comprehensive security measures
 */

// Start secure session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Include core files
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth.php';

// Initialize security
Security::init();

// Get current page
$page = isset($_GET['page']) ? Security::sanitizeInput($_GET['page']) : 'home';

// Validate page parameter
$allowed_pages = [
    'home', 'shop', 'product', 'cart', 'checkout', 'account',
    'login', 'register', 'wishlist', 'orders', 'contact', 'about',
    'forgot-password', 'reset-password', 'verify-email'
];

if (!in_array($page, $allowed_pages)) {
    $page = '404';
}

// Include header
include INCLUDES_PATH . '/header.php';

// Route to appropriate page with error handling
try {
    switch ($page) {
        case 'home':
            include 'pages/home.php';
            break;
    case 'shop':
        include 'pages/shop.php';
        break;
    case 'product':
        include 'pages/product.php';
        break;
    case 'cart':
        include 'pages/cart.php';
        break;
    case 'checkout':
        include 'pages/checkout.php';
        break;
    case 'account':
        include 'pages/account.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'forgot-password':
        include 'pages/forgot-password.php';
        break;
    case 'reset-password':
        include 'pages/reset-password.php';
        break;
    case 'verify-email':
        include 'pages/verify-email.php';
        break;
    case 'wishlist':
        include 'pages/wishlist.php';
        break;
    case 'orders':
        include 'pages/orders.php';
        break;
    case 'contact':
        include 'pages/contact.php';
        break;
    case 'about':
        include 'pages/about.php';
        break;
    default:
        http_response_code(404);
        include 'pages/404.php';
        break;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Page inclusion error: " . $e->getMessage());

    // Show generic error page
    echo '<div class="container py-5 text-center">
            <h1>Something went wrong</h1>
            <p>We apologize for the inconvenience. Please try again later.</p>
            <a href="/" class="btn btn-primary">Go Home</a>
          </div>';
}

// Include footer
include INCLUDES_PATH . '/footer.php';
?>
