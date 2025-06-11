<?php
/**
 * Admin 404 Page
 */
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                <h1 class="display-4">404</h1>
                <h2>Page Not Found</h2>
                <p class="lead text-muted">The admin page you're looking for doesn't exist or is under development.</p>
                
                <div class="mt-4">
                    <a href="?page=dashboard" class="btn btn-primary me-2">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </a>
                </div>
                
                <div class="mt-4">
                    <small class="text-muted">
                        If you believe this is an error, please contact the system administrator.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
