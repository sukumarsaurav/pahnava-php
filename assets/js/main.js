/**
 * Pahnava - Main JavaScript File
 * Handles common functionality across the site
 * 
 * @security All AJAX requests include CSRF protection
 */

// Global variables
let cartCount = 0;
let wishlistCount = 0;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize application
 */
function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize cart functionality
    initializeCart();
    
    // Initialize wishlist functionality
    initializeWishlist();
    
    // Initialize product quick view
    initializeQuickView();
    
    // Initialize image lazy loading
    initializeLazyLoading();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Update cart and wishlist counts
    updateCounts();
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize cart functionality
 */
function initializeCart() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
            e.preventDefault();
            const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
            addToCart(button);
        }
    });
    
    // Update cart quantity
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-quantity')) {
            updateCartQuantity(e.target);
        }
    });
    
    // Remove from cart
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-cart') || e.target.closest('.remove-from-cart')) {
            e.preventDefault();
            const button = e.target.classList.contains('remove-from-cart') ? e.target : e.target.closest('.remove-from-cart');
            removeFromCart(button);
        }
    });
}

/**
 * Add product to cart
 */
function addToCart(button) {
    const productId = button.dataset.productId;
    const variantId = button.dataset.variantId || null;
    const quantity = button.dataset.quantity || 1;
    
    // Show loading state
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner"></span> Adding...';
    button.disabled = true;
    
    // Make AJAX request
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            variant_id: variantId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount(data.cart_count);
            
            // Show success message
            showNotification('Product added to cart!', 'success');
            
            // Update button text
            button.innerHTML = '<i class="fas fa-check"></i> Added';
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        } else {
            showNotification(data.message || 'Failed to add product to cart', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

/**
 * Update cart quantity
 */
function updateCartQuantity(input) {
    const cartItemId = input.dataset.cartItemId;
    const quantity = parseInt(input.value);
    
    if (quantity < 1) {
        input.value = 1;
        return;
    }
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({
            action: 'update',
            cart_item_id: cartItemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart totals
            updateCartTotals(data.cart_data);
            updateCartCount(data.cart_count);
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

/**
 * Remove item from cart
 */
function removeFromCart(button) {
    const cartItemId = button.dataset.cartItemId;
    
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({
            action: 'remove',
            cart_item_id: cartItemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from DOM
            const cartItem = button.closest('.cart-item');
            if (cartItem) {
                cartItem.remove();
            }
            
            // Update cart totals
            updateCartTotals(data.cart_data);
            updateCartCount(data.cart_count);
            
            showNotification('Item removed from cart', 'success');
        } else {
            showNotification(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

/**
 * Initialize wishlist functionality
 */
function initializeWishlist() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-wishlist') || e.target.closest('.add-to-wishlist')) {
            e.preventDefault();
            const button = e.target.classList.contains('add-to-wishlist') ? e.target : e.target.closest('.add-to-wishlist');
            toggleWishlist(button);
        }
    });
}

/**
 * Toggle wishlist item
 */
function toggleWishlist(button) {
    const productId = button.dataset.productId;
    const isInWishlist = button.classList.contains('in-wishlist');
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({
            action: isInWishlist ? 'remove' : 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Toggle button state
            if (isInWishlist) {
                button.classList.remove('in-wishlist');
                button.innerHTML = '<i class="far fa-heart"></i>';
                showNotification('Removed from wishlist', 'success');
            } else {
                button.classList.add('in-wishlist');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                showNotification('Added to wishlist', 'success');
            }
            
            // Update wishlist count
            updateWishlistCount(data.wishlist_count);
        } else {
            showNotification(data.message || 'Please login to use wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

/**
 * Initialize product quick view
 */
function initializeQuickView() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('quick-view') || e.target.closest('.quick-view')) {
            e.preventDefault();
            const button = e.target.classList.contains('quick-view') ? e.target : e.target.closest('.quick-view');
            openQuickView(button.dataset.productId);
        }
    });
}

/**
 * Open product quick view modal
 */
function openQuickView(productId) {
    // Implementation for quick view modal
    // This would load product details via AJAX and show in a modal
    console.log('Quick view for product:', productId);
}

/**
 * Initialize lazy loading for images
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    // Add custom validation styles
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Update cart count in header
 */
function updateCartCount(count) {
    cartCount = count;
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'flex' : 'none';
    });
}

/**
 * Update wishlist count in header
 */
function updateWishlistCount(count) {
    wishlistCount = count;
    const wishlistCountElements = document.querySelectorAll('.wishlist-count');
    wishlistCountElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'flex' : 'none';
    });
}

/**
 * Update cart totals on cart page
 */
function updateCartTotals(cartData) {
    if (cartData) {
        const subtotalElement = document.querySelector('.cart-subtotal');
        const totalElement = document.querySelector('.cart-total');
        
        if (subtotalElement) {
            subtotalElement.textContent = '₹' + cartData.subtotal;
        }
        
        if (totalElement) {
            totalElement.textContent = '₹' + cartData.total;
        }
    }
}

/**
 * Update counts from server
 */
function updateCounts() {
    fetch('ajax/get-counts.php', {
        method: 'GET',
        headers: {
            'X-CSRF-Token': window.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            updateWishlistCount(data.wishlist_count);
        }
    })
    .catch(error => {
        console.error('Error updating counts:', error);
    });
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

/**
 * Debounce function for search
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
