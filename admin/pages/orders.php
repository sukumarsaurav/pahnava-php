<?php
/**
 * Orders Management Page
 * 
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_orders')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}

// Get filters
$search = Security::sanitizeInput($_GET['search'] ?? '');
$status = Security::sanitizeInput($_GET['status'] ?? '');
$dateFrom = Security::sanitizeInput($_GET['date_from'] ?? '');
$dateTo = Security::sanitizeInput($_GET['date_to'] ?? '');
$sort = Security::sanitizeInput($_GET['sort'] ?? 'created_at');
$order = Security::sanitizeInput($_GET['order'] ?? 'desc');
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($status)) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               $whereClause";
$totalResult = $db->fetchRow($countQuery, $params);
$totalOrders = $totalResult['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$offset = ($page - 1) * $perPage;
$allowedSorts = ['order_number', 'total_amount', 'status', 'created_at'];
$sortColumn = in_array($sort, $allowedSorts) ? $sort : 'created_at';
$sortOrder = $order === 'asc' ? 'ASC' : 'DESC';

$ordersQuery = "SELECT o.*, u.first_name, u.last_name, u.email,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                $whereClause
                ORDER BY o.$sortColumn $sortOrder
                LIMIT $perPage OFFSET $offset";

$orders = $db->fetchAll($ordersQuery, $params);

// Get order status counts
$statusCountsQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$statusCounts = $db->fetchAll($statusCountsQuery);
$statusCountsArray = [];
foreach ($statusCounts as $statusCount) {
    $statusCountsArray[$statusCount['status']] = $statusCount['count'];
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Orders</h1>
            <p class="text-muted">Manage customer orders and fulfillment</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="exportData('csv', 'ajax/export-orders.php')">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
        </div>
    </div>
    
    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1"><?php echo number_format($totalOrders); ?></h5>
                    <small class="text-muted">Total Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1 text-warning"><?php echo $statusCountsArray['pending'] ?? 0; ?></h5>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1 text-primary"><?php echo $statusCountsArray['processing'] ?? 0; ?></h5>
                    <small class="text-muted">Processing</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1 text-info"><?php echo $statusCountsArray['shipped'] ?? 0; ?></h5>
                    <small class="text-muted">Shipped</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1 text-success"><?php echo $statusCountsArray['delivered'] ?? 0; ?></h5>
                    <small class="text-muted">Delivered</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 class="mb-1 text-danger"><?php echo $statusCountsArray['cancelled'] ?? 0; ?></h5>
                    <small class="text-muted">Cancelled</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="orders">
                
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Order number, customer name, email...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
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
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="order_number" <?php echo $sort === 'order_number' ? 'selected' : ''; ?>>Order #</option>
                        <option value="total_amount" <?php echo $sort === 'total_amount' ? 'selected' : ''; ?>>Amount</option>
                        <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="?page=orders" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Orders (<?php echo number_format($totalOrders); ?>)</h5>
            
            <!-- Bulk Actions -->
            <div class="d-flex align-items-center">
                <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="mark_processing">Mark as Processing</option>
                    <option value="mark_shipped">Mark as Shipped</option>
                    <option value="mark_delivered">Mark as Delivered</option>
                    <option value="export_selected">Export Selected</option>
                </select>
                <button class="btn btn-sm btn-outline-primary" id="bulkActionBtn" disabled onclick="handleBulkActions()">
                    Apply
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($orders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" class="form-check-input" onchange="toggleSelectAll(this)">
                                </th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input" 
                                               name="selected_items[]" value="<?php echo $order['id']; ?>">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>#<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></strong>
                                            <?php if ($order['payment_method']): ?>
                                                <br><small class="text-muted"><?php echo ucfirst($order['payment_method']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($order['first_name']): ?>
                                            <div>
                                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Guest Customer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo $order['item_count']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatAdminCurrency($order['total_amount']); ?></strong>
                                        <?php if ($order['discount_amount'] > 0): ?>
                                            <br><small class="text-success">-<?php echo formatAdminCurrency($order['discount_amount']); ?> discount</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-<?php echo getOrderStatusBadge($order['status']); ?> dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <?php echo ucfirst($order['status']); ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending')">Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">Confirmed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')">Processing</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">Shipped</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">Delivered</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">Cancel</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="viewOrderDetails(<?php echo $order['id']; ?>)" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" 
                                                    onclick="printOrder(<?php echo $order['id']; ?>)" 
                                                    title="Print">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')" 
                                                        title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
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
                        $baseUrl = "?page=orders";
                        if ($search) $baseUrl .= "&search=" . urlencode($search);
                        if ($status) $baseUrl .= "&status=" . urlencode($status);
                        if ($dateFrom) $baseUrl .= "&date_from=" . urlencode($dateFrom);
                        if ($dateTo) $baseUrl .= "&date_to=" . urlencode($dateTo);
                        if ($sort !== 'created_at') $baseUrl .= "&sort=" . urlencode($sort);
                        if ($order !== 'desc') $baseUrl .= "&order=" . urlencode($order);
                        
                        echo generateAdminPagination($page, $totalPages, $baseUrl);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5>No orders found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
                            Try adjusting your filters or <a href="?page=orders">clear all filters</a>.
                        <?php else: ?>
                            Orders will appear here once customers start purchasing.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(orderId, newStatus) {
    if (!confirm(`Are you sure you want to change the order status to "${newStatus}"?`)) {
        return;
    }
    
    makeAjaxRequest('ajax/update-order-status.php', {
        method: 'POST',
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to update order status', 'danger');
        }
    });
}

function viewOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load order details
    makeAjaxRequest(`ajax/get-order-details.php?id=${orderId}`, {
        method: 'GET'
    })
    .then(data => {
        if (data.success) {
            content.innerHTML = data.html;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load order details: ${data.message || 'Unknown error'}
                </div>
            `;
        }
    })
    .catch(error => {
        content.innerHTML = `
            <div class="alert alert-danger">
                Failed to load order details. Please try again.
            </div>
        `;
    });
}

function printOrder(orderId) {
    window.open(`ajax/print-order.php?id=${orderId}`, '_blank');
}
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
