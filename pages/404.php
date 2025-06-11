<?php
/**
 * 404 Error Page
 */

// Set page title
$pageTitle = 'Page Not Found';

// Set 404 header
http_response_code(404);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 text-center">
            <div class="error-page">
                <!-- 404 Illustration -->
                <div class="error-illustration mb-4">
                    <div class="error-number">
                        <span class="four">4</span>
                        <span class="zero">
                            <i class="fas fa-search"></i>
                        </span>
                        <span class="four">4</span>
                    </div>
                </div>
                
                <!-- Error Message -->
                <h1 class="error-title h2 mb-3">Oops! Page Not Found</h1>
                <p class="error-description text-muted mb-4">
                    The page you're looking for doesn't exist or has been moved. 
                    Don't worry, it happens to the best of us!
                </p>
                
                <!-- Search Box -->
                <div class="error-search mb-4">
                    <form action="?page=shop" method="GET" class="d-flex">
                        <input type="hidden" name="page" value="shop">
                        <input type="text" class="form-control me-2" name="search" 
                               placeholder="Search for products..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Action Buttons -->
                <div class="error-actions">
                    <a href="/" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                    <a href="?page=shop" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Now
                    </a>
                </div>
                
                <!-- Helpful Links -->
                <div class="helpful-links mt-5">
                    <h5 class="mb-3">You might be looking for:</h5>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="?page=shop&category=men" class="text-decoration-none">
                                <i class="fas fa-male me-2"></i>Men's Collection
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="?page=shop&category=women" class="text-decoration-none">
                                <i class="fas fa-female me-2"></i>Women's Collection
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="?page=shop&category=kids" class="text-decoration-none">
                                <i class="fas fa-child me-2"></i>Kids' Collection
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="?page=shop&featured=1" class="text-decoration-none">
                                <i class="fas fa-star me-2"></i>Featured Products
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="?page=shop&sale=1" class="text-decoration-none">
                                <i class="fas fa-tag me-2"></i>Sale Items
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="?page=contact" class="text-decoration-none">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 2rem 0;
}

.error-number {
    font-size: 8rem;
    font-weight: bold;
    color: #e9ecef;
    line-height: 1;
    margin-bottom: 1rem;
}

.error-number .four {
    color: #6c757d;
}

.error-number .zero {
    color: #007bff;
    position: relative;
    display: inline-block;
}

.error-number .zero i {
    font-size: 3rem;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.error-title {
    color: #333;
    font-weight: 600;
}

.error-description {
    font-size: 1.1rem;
    line-height: 1.6;
}

.error-search .form-control {
    border-radius: 0.5rem 0 0 0.5rem;
}

.error-search .btn {
    border-radius: 0 0.5rem 0.5rem 0;
    padding: 0.75rem 1.5rem;
}

.error-actions .btn {
    margin-bottom: 1rem;
}

.helpful-links {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 1rem;
    border: 2px solid #e9ecef;
}

.helpful-links a {
    color: #6c757d;
    transition: color 0.3s ease;
    display: block;
    padding: 0.5rem 0;
}

.helpful-links a:hover {
    color: #007bff;
}

.helpful-links i {
    width: 20px;
    text-align: center;
}

/* Animation for 404 number */
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.error-number .zero {
    animation: bounce 2s infinite;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .error-number {
        font-size: 5rem;
    }
    
    .error-number .zero i {
        font-size: 2rem;
    }
    
    .error-actions .btn {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .error-search {
        flex-direction: column;
    }
    
    .error-search .form-control {
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .error-search .btn {
        border-radius: 0.5rem;
        width: 100%;
    }
    
    .helpful-links {
        padding: 1.5rem;
    }
}

@media (max-width: 575.98px) {
    .error-number {
        font-size: 4rem;
    }
    
    .error-number .zero i {
        font-size: 1.5rem;
    }
}
</style>

<!-- Add some interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus search input
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.focus();
    }
    
    // Add click tracking for helpful links
    const helpfulLinks = document.querySelectorAll('.helpful-links a');
    helpfulLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Track which helpful link was clicked
            console.log('404 helpful link clicked:', this.textContent.trim());
        });
    });
});
</script>
