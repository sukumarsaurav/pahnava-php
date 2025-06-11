<?php
/**
 * Email Verification Page
 * 
 * @security Token validation and secure verification process
 */

// Get verification token from URL
$token = Security::sanitizeInput($_GET['token'] ?? '');

// Set page title
$pageTitle = 'Email Verification';

$message = '';
$messageType = 'info';

if (!empty($token)) {
    // Verify the email
    $verificationResult = $auth->verifyEmail($token);
    
    if ($verificationResult['success']) {
        $message = $verificationResult['message'];
        $messageType = 'success';
        
        // Log security event
        Security::logSecurityEvent('email_verified', ['token' => substr($token, 0, 8) . '...']);
    } else {
        $message = $verificationResult['error'];
        $messageType = 'danger';
    }
} else {
    $message = 'Invalid verification link. Please check your email for the correct link.';
    $messageType = 'danger';
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5 text-center">
                    <!-- Icon -->
                    <div class="mb-4">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h2 class="card-title mb-3">
                        <?php if ($messageType === 'success'): ?>
                            Email Verified!
                        <?php else: ?>
                            Verification Failed
                        <?php endif; ?>
                    </h2>
                    
                    <!-- Message -->
                    <div class="alert alert-<?php echo $messageType; ?> text-start">
                        <?php echo Security::sanitizeInput($message); ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <?php if ($messageType === 'success'): ?>
                            <a href="?page=login" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In Now
                            </a>
                            <a href="/" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Go to Homepage
                            </a>
                        <?php else: ?>
                            <a href="?page=register" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Register Again
                            </a>
                            <a href="?page=contact" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-2"></i>Contact Support
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div class="text-center mt-4">
                <div class="card">
                    <div class="card-body">
                        <?php if ($messageType === 'success'): ?>
                            <h6 class="card-title">Welcome to Pahnava!</h6>
                            <p class="card-text text-muted">
                                Your email has been successfully verified. You can now enjoy all the features of our platform.
                            </p>
                        <?php else: ?>
                            <h6 class="card-title">Need Help?</h6>
                            <p class="card-text text-muted">
                                If you're having trouble with email verification, our support team is here to help.
                            </p>
                        <?php endif; ?>
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

.btn {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-weight: 500;
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
    transform: translateY(-1px);
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

@media (max-width: 575.98px) {
    .card-body {
        padding: 2rem 1.5rem !important;
    }
}
</style>

<!-- Auto-redirect for successful verification -->
<?php if ($messageType === 'success'): ?>
<script>
// Auto-redirect to login page after 5 seconds
setTimeout(function() {
    if (confirm('Redirect to login page now?')) {
        window.location.href = '?page=login';
    }
}, 5000);
</script>
<?php endif; ?>

<?php
// Add page-specific scripts
$pageScripts = ['assets/js/auth.js'];
?>
