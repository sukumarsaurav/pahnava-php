<?php
/**
 * Reports Page
 *
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('view_reports')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    adminRedirect('?page=dashboard');
}

// Get date range from filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today
$reportType = $_GET['report_type'] ?? 'overview';

// Validate dates
if (!strtotime($dateFrom) || !strtotime($dateTo)) {
    $dateFrom = date('Y-m-01');
    $dateTo = date('Y-m-d');
}

try {
    // Sales Overview
    $salesQuery = "SELECT
                       COUNT(*) as total_orders,
                       SUM(total_amount) as total_revenue,
                       AVG(total_amount) as avg_order_value,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                       SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                   FROM orders
                   WHERE DATE(created_at) BETWEEN ? AND ?";
    $salesData = $db->fetchRow($salesQuery, [$dateFrom, $dateTo]);

    // Daily sales for chart
    $dailySalesQuery = "SELECT
                            DATE(created_at) as date,
                            COUNT(*) as orders,
                            SUM(total_amount) as revenue
                        FROM orders
                        WHERE DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY DATE(created_at)";
    $dailySales = $db->fetchAll($dailySalesQuery, [$dateFrom, $dateTo]);

    // Top products
    $topProductsQuery = "SELECT
                             p.name,
                             p.sku,
                             SUM(oi.quantity) as total_sold,
                             SUM(oi.price * oi.quantity) as total_revenue
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         JOIN orders o ON oi.order_id = o.id
                         WHERE DATE(o.created_at) BETWEEN ? AND ?
                         GROUP BY p.id
                         ORDER BY total_sold DESC
                         LIMIT 10";
    $topProducts = $db->fetchAll($topProductsQuery, [$dateFrom, $dateTo]);

    // Customer statistics
    $customerStatsQuery = "SELECT
                               COUNT(DISTINCT user_id) as unique_customers,
                               COUNT(*) as total_orders,
                               AVG(total_amount) as avg_order_value
                           FROM orders
                           WHERE DATE(created_at) BETWEEN ? AND ?";
    $customerStats = $db->fetchRow($customerStatsQuery, [$dateFrom, $dateTo]);

    // New customers
    $newCustomersQuery = "SELECT COUNT(*) as new_customers
                          FROM users
                          WHERE DATE(created_at) BETWEEN ? AND ?";
    $newCustomers = $db->fetchRow($newCustomersQuery, [$dateFrom, $dateTo]);

    // Payment methods
    $paymentMethodsQuery = "SELECT
                                payment_method,
                                COUNT(*) as count,
                                SUM(total_amount) as revenue
                            FROM orders
                            WHERE DATE(created_at) BETWEEN ? AND ?
                            GROUP BY payment_method
                            ORDER BY count DESC";
    $paymentMethods = $db->fetchAll($paymentMethodsQuery, [$dateFrom, $dateTo]);

} catch (Exception $e) {
    $salesData = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'completed_orders' => 0, 'pending_orders' => 0, 'cancelled_orders' => 0];
    $dailySales = [];
    $topProducts = [];
    $customerStats = ['unique_customers' => 0, 'total_orders' => 0, 'avg_order_value' => 0];
    $newCustomers = ['new_customers' => 0];
    $paymentMethods = [];
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reports & Analytics</h1>
            <p class="text-muted">Business insights and performance metrics</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="exportReport()">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="reports">

                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" name="report_type">
                        <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="sales" <?php echo $reportType === 'sales' ? 'selected' : ''; ?>>Sales</option>
                        <option value="products" <?php echo $reportType === 'products' ? 'selected' : ''; ?>>Products</option>
                        <option value="customers" <?php echo $reportType === 'customers' ? 'selected' : ''; ?>>Customers</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="?page=reports" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number">₹<?php echo number_format($salesData['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($salesData['total_orders'] ?? 0); ?></div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number">₹<?php echo number_format($salesData['avg_order_value'] ?? 0, 2); ?></div>
                        <div class="stats-label">Avg Order Value</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?php echo number_format($customerStats['unique_customers'] ?? 0); ?></div>
                        <div class="stats-label">Unique Customers</div>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Detailed Reports -->
    <div class="row">
        <!-- Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sales Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Order Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Completed</span>
                            <span class="text-success"><?php echo number_format($salesData['completed_orders'] ?? 0); ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $salesData['total_orders'] > 0 ? ($salesData['completed_orders'] / $salesData['total_orders'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Pending</span>
                            <span class="text-warning"><?php echo number_format($salesData['pending_orders'] ?? 0); ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo $salesData['total_orders'] > 0 ? ($salesData['pending_orders'] / $salesData['total_orders'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Cancelled</span>
                            <span class="text-danger"><?php echo number_format($salesData['cancelled_orders'] ?? 0); ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: <?php echo $salesData['total_orders'] > 0 ? ($salesData['cancelled_orders'] / $salesData['total_orders'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topProducts)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($product['sku']); ?></small></td>
                                        <td><span class="badge bg-primary"><?php echo number_format($product['total_sold']); ?></span></td>
                                        <td><strong>₹<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No product sales data available for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($paymentMethods)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalPaymentOrders = array_sum(array_column($paymentMethods, 'count'));
                                    foreach ($paymentMethods as $method):
                                        $percentage = $totalPaymentOrders > 0 ? ($method['count'] / $totalPaymentOrders * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo ucfirst(htmlspecialchars($method['payment_method'])); ?></td>
                                        <td><?php echo number_format($method['count']); ?></td>
                                        <td>₹<?php echo number_format($method['revenue'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <small><?php echo number_format($percentage, 1); ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No payment data available for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h4 class="text-primary"><?php echo number_format($newCustomers['new_customers'] ?? 0); ?></h4>
                            <p class="text-muted mb-0">New Customers</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="text-success"><?php echo $salesData['total_orders'] > 0 ? number_format(($salesData['completed_orders'] / $salesData['total_orders'] * 100), 1) : 0; ?>%</h4>
                            <p class="text-muted mb-0">Completion Rate</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="text-info"><?php echo $customerStats['unique_customers'] > 0 ? number_format(($customerStats['total_orders'] / $customerStats['unique_customers']), 1) : 0; ?></h4>
                            <p class="text-muted mb-0">Orders per Customer</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4 class="text-warning"><?php echo count($topProducts); ?></h4>
                            <p class="text-muted mb-0">Products Sold</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            <?php
            foreach ($dailySales as $sale) {
                echo "'" . date('M j', strtotime($sale['date'])) . "',";
            }
            ?>
        ],
        datasets: [{
            label: 'Revenue',
            data: [
                <?php
                foreach ($dailySales as $sale) {
                    echo $sale['revenue'] . ',';
                }
                ?>
            ],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            yAxisID: 'y'
        }, {
            label: 'Orders',
            data: [
                <?php
                foreach ($dailySales as $sale) {
                    echo $sale['orders'] . ',';
                }
                ?>
            ],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Date'
                }
            },
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
        }
    }
});

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('ajax/export-report.php?' + params.toString(), '_blank');
}
</script>
