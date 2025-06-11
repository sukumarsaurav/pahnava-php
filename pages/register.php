<?php
/**
 * Registration Page - User account creation
 * 
 * @security Implements comprehensive validation, rate limiting, and CSRF protection
 */

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('?page=account');
}

// Set page title
$pageTitle = 'Create Account';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize input
        $userData = [
            'first_name' => Security::sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => Security::sanitizeInput($_POST['last_name'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'phone' => Security::sanitizeInput($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Additional validation
        if (!isset($_POST['terms_accepted'])) {
            $errors[] = 'You must accept the terms and conditions.';
        }
        
        // Attempt registration if no validation errors
        if (empty($errors)) {
            $registrationResult = $auth->register($userData);
            
            if ($registrationResult['success']) {
                $success = $registrationResult['message'];
                // Clear form data
                $userData = array_fill_keys(array_keys($userData), '');
            } else {
                $errors = $registrationResult['errors'];
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="card-title">Create Account</h2>
                        <p class="text-muted">Join Pahnava and start shopping</p>
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
                            <?php echo Security::sanitizeInput($success); ?>
                            <div class="mt-2">
                                <a href="?page=login" class="btn btn-sm btn-outline-success">Go to Login</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Registration Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                        
                        <!-- Name Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo isset($userData['first_name']) ? Security::sanitizeInput($userData['first_name']) : ''; ?>"
                                       required
                                       minlength="2"
                                       maxlength="50">
                                <div class="invalid-feedback">
                                    Please enter your first name (2-50 characters).
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo isset($userData['last_name']) ? Security::sanitizeInput($userData['last_name']) : ''; ?>"
                                       required
                                       minlength="2"
                                       maxlength="50">
                                <div class="invalid-feedback">
                                    Please enter your last name (2-50 characters).
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo isset($userData['email']) ? Security::sanitizeInput($userData['email']) : ''; ?>"
                                   required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo isset($userData['phone']) ? Security::sanitizeInput($userData['phone']) : ''; ?>"
                                   pattern="[+]?[0-9\s\-\(\)]{10,15}">
                            <div class="invalid-feedback">
                                Please enter a valid phone number.
                            </div>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required
                                       minlength="8"
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Password must be at least 8 characters with uppercase, lowercase, and number.
                            </div>
                            <div class="invalid-feedback">
                                Password must meet the requirements above.
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Passwords do not match.
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="terms_accepted" 
                                       name="terms_accepted" 
                                       required>
                                <label class="form-check-label" for="terms_accepted">
                                    I agree to the <a href="#" target="_blank">Terms and Conditions</a> 
                                    and <a href="#" target="_blank">Privacy Policy</a>
                                </label>
                                <div class="invalid-feedback">
                                    You must accept the terms and conditions.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>
                    
                    <!-- Social Registration -->
                    <div class="text-center mb-4">
                        <p class="text-muted">Or sign up with</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-danger" onclick="registerWithGoogle()">
                                <i class="fab fa-google me-2"></i>Continue with Google
                            </button>
                            <button class="btn btn-outline-primary" onclick="registerWithFacebook()">
                                <i class="fab fa-facebook-f me-2"></i>Continue with Facebook
                            </button>
                        </div>
                    </div>
                    
                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="?page=login" class="text-decoration-none">Sign in here</a>
                        </p>
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

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.alert {
    border-radius: 0.5rem;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
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
    // Toggle password visibility
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        toggle.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswordMatch() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        validatePasswordMatch();
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Real-time password strength indicator
    password.addEventListener('input', function() {
        const value = this.value;
        const strength = calculatePasswordStrength(value);
        updatePasswordStrengthIndicator(strength);
    });
});

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[@$!%*?&]/.test(password)) strength++;
    
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    // This could be enhanced with a visual strength indicator
    const strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'];
    
    // Implementation for visual feedback
}

// Social registration functions (placeholder implementations)
function registerWithGoogle() {
    showNotification('Google registration will be implemented with OAuth 2.0', 'info');
}

function registerWithFacebook() {
    showNotification('Facebook registration will be implemented with OAuth 2.0', 'info');
}

// Auto-focus first name field
document.getElementById('first_name').focus();
</script>

<?php
// Add page-specific scripts
$pageScripts = ['assets/js/auth.js'];
?>
