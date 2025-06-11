<?php
/**
 * Contact Page
 * 
 * @security CSRF protection and input validation
 */

// Set page title
$pageTitle = 'Contact Us';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Rate limiting
        if (!Security::checkRateLimit('contact_form', 3, 3600)) {
            $errors[] = 'Too many contact requests. Please try again later.';
        } else {
            // Sanitize input
            $name = Security::sanitizeInput($_POST['name'] ?? '');
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $subject = Security::sanitizeInput($_POST['subject'] ?? '');
            $message = Security::sanitizeInput($_POST['message'] ?? '');
            
            // Validate input
            if (empty($name)) {
                $errors[] = 'Name is required.';
            }
            
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!Security::validateEmail($email)) {
                $errors[] = 'Please enter a valid email address.';
            }
            
            if (empty($subject)) {
                $errors[] = 'Subject is required.';
            }
            
            if (empty($message)) {
                $errors[] = 'Message is required.';
            }
            
            // Process contact form if no validation errors
            if (empty($errors)) {
                // Here you would typically save to database and send email
                $success = 'Thank you for your message. We will get back to you soon!';
                
                // Log activity
                if ($auth->isLoggedIn()) {
                    logActivity($_SESSION['user_id'], 'contact_form_submitted', [
                        'subject' => $subject
                    ]);
                }
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Contact Us</h1>
            <p class="lead mb-5">We'd love to hear from you. Get in touch with us for any questions or support.</p>
        </div>
    </div>
    
    <div class="row">
        <!-- Contact Form -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Send us a Message</h5>
                    
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo Security::sanitizeInput($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display Success Message -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo Security::sanitizeInput($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo isset($_POST['name']) ? Security::sanitizeInput($_POST['name']) : ''; ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your name.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo isset($_POST['email']) ? Security::sanitizeInput($_POST['email']) : ''; ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Choose a subject...</option>
                                <option value="general" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="order" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'order') ? 'selected' : ''; ?>>Order Support</option>
                                <option value="product" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'product') ? 'selected' : ''; ?>>Product Question</option>
                                <option value="shipping" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'shipping') ? 'selected' : ''; ?>>Shipping & Returns</option>
                                <option value="technical" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="other" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a subject.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="6" 
                                      required><?php echo isset($_POST['message']) ? Security::sanitizeInput($_POST['message']) : ''; ?></textarea>
                            <div class="invalid-feedback">
                                Please enter your message.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="col-lg-4">
            <div class="contact-info">
                <!-- Contact Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Get in Touch</h5>
                        
                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                <div>
                                    <strong>Address</strong><br>
                                    <small class="text-muted">
                                        123 Fashion Street<br>
                                        Mumbai, Maharashtra 400001<br>
                                        India
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone text-primary me-3"></i>
                                <div>
                                    <strong>Phone</strong><br>
                                    <small class="text-muted">+91 9876543210</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-envelope text-primary me-3"></i>
                                <div>
                                    <strong>Email</strong><br>
                                    <small class="text-muted">support@pahnava.com</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-3"></i>
                                <div>
                                    <strong>Business Hours</strong><br>
                                    <small class="text-muted">
                                        Mon - Fri: 9:00 AM - 6:00 PM<br>
                                        Sat: 10:00 AM - 4:00 PM<br>
                                        Sun: Closed
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ Link -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h6 class="card-title">Frequently Asked Questions</h6>
                        <p class="card-text text-muted">
                            Find quick answers to common questions.
                        </p>
                        <a href="#" class="btn btn-outline-primary">View FAQ</a>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Follow Us</h6>
                        <div class="social-links">
                            <a href="#" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info btn-sm me-2">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger btn-sm me-2">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-dark btn-sm">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.contact-item:last-child {
    border-bottom: none;
}

.social-links .btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

@media (max-width: 991.98px) {
    .contact-info {
        margin-top: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
