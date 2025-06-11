<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pahnava - Premium Clothing Store. Discover the latest fashion trends in men's, women's and kids' clothing.">
    <meta name="keywords" content="clothing, fashion, men, women, kids, shirts, dresses, jeans, accessories">
    <meta name="author" content="Pahnava">
    
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Pahnava' : 'Pahnava - Premium Clothing Store'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?php echo Security::getCSRFToken(); ?>">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>
                        <i class="fas fa-phone me-2"></i>+91 9876543210
                        <i class="fas fa-envelope ms-3 me-2"></i>support@pahnava.com
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        <i class="fas fa-truck me-2"></i>Free shipping on orders over ₹999
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header bg-white shadow-sm sticky-top">
        <div class="container">
            <div class="row align-items-center py-3">
                <!-- Logo -->
                <div class="col-6 col-md-3">
                    <a href="/" class="logo text-decoration-none">
                        <h2 class="mb-0 text-primary fw-bold">Pahnava</h2>
                    </a>
                </div>
                
                <!-- Search Bar (Desktop) -->
                <div class="col-md-6 d-none d-md-block">
                    <form class="search-form" action="?page=shop" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search for products..." 
                                   value="<?php echo isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Header Actions -->
                <div class="col-6 col-md-3">
                    <div class="header-actions d-flex justify-content-end align-items-center">
                        <!-- User Account -->
                        <div class="dropdown me-3">
                            <a href="#" class="text-dark text-decoration-none dropdown-toggle" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                                <span class="d-none d-lg-inline ms-1">
                                    <?php if ($auth->isLoggedIn()): ?>
                                        <?php echo Security::sanitizeInput($_SESSION['user_name']); ?>
                                    <?php else: ?>
                                        Account
                                    <?php endif; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($auth->isLoggedIn()): ?>
                                    <li><a class="dropdown-item" href="?page=account">My Account</a></li>
                                    <li><a class="dropdown-item" href="?page=orders">My Orders</a></li>
                                    <li><a class="dropdown-item" href="?page=wishlist">Wishlist</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="?page=login">Login</a></li>
                                    <li><a class="dropdown-item" href="?page=register">Register</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <!-- Wishlist -->
                        <a href="?page=wishlist" class="text-dark text-decoration-none me-3 position-relative">
                            <i class="fas fa-heart"></i>
                            <span class="wishlist-count badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
                                <?php echo getWishlistCount(); ?>
                            </span>
                        </a>
                        
                        <!-- Shopping Cart -->
                        <a href="?page=cart" class="text-dark text-decoration-none position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count badge bg-primary position-absolute top-0 start-100 translate-middle rounded-pill">
                                <?php echo getCartCount(); ?>
                            </span>
                        </a>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="btn btn-outline-primary ms-3 d-md-none" type="button" 
                                data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Search -->
            <div class="row d-md-none mb-3">
                <div class="col-12">
                    <form class="search-form" action="?page=shop" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search for products..." 
                                   value="<?php echo isset($_GET['search']) ? Security::sanitizeInput($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Mega Menu Navigation -->
    <nav class="mega-menu bg-light border-top d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-pills justify-content-center py-2">
                        <li class="nav-item dropdown mega-dropdown">
                            <a class="nav-link dropdown-toggle" href="?page=shop&category=men" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                Men
                            </a>
                            <div class="dropdown-menu mega-menu-content">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Clothing</h6>
                                            <a class="dropdown-item" href="?page=shop&category=men-shirts">Shirts</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-tshirts">T-Shirts</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-jeans">Jeans</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-formal">Formal Wear</a>
                                        </div>
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Footwear</h6>
                                            <a class="dropdown-item" href="?page=shop&category=men-casual-shoes">Casual Shoes</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-formal-shoes">Formal Shoes</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-sneakers">Sneakers</a>
                                        </div>
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Accessories</h6>
                                            <a class="dropdown-item" href="?page=shop&category=men-watches">Watches</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-belts">Belts</a>
                                            <a class="dropdown-item" href="?page=shop&category=men-wallets">Wallets</a>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mega-menu-banner">
                                                <img src="assets/images/men-banner.jpg" alt="Men's Collection" class="img-fluid rounded">
                                                <div class="banner-content">
                                                    <h5>New Arrivals</h5>
                                                    <p>Up to 50% Off</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <li class="nav-item dropdown mega-dropdown">
                            <a class="nav-link dropdown-toggle" href="?page=shop&category=women" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                Women
                            </a>
                            <div class="dropdown-menu mega-menu-content">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Clothing</h6>
                                            <a class="dropdown-item" href="?page=shop&category=women-dresses">Dresses</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-tops">Tops</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-jeans">Jeans</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-ethnic">Ethnic Wear</a>
                                        </div>
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Footwear</h6>
                                            <a class="dropdown-item" href="?page=shop&category=women-heels">Heels</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-flats">Flats</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-sneakers">Sneakers</a>
                                        </div>
                                        <div class="col-md-3">
                                            <h6 class="dropdown-header">Accessories</h6>
                                            <a class="dropdown-item" href="?page=shop&category=women-jewelry">Jewelry</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-bags">Bags</a>
                                            <a class="dropdown-item" href="?page=shop&category=women-scarves">Scarves</a>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mega-menu-banner">
                                                <img src="assets/images/women-banner.jpg" alt="Women's Collection" class="img-fluid rounded">
                                                <div class="banner-content">
                                                    <h5>Trending Now</h5>
                                                    <p>Latest Fashion</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="?page=shop&category=kids">Kids</a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="?page=shop&category=accessories">Accessories</a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="?page=shop&featured=1">Featured</a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="?page=shop&sale=1">Sale</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Offcanvas -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-unstyled">
                <li class="mb-2">
                    <a href="?page=shop&category=men" class="text-decoration-none d-block py-2">Men</a>
                </li>
                <li class="mb-2">
                    <a href="?page=shop&category=women" class="text-decoration-none d-block py-2">Women</a>
                </li>
                <li class="mb-2">
                    <a href="?page=shop&category=kids" class="text-decoration-none d-block py-2">Kids</a>
                </li>
                <li class="mb-2">
                    <a href="?page=shop&category=accessories" class="text-decoration-none d-block py-2">Accessories</a>
                </li>
                <li class="mb-2">
                    <a href="?page=shop&featured=1" class="text-decoration-none d-block py-2">Featured</a>
                </li>
                <li class="mb-2">
                    <a href="?page=shop&sale=1" class="text-decoration-none d-block py-2">Sale</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php displayFlashMessage(); ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">

<?php
// Helper functions for header
function getCartCount() {
    global $db, $auth;
    
    if ($auth->isLoggedIn()) {
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
        $result = $db->fetchRow($query, [$_SESSION['user_id']]);
    } else {
        $sessionId = session_id();
        $query = "SELECT SUM(quantity) as count FROM cart WHERE session_id = ?";
        $result = $db->fetchRow($query, [$sessionId]);
    }
    
    return $result['count'] ?? 0;
}

function getWishlistCount() {
    global $db, $auth;
    
    if (!$auth->isLoggedIn()) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $result = $db->fetchRow($query, [$_SESSION['user_id']]);
    
    return $result['count'] ?? 0;
}
?>
