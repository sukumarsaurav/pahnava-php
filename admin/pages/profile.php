<?php
/**
 * Admin Profile Page
 */

$currentAdmin = $adminAuth->getCurrentAdmin();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Profile</h1>
            <p class="text-muted">Manage your admin account</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Username:</strong> <?php echo htmlspecialchars($currentAdmin['username']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong> <?php echo htmlspecialchars($currentAdmin['email']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Name:</strong> <?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong> 
                        <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $currentAdmin['role'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Last Login:</strong> 
                        <?php echo $currentAdmin['last_login'] ? date('M j, Y g:i A', strtotime($currentAdmin['last_login'])) : 'Never'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-edit fa-3x text-primary mb-3"></i>
                    <h4>Profile Management</h4>
                    <p class="text-muted">Profile editing features are currently under development.</p>
                </div>
            </div>
        </div>
    </div>
</div>
