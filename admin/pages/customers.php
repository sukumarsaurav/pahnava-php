<?php
/**
 * Customers Management Page
 * 
 * @security Admin authentication and permissions required
 */

// Simple permission check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ?page=login');
    exit;
}

$errors = [];
$success = '';

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';
$page = (int)($_GET['page_num'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($status)) {
    if ($status === 'active') {
        $conditions[] = "u.status = 'active'";
    } elseif ($status === 'inactive') {
        $conditions[] = "u.status = 'inactive'";
    } elseif ($status === 'suspended') {
        $conditions[] = "u.status = 'suspended'";
    } elseif ($status === 'verified') {
        $conditions[] = "u.email_verified = 1";
    } elseif ($status === 'unverified') {
        $conditions[] = "u.email_verified = 0";
    }
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(u.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(u.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM users u $whereClause";
    $totalResult = $db->fetchRow($countQuery, $params);
    $totalCustomers = $totalResult['total'] ?? 0;
    $totalPages = ceil($totalCustomers / $perPage);

    // Get customers
    $offset = ($page - 1) * $perPage;
    $allowedSorts = ['first_name', 'last_name', 'email', 'created_at'];
    $sortColumn = in_array($sort, $allowedSorts) ? $sort : 'created_at';
    $sortOrder = $order === 'asc' ? 'ASC' : 'DESC';

    $customersQuery = "SELECT u.*,
                              (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as total_orders,
                              (SELECT SUM(o.total_amount) FROM orders o WHERE o.user_id = u.id) as total_spent,
                              (SELECT MAX(o.created_at) FROM orders o WHERE o.user_id = u.id) as last_order_date
                       FROM users u
                       $whereClause
                       ORDER BY u.$sortColumn $sortOrder
                       LIMIT $perPage OFFSET $offset";

    $customers = $db->fetchAll($customersQuery, $params);

    // Get customer statistics
    $statsQuery = "SELECT
                       COUNT(*) as total_customers,
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                       SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_customers,
                       SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today
                    FROM users";
    $stats = $db->fetchRow($statsQuery);

} catch (Exception $e) {
    $customers = [];
    $totalCustomers = 0;
    $totalPages = 0;
    $stats = ['total_customers' => 0, 'active_customers' => 0, 'verified_customers' => 0, 'new_today' => 0];
    $errors[] = 'Error loading customers: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Customers</h1>
            <p class="text-muted">Manage customer accounts and information</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="exportData('csv', 'ajax/export-customers.php')">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
        </div>
    </div>
    
    <!-- Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['total_customers']); ?></div>
                        <div class="stats-label">Total Customers</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['active_customers']); ?></div>
                        <div class="stats-label">Active Customers</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['verified_customers']); ?></div>
                        <div class="stats-label">Verified Emails</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-envelope-check"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['new_today']); ?></div>
                        <div class="stats-label">New Today</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="customers">
                
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name, email, phone...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Email Verified</option>
                        <option value="unverified" <?php echo $status === 'unverified' ? 'selected' : ''; ?>>Email Unverified</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">Sort</label>
                    <select class="form-select" name="sort">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Joined</option>
                        <option value="first_name" <?php echo $sort === 'first_name' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="?page=customers" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Customers Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Customers (<?php echo number_format($totalCustomers); ?>)</h5>
            
            <!-- Bulk Actions -->
            <div class="d-flex align-items-center">
                <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="send_email">Send Email</option>
                    <option value="export_selected">Export Selected</option>
                </select>
                <button class="btn btn-sm btn-outline-primary" id="bulkActionBtn" disabled onclick="handleBulkActions()">
                    Apply
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($customers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" class="form-check-input" onchange="toggleSelectAll(this)">
                                </th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input" 
                                               name="selected_items[]" value="<?php echo $customer['id']; ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                                                <small class="text-muted">ID: <?php echo $customer['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div><?php echo htmlspecialchars($customer['email']); ?></div>
                                            <?php if ($customer['phone']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($customer['phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo number_format($customer['total_orders']); ?></span>
                                        <?php if ($customer['last_order_date']): ?>
                                            <br><small class="text-muted">Last: <?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <?php
                                            $statusColors = [
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'suspended' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$customer['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo ucfirst($customer['status']); ?>
                                            </span>
                                            <?php if ($customer['email_verified']): ?>
                                                <br><span class="badge bg-info">Email Verified</span>
                                            <?php else: ?>
                                                <br><span class="badge bg-warning">Email Unverified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($customer['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_login']): ?>
                                            <div>
                                                <?php echo date('M j, Y', strtotime($customer['last_login'])); ?>
                                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($customer['last_login'])); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="viewCustomerDetails(<?php echo $customer['id']; ?>)" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" 
                                                    onclick="sendCustomerEmail(<?php echo $customer['id']; ?>)" 
                                                    title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="More Actions">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="toggleCustomerStatus(<?php echo $customer['id']; ?>, <?php echo $customer['is_active'] ? 0 : 1; ?>)">
                                                        <?php echo $customer['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="resetCustomerPassword(<?php echo $customer['id']; ?>)">Reset Password</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <?php
                        $baseUrl = "?page=customers";
                        if ($search) $baseUrl .= "&search=" . urlencode($search);
                        if ($status) $baseUrl .= "&status=" . urlencode($status);
                        if ($dateFrom) $baseUrl .= "&date_from=" . urlencode($dateFrom);
                        if ($dateTo) $baseUrl .= "&date_to=" . urlencode($dateTo);
                        if ($sort !== 'created_at') $baseUrl .= "&sort=" . urlencode($sort);
                        if ($order !== 'desc') $baseUrl .= "&order=" . urlencode($order);
                        
                        // Simple pagination
                        echo '<nav aria-label="Customers pagination">';
                        echo '<ul class="pagination justify-content-center mb-0">';

                        if ($page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page_num=' . ($page - 1) . '">Previous</a></li>';
                        }

                        for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
                            $active = $i === $page ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page_num=' . $i . '">' . $i . '</a></li>';
                        }

                        if ($page < $totalPages) {
                            echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page_num=' . ($page + 1) . '">Next</a></li>';
                        }

                        echo '</ul>';
                        echo '</nav>';
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5>No customers found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
                            Try adjusting your filters or <a href="?page=customers">clear all filters</a>.
                        <?php else: ?>
                            Customers will appear here once they register on your website.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}
</style>

<script>
function viewCustomerDetails(customerId) {
    alert('Customer details view will be implemented soon. Customer ID: ' + customerId);
}

function toggleCustomerStatus(customerId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this customer?`)) {
        return;
    }
    
    makeAjaxRequest('ajax/toggle-customer-status.php', {
        method: 'POST',
        body: JSON.stringify({
            customer_id: customerId,
            status: newStatus
        })
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to update customer status', 'danger');
        }
    });
}

function sendCustomerEmail(customerId) {
    alert('Email functionality will be implemented soon. Customer ID: ' + customerId);
}

function resetCustomerPassword(customerId) {
    if (!confirm('Are you sure you want to reset this customer\'s password? They will receive an email with reset instructions.')) {
        return;
    }
    
    makeAjaxRequest('ajax/reset-customer-password.php', {
        method: 'POST',
        body: JSON.stringify({
            customer_id: customerId
        })
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Failed to reset password', 'danger');
        }
    });
}

function deleteCustomer(customerId) {
    if (!confirm('Are you sure you want to delete this customer? This action cannot be undone and will also delete all their orders and data.')) {
        return;
    }
    
    makeAjaxRequest('ajax/delete-customer.php', {
        method: 'POST',
        body: JSON.stringify({
            customer_id: customerId
        })
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to delete customer', 'danger');
        }
    });
}
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
