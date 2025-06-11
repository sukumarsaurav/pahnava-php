<?php
/**
 * Forgot Password Page - Password reset request
 * 
 * @security Rate limiting, CSRF protection, and secure token generation
 */

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('?page=account');
}

// Set page title
$pageTitle = 'Forgot Password';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Rate limiting check
        if (!Security::checkRateLimit('forgot_password', 3, 3600)) {
            $errors[] = 'Too many password reset requests. Please try again later.';
        } else {
            // Sanitize input
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            
            // Validate email
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!Security::validateEmail($email)) {
                $errors[] = 'Please enter a valid email address.';
            }
            
            // Process password reset request if no validation errors
            if (empty($errors)) {
                $resetResult = $auth->requestPasswordReset($email);
                
                if ($resetResult['success']) {
                    $success = $resetResult['message'];
                    
                    // Log security event
                    Security::logSecurityEvent('password_reset_requested', ['email' => $email]);
                } else {
                    // Don't reveal if email exists for security
                    $success = 'If the email exists in our system, a password reset link has been sent.';
                }
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Forgot Password?</h2>
                        <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                    </div>
                    
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
                            <div class="mt-3">
                                <a href="?page=login" class="btn btn-sm btn-outline-success">Back to Login</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Forgot Password Form -->
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo isset($_POST['email']) ? Security::sanitizeInput($_POST['email']) : ''; ?>"
                                           placeholder="Enter your email address"
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </form>
                        
                        <!-- Back to Login -->
                        <div class="text-center">
                            <p class="mb-0">
                                Remember your password? 
                                <a href="?page=login" class="text-decoration-none">Sign in here</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Additional Help -->
            <div class="text-center mt-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Need Help?</h6>
                        <p class="card-text text-muted">
                            If you're having trouble resetting your password, please contact our support team.
                        </p>
                        <a href="?page=contact" class="btn btn-outline-primary btn-sm">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Styles -->
<style>
.card {
    border: none;
    border-radius: 1rem;
}

.card-body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 1rem;
}

.form-control {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}

.input-group-text {
    border-radius: 0.5rem 0 0 0.5rem;
    background-color: #f8f9fa;
    border-color: #ced4da;
}

.btn {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-weight: 500;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
}

.alert {
    border-radius: 0.5rem;
}

.fa-key {
    opacity: 0.8;
}

@media (max-width: 575.98px) {
    .card-body {
        padding: 2rem 1.5rem !important;
    }
}
</style>

<!-- Page Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto-focus email field
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.focus();
    }
});
</script>

<?php
// Add page-specific scripts
$pageScripts = ['assets/js/auth.js'];
?>
