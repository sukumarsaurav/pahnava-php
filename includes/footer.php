    </main>

    <!-- Footer -->
    <footer class="footer bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Pahnava</h5>
                    <p class="text-light">Your premium destination for the latest fashion trends. Quality clothing for men, women, and kids.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="?page=about" class="text-light text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="?page=contact" class="text-light text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="?page=shop" class="text-light text-decoration-none">Shop</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Blog</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Careers</a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="mb-3">Categories</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="?page=shop&category=men" class="text-light text-decoration-none">Men</a></li>
                        <li class="mb-2"><a href="?page=shop&category=women" class="text-light text-decoration-none">Women</a></li>
                        <li class="mb-2"><a href="?page=shop&category=kids" class="text-light text-decoration-none">Kids</a></li>
                        <li class="mb-2"><a href="?page=shop&category=accessories" class="text-light text-decoration-none">Accessories</a></li>
                        <li class="mb-2"><a href="?page=shop&sale=1" class="text-light text-decoration-none">Sale</a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="mb-3">Customer Service</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Size Guide</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Shipping Info</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Returns</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Track Order</a></li>
                    </ul>
                </div>
                
                <!-- Newsletter -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="mb-3">Newsletter</h6>
                    <p class="text-light">Subscribe to get updates on new arrivals and exclusive offers.</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your email" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                    <small class="text-light">We respect your privacy. Unsubscribe anytime.</small>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">&copy; <?php echo date('Y'); ?> Pahnava. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="#" class="text-light text-decoration-none">Privacy Policy</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-light text-decoration-none">Terms of Service</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-light text-decoration-none">Cookie Policy</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="payment-methods bg-secondary py-3">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <span class="text-light">Secure Payment Methods:</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <img src="assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid" style="max-height: 30px;">
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="btn btn-primary back-to-top" id="backToTop" style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '<?php echo Security::getCSRFToken(); ?>';
        
        // Newsletter subscription
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            
            fetch('ajax/newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for subscribing!');
                    this.reset();
                } else {
                    alert(data.message || 'Subscription failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // Back to top button
        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
