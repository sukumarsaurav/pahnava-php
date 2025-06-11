<?php
/**
 * Admin Dashboard
 * 
 * @security Admin authentication required
 */

// Get dashboard statistics
$stats = getDashboardStats();
$recentOrders = getRecentOrders(5);
$salesData = getSalesData(30);
$topProducts = getTopSellingProducts(5);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentAdmin['first_name']); ?>!</p>
        </div>
        <div>
            <span class="text-muted">Last updated: <?php echo date('M j, Y g:i A'); ?></span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo formatAdminCurrency($stats['total_revenue']); ?></div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card warning">
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
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card <?php echo $stats['low_stock_products'] > 0 ? 'danger' : ''; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stats-label">Total Products</div>
                        <?php if ($stats['low_stock_products'] > 0): ?>
                            <small><?php echo $stats['low_stock_products']; ?> low stock</small>
                        <?php endif; ?>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Stats -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo number_format($stats['orders_today']); ?></h3>
                                <p class="text-muted mb-0">Orders Today</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo formatAdminCurrency($stats['revenue_today']); ?></h3>
                                <p class="text-muted mb-0">Revenue Today</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?page=add-product" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </a>
                        <a href="?page=orders" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>View Orders
                            <?php if ($stats['pending_orders'] > 0): ?>
                                <span class="badge bg-warning text-dark"><?php echo $stats['pending_orders']; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sales Overview (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topProducts)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topProducts as $product): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $product['image_url'] ?: '../assets/images/product-placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="rounded me-3" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted"><?php echo formatAdminCurrency($product['price']); ?></small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $product['total_sold'] ?? 0; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No sales data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="?page=orders" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($order['first_name']): ?>
                                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Guest</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatAdminCurrency($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getOrderStatusBadge($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="?page=orders&view=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5>No orders yet</h5>
                            <p class="text-muted">Orders will appear here once customers start purchasing.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sales chart
    const salesData = <?php echo json_encode($salesData); ?>;
    
    if (salesData && salesData.length > 0) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-IN', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue',
                    data: salesData.map(item => item.revenue),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: salesData.map(item => item.orders),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }
    
    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        location.reload();
    }, 300000);
});
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
