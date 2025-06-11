<?php
/**
 * Reset Password Page - Password reset with token
 * 
 * @security Token validation, rate limiting, and secure password reset
 */

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('?page=account');
}

// Get reset token from URL
$token = Security::sanitizeInput($_GET['token'] ?? '');

if (empty($token)) {
    redirect('?page=forgot-password');
}

// Set page title
$pageTitle = 'Reset Password';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Rate limiting check
        if (!Security::checkRateLimit('reset_password', 5, 3600)) {
            $errors[] = 'Too many password reset attempts. Please try again later.';
        } else {
            // Sanitize input
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate passwords
            if (empty($newPassword)) {
                $errors[] = 'Password is required.';
            } elseif (!Security::validatePassword($newPassword)) {
                $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
            }
            
            if (empty($confirmPassword)) {
                $errors[] = 'Please confirm your password.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            
            // Process password reset if no validation errors
            if (empty($errors)) {
                $resetResult = $auth->resetPassword($token, $newPassword);
                
                if ($resetResult['success']) {
                    $success = $resetResult['message'];
                    
                    // Log security event
                    Security::logSecurityEvent('password_reset_completed', ['token' => substr($token, 0, 8) . '...']);
                } else {
                    $errors[] = $resetResult['error'];
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
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Reset Password</h2>
                        <p class="text-muted">Enter your new password below.</p>
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
                                <a href="?page=login" class="btn btn-success">Sign In Now</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Reset Password Form -->
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                            
                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required
                                           minlength="8"
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                                           placeholder="Enter new password">
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
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required
                                           placeholder="Confirm new password">
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
                            
                            <!-- Password Strength Indicator -->
                            <div class="password-strength mb-4" id="passwordStrength" style="display: none;">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="strength-text text-muted"></small>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-check me-2"></i>Reset Password
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
            
            <!-- Security Notice -->
            <div class="text-center mt-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-shield-alt text-success me-2"></i>Security Notice
                        </h6>
                        <p class="card-text text-muted small">
                            For your security, this password reset link will expire in 1 hour. 
                            After resetting your password, you'll be automatically signed out from all devices.
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

.input-group-text {
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

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.password-strength .progress {
    border-radius: 3px;
}

.strength-weak { background-color: #dc3545; }
.strength-fair { background-color: #ffc107; }
.strength-good { background-color: #20c997; }
.strength-strong { background-color: #28a745; }

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
        
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    function validatePasswordMatch() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    // Password strength indicator
    function updatePasswordStrength() {
        const value = password.value;
        const strength = calculatePasswordStrength(value);
        
        if (value.length > 0) {
            strengthIndicator.style.display = 'block';
            const progressBar = strengthIndicator.querySelector('.progress-bar');
            const strengthText = strengthIndicator.querySelector('.strength-text');
            
            const strengthLevels = [
                { min: 0, max: 1, width: 25, class: 'strength-weak', text: 'Weak' },
                { min: 2, max: 2, width: 50, class: 'strength-fair', text: 'Fair' },
                { min: 3, max: 3, width: 75, class: 'strength-good', text: 'Good' },
                { min: 4, max: 5, width: 100, class: 'strength-strong', text: 'Strong' }
            ];
            
            const level = strengthLevels.find(l => strength >= l.min && strength <= l.max) || strengthLevels[0];
            
            progressBar.style.width = level.width + '%';
            progressBar.className = 'progress-bar ' + level.class;
            strengthText.textContent = 'Password strength: ' + level.text;
        } else {
            strengthIndicator.style.display = 'none';
        }
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[@$!%*?&]/.test(password)) strength++;
        
        return strength;
    }
    
    password.addEventListener('input', function() {
        updatePasswordStrength();
        validatePasswordMatch();
    });
    
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
    
    // Auto-focus password field
    password.focus();
});
</script>

<?php
// Add page-specific scripts
$pageScripts = ['assets/js/auth.js'];
?>
