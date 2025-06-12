<?php
/**
 * Orders Management Page
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
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'desc';
$page = (int)($_GET['page_num'] ?? 1);
$perPage = 20;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(o.order_number LIKE ? OR o.billing_first_name LIKE ? OR o.billing_last_name LIKE ? OR u.email LIKE ?)";
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

try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
    $totalResult = $db->fetchRow($countQuery, $params);
    $totalOrders = $totalResult['total'] ?? 0;
    $totalPages = ceil($totalOrders / $perPage);

    // Get orders with user info and item counts
    $offset = ($page - 1) * $perPage;
    $allowedSorts = ['order_number', 'total_amount', 'status', 'created_at'];
    $sortColumn = in_array($sort, $allowedSorts) ? $sort : 'created_at';
    $sortOrder = $order === 'asc' ? 'ASC' : 'DESC';

    $ordersQuery = "SELECT o.*,
                           u.first_name, u.last_name, u.email,
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

} catch (Exception $e) {
    $orders = [];
    $totalOrders = 0;
    $totalPages = 0;
    $statusCountsArray = [];
    $errors[] = 'Error loading orders: ' . $e->getMessage();
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
                                        <?php if (!empty($order['first_name']) || !empty($order['billing_first_name'])): ?>
                                            <div>
                                                <?php
                                                $customerName = trim(($order['first_name'] ?? $order['billing_first_name']) . ' ' . ($order['last_name'] ?? $order['billing_last_name']));
                                                echo htmlspecialchars($customerName ?: 'Guest Customer');
                                                ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Guest Customer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo number_format($order['item_count'] ?? 0); ?> items</span>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                            <br><small class="text-success">-₹<?php echo number_format($order['discount_amount'], 2); ?> discount</small>
                                        <?php endif; ?>
                                        <?php if (!empty($order['coupon_code'])): ?>
                                            <br><small class="text-info">Coupon: <?php echo htmlspecialchars($order['coupon_code']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'processing' => 'primary',
                                            'shipped' => 'info',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            'refunded' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$order['status'] ?? 'pending'] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusColor; ?>">
                                            <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($order['created_at'])): ?>
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success"
                                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>)"
                                                    title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-info"
                                                    onclick="printOrder(<?php echo $order['id']; ?>)"
                                                    title="Print">
                                                <i class="fas fa-print"></i>
                                            </button>
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
                        <nav aria-label="Orders pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=orders&page_num=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=orders&page_num=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=orders&page_num=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
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
function viewOrder(orderId) {
    alert('Order details view will be implemented soon. Order ID: ' + orderId);
}

function updateOrderStatus(orderId) {
    const newStatus = prompt('Enter new status (pending, confirmed, processing, shipped, delivered, cancelled):');
    if (!newStatus) return;

    if (!confirm(`Are you sure you want to change the order status to "${newStatus}"?`)) {
        return;
    }

    alert('Order status update functionality will be implemented soon.');
}

function printOrder(orderId) {
    alert('Print order functionality will be implemented soon. Order ID: ' + orderId);
}

function exportOrders() {
    alert('Export orders functionality will be implemented soon.');
}
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
