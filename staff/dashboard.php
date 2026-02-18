<?php
/**
 * Staff Dashboard
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require staff access

require_role('Staff');
require_once __DIR__ . '/../includes/staff_pending_check.php';
//new test
$current_user = get_logged_in_user();

// --- STATISTIC CARDS DATA ---

// 1. Orders Today (Proxy for "Assigned Today")
$orders_today_result = db_query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()");
$orders_today = $orders_today_result[0]['count'] ?? 0;

// 2. Total Pending Orders
$pending_orders_result = db_query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
$pending_orders = $pending_orders_result[0]['count'] ?? 0;

// 3. Completed Orders Today
$completed_today_result = db_query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed' AND DATE(order_date) = CURDATE()");
$completed_today = $completed_today_result[0]['count'] ?? 0;

// 4. Low Stock Items Count
$low_stock_count_result = db_query("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10 AND status = 'Activated'");
$low_stock_count = $low_stock_count_result[0]['count'] ?? 0;

// 5. Total Active Orders (Pending + Processing + Ready)
$active_orders_result = db_query("SELECT COUNT(*) as count FROM orders WHERE status IN ('Pending', 'Processing', 'Ready for Pickup')");
$active_orders = $active_orders_result[0]['count'] ?? 0;

// --- CHART DATA ---

// 2. Bar Chart – Orders by Status
$status_counts = db_query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$chart_status_labels = [];
$chart_status_data = [];
foreach ($status_counts as $row) {
    $chart_status_labels[] = $row['status'];
    $chart_status_data[] = (int)$row['count'];
}

// 3. Line Chart – Daily Order Trend (Last 7 Days)
$daily_trend = db_query("
    SELECT DATE(order_date) as date, COUNT(*) as count 
    FROM orders 
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(order_date)
    ORDER BY DATE(order_date) ASC
");
$chart_trend_labels = [];
$chart_trend_data = [];
$trend_map = [];
foreach ($daily_trend as $row) {
    $trend_map[$row['date']] = $row['count'];
}

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_trend_labels[] = date('M d', strtotime($date));
    $chart_trend_data[] = (int)($trend_map[$date] ?? 0);
}

// 4. Doughnut Chart – Completion Rate
$total_orders_result = db_query("SELECT COUNT(*) as count FROM orders");
$total_orders = $total_orders_result[0]['count'] ?? 1; // Prevent division by zero
$completion_rate = ($total_orders > 0) ? round(($completed_today / $total_orders) * 100) : 0;
// Using total completed for the rate chart
$total_completed_result = db_query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'");
$total_completed = $total_completed_result[0]['count'] ?? 0;
$remaining_orders = max(0, $total_orders - $total_completed);

// 5. Horizontal Bar Chart – Low Stock Products
$low_stock_products = db_query("
    SELECT name, stock_quantity 
    FROM products 
    WHERE status = 'Activated' 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");
$chart_low_stock_labels = [];
$chart_low_stock_data = [];
foreach ($low_stock_products as $row) {
    $chart_low_stock_labels[] = $row['name'];
    $chart_low_stock_data[] = (int)$row['stock_quantity'];
}

// Recent Notifications
$current_staff_id = get_user_id();
$dashboard_notifications = db_query("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", 'i', [$current_staff_id]);

// Recent Orders for the table
$recent_orders = db_query("
    SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    ORDER BY o.order_date DESC 
    LIMIT 10
");

$page_title = 'Staff Dashboard - PrintFlow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Include the modern dashboard CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/dashboard_modern.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include __DIR__ . '/../includes/admin_style.php'; // Keep sidebar styles ?>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header style="margin-bottom: 2rem;">
            <h1 class="page-title">Dashboard</h1>
            <p style="color: var(--text-muted); font-size: 18px;">Welcome back, <strong><?php echo htmlspecialchars($current_user['first_name']); ?></strong>! Here's what's happening today.</p>
        </header>

        <main>
            <!-- 1️⃣ Statistic Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Orders Today</div>
                    <div class="stat-value"><?php echo $orders_today; ?></div>
                    <div class="stat-sub">Newly received</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pending</div>
                    <div class="stat-value"><?php echo $pending_orders; ?></div>
                    <div class="stat-sub">Awaiting action</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value"><?php echo $completed_today; ?></div>
                    <div class="stat-sub">Finalized today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value <?php echo $low_stock_count > 0 ? 'text-danger' : ''; ?>" style="<?php echo $low_stock_count > 0 ? 'color: var(--danger);' : ''; ?>">
                        <?php echo $low_stock_count; ?>
                    </div>
                    <div class="stat-sub">Critical items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Orders</div>
                    <div class="stat-value"><?php echo $active_orders; ?></div>
                    <div class="stat-sub">Total in pipeline</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- 2️⃣ Bar Chart – Orders by Status -->
                <div class="card">
                    <h2 class="section-title">Orders by Status</h2>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- 3️⃣ Line Chart – Daily Order Trend -->
                <div class="card">
                    <h2 class="section-title">Daily Order Trend</h2>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- 4️⃣ Doughnut Chart – Completion Rate -->
                <div class="card">
                    <h2 class="section-title">Overall Completion</h2>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="completionChart"></canvas>
                    </div>
                    <div style="text-align: center; margin-top: 1rem; font-weight: 700; font-size: 24px;">
                        <?php echo round(($total_completed / $total_orders) * 100); ?>%
                    </div>
                </div>

                <!-- 5️⃣ Horizontal Bar Chart – Low Stock Products -->
                <div class="card">
                    <h2 class="section-title">Critical Inventory</h2>
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="card" style="margin-top: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 class="section-title" style="margin-bottom:0;">Recent Notifications</h2>
                    <a href="<?php echo BASE_URL; ?>/staff/notifications.php" class="btn" style="color:var(--primary); font-size:14px;">See All Notifications →</a>
                </div>
                
                <?php if (empty($dashboard_notifications)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:1rem;">No recent notifications.</p>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($dashboard_notifications as $notif): ?>
                            <div style="padding:12px; border-radius:8px; border:1px solid var(--border); <?php echo !$notif['is_read'] ? 'background:#eff6ff; border-color:#bfdbfe;' : ''; ?>">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <p style="font-size:14px; margin:0; <?php echo !$notif['is_read'] ? 'font-weight:600;' : ''; ?>">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </p>
                                    <span style="font-size:12px; color:var(--text-muted);"><?php echo format_datetime($notif['created_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders Table -->
            <div class="card" style="margin-top: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 class="section-title" style="margin-bottom:0;">Recent Orders</h2>
                    <a href="<?php echo BASE_URL; ?>/staff/orders.php" class="btn btn-primary">View All Orders →</a>
                </div>

                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td style="font-weight:700;">#<?php echo $order['order_id']; ?></td>
                                    <td style="font-weight:500;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo format_date($order['order_date']); ?></td>
                                    <td style="font-weight:700; color: var(--text-main);"><?php echo format_currency($order['total_amount']); ?></td>
                                    <td><?php echo status_badge($order['status'], 'order'); ?></td>
                                    <td style="text-align:center;">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn" style="color:var(--primary); background: #e0e7ff;">Update</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Chart Defaults
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 14;
Chart.defaults.color = '#64748b'; // var(--text-muted)
Chart.defaults.plugins.legend.labels.font = { weight: '600', size: 14 };

// Premium Animation Config
const premiumAnimation = {
    duration: 2000,
    easing: 'easeInOutQuart',
    from: (context) => {
        if (context.type === 'data') {
            if (context.mode === 'default' && !context.dropped) {
                context.dropped = true;
                return 0;
            }
        }
    }
};

// 2️⃣ Bar Chart – Orders by Status
new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_status_labels); ?>,
        datasets: [{
            label: 'Orders',
            data: <?php echo json_encode($chart_status_data); ?>,
            backgroundColor: [
                '#f59e0b', // Pending
                '#3b82f6', // Processing
                '#10b981', // Ready
                '#64748b', // Completed
                '#ef4444'  // Cancelled
            ],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: premiumAnimation,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, min: 0, ticks: { stepSize: 1, color: '#1e293b' } },
            x: { grid: { display: false }, ticks: { color: '#1e293b', font: { weight: '600' } } }
        }
    }
});

// 3️⃣ Line Chart – Daily Order Trend
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_trend_labels); ?>,
        datasets: [{
            label: 'Orders',
            data: <?php echo json_encode($chart_trend_data); ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 10,
            pointBackgroundColor: '#4f46e5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: premiumAnimation,
        plugins: { legend: { display: false } },
        interaction: { intersect: false, mode: 'index' },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1, color: '#1e293b' } },
            x: { grid: { display: false }, ticks: { color: '#1e293b', font: { weight: '600' } } }
        }
    }
});

// 4️⃣ Doughnut Chart – Completion Rate
new Chart(document.getElementById('completionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Remaining'],
        datasets: [{
            data: [<?php echo $total_completed; ?>, <?php echo $remaining_orders; ?>],
            backgroundColor: ['#10b981', '#f1f5f9'],
            hoverOffset: 15,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%',
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 2500,
            easing: 'easeOutBounce'
        },
        plugins: { legend: { position: 'bottom', labels: { padding: 20 } } }
    }
});

// 5️⃣ Horizontal Bar Chart – Low Stock Products
new Chart(document.getElementById('inventoryChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_low_stock_labels); ?>,
        datasets: [{
            label: 'Stock Quantity',
            data: <?php echo json_encode($chart_low_stock_data); ?>,
            backgroundColor: function(context) {
                const val = context.raw;
                return val < 5 ? '#ef4444' : '#f59e0b';
            },
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: premiumAnimation,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 2, color: '#1e293b' } },
            y: { grid: { display: false }, ticks: { color: '#1e293b', font: { weight: '600' } } }
        }
    }
});
</script>

</body>
</html>
