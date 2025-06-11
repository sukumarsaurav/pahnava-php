<?php
/**
 * User Account Dashboard
 * 
 * @security Requires authentication
 */

// Require login
if (!$auth->isLoggedIn()) {
    redirect('?page=login');
}

// Set page title
$pageTitle = 'My Account';

// Get user data
$user = $auth->getCurrentUser();
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="account-sidebar">
                <div class="user-info text-center mb-4">
                    <div class="user-avatar mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <nav class="account-nav">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard" data-bs-toggle="pill">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#orders" data-bs-toggle="pill">
                                <i class="fas fa-shopping-bag me-2"></i>My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#profile" data-bs-toggle="pill">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#addresses" data-bs-toggle="pill">
                                <i class="fas fa-map-marker-alt me-2"></i>Addresses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?page=wishlist">
                                <i class="fas fa-heart me-2"></i>Wishlist
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#security" data-bs-toggle="pill">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard">
                    <h3 class="mb-4">Dashboard</h3>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                                    <h5>0</h5>
                                    <p class="text-muted">Total Orders</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                    <h5>0</h5>
                                    <p class="text-muted">Wishlist Items</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                    <h5>0</h5>
                                    <p class="text-muted">Reviews</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <h5>Recent Activity</h5>
                        <div class="card">
                            <div class="card-body text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>No recent activity</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <h3 class="mb-4">My Orders</h3>
                    
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            <i class="fas fa-shopping-bag fa-3x mb-3"></i>
                            <h5>No orders yet</h5>
                            <p>You haven't placed any orders yet.</p>
                            <a href="?page=shop" class="btn btn-primary">Start Shopping</a>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Tab -->
                <div class="tab-pane fade" id="profile">
                    <h3 class="mb-4">Profile Information</h3>
                    
                    <form class="profile-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>My Addresses</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="fas fa-plus me-2"></i>Add Address
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <h5>No addresses saved</h5>
                            <p>Add your addresses for faster checkout.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="security">
                    <h3 class="mb-4">Security Settings</h3>
                    
                    <div class="security-section mb-4">
                        <h5>Change Password</h5>
                        <form class="change-password-form">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                    
                    <div class="security-section">
                        <h5>Account Security</h5>
                        <div class="security-info">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Email Verification</span>
                                <span class="badge bg-success">Verified</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Last Login</span>
                                <span class="text-muted"><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAddressForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" name="address_line_1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control" name="address_line_2">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="isDefault">
                        <label class="form-check-label" for="isDefault">
                            Set as default address
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAddress()">Save Address</button>
            </div>
        </div>
    </div>
</div>

<style>
.account-sidebar {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.user-avatar {
    opacity: 0.8;
}

.account-nav .nav-link {
    color: #6c757d;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.account-nav .nav-link:hover,
.account-nav .nav-link.active {
    background-color: #007bff;
    color: white;
}

.security-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.security-info {
    background: white;
    padding: 1rem;
    border-radius: 0.25rem;
}

@media (max-width: 991.98px) {
    .account-sidebar {
        margin-bottom: 2rem;
    }
    
    .account-nav .nav {
        flex-direction: row;
        overflow-x: auto;
    }
    
    .account-nav .nav-item {
        flex-shrink: 0;
    }
}
</style>

<script>
function saveAddress() {
    const form = document.getElementById('addAddressForm');
    const formData = new FormData(form);
    
    // Simulate saving address
    showNotification('Address functionality will be implemented', 'info');
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addAddressModal'));
    modal.hide();
}
</script>
