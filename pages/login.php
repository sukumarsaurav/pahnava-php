<?php
/**
 * Login Page - User authentication
 * 
 * @security Implements rate limiting, CSRF protection, and secure authentication
 */

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('?page=account');
}

// Set page title
$pageTitle = 'Login';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize input
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Validate input
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!Security::validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // Attempt login if no validation errors
        if (empty($errors)) {
            $loginResult = $auth->login($email, $password, $rememberMe);
            
            if ($loginResult['success']) {
                // Redirect to intended page or account page
                $redirectUrl = $_GET['redirect'] ?? '?page=account';
                redirect($redirectUrl);
            } else {
                $errors[] = $loginResult['error'];
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
                        <h2 class="card-title">Welcome Back</h2>
                        <p class="text-muted">Sign in to your account</p>
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
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
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
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="remember_me" 
                                       name="remember_me"
                                       <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember_me">
                                    Remember me
                                </label>
                            </div>
                            <a href="?page=forgot-password" class="text-decoration-none">
                                Forgot password?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>
                    
                    <!-- Social Login -->
                    <div class="text-center mb-4">
                        <p class="text-muted">Or sign in with</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-danger" onclick="loginWithGoogle()">
                                <i class="fab fa-google me-2"></i>Continue with Google
                            </button>
                            <button class="btn btn-outline-primary" onclick="loginWithFacebook()">
                                <i class="fab fa-facebook-f me-2"></i>Continue with Facebook
                            </button>
                        </div>
                    </div>
                    
                    <!-- Register Link -->
                    <div class="text-center">
                        <p class="mb-0">
                            Don't have an account? 
                            <a href="?page=register" class="text-decoration-none">Create one here</a>
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

.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-outline-primary {
    border-color: #4267B2;
    color: #4267B2;
}

.btn-outline-primary:hover {
    background-color: #4267B2;
    border-color: #4267B2;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.alert {
    border-radius: 0.5rem;
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
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
    
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

// Social login functions (placeholder implementations)
function loginWithGoogle() {
    // Implement Google OAuth login
    showNotification('Google login will be implemented with OAuth 2.0', 'info');
}

function loginWithFacebook() {
    // Implement Facebook OAuth login
    showNotification('Facebook login will be implemented with OAuth 2.0', 'info');
}

// Auto-focus email field
document.getElementById('email').focus();
</script>

<?php
// Add page-specific scripts
$pageScripts = ['assets/js/auth.js'];
?>
