<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get statistics
$stats = [];

// Total orders today
$today_query = "SELECT COUNT(*) as count, SUM(total_amount) as total 
                FROM orders 
                WHERE DATE(created_at) = CURDATE()";
$stmt = $conn->query($today_query);
$stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Total orders this month
$month_query = "SELECT COUNT(*) as count, SUM(total_amount) as total 
                FROM orders 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                AND YEAR(created_at) = YEAR(CURDATE())";
$stmt = $conn->query($month_query);
$stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Total users
$users_query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $conn->query($users_query);
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Recent orders
$recent_orders_query = "SELECT o.*, u.full_name 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC 
                       LIMIT 10";
$stmt = $conn->query($recent_orders_query);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order status counts
$status_query = "SELECT status, COUNT(*) as count 
                 FROM orders 
                 GROUP BY status";
$stmt = $conn->query($status_query);
$order_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread contact messages count
$unread_messages_query = "SELECT COUNT(*) as count 
                         FROM contact_messages 
                         WHERE status = 'pending'";
$stmt = $conn->query($unread_messages_query);
$unread_messages = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
            <!-- Page content -->
            <div class="p-4">
                <!-- Mobile Nav -->
                <div class="navbar bg-base-100 shadow-lg rounded-box mb-4 lg:hidden">
                    <div class="flex-1">
                        <label for="my-drawer-2" class="btn btn-ghost drawer-button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </label>
                        <h1 class="text-xl font-bold px-4">Dashboard</h1>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Today's Orders -->
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-title">Today's Orders</div>
                            <div class="stat-value"><?php echo $stats['today']['count'] ?? 0; ?></div>
                            <div class="stat-desc">
                                RM<?php echo number_format($stats['today']['total'] ?? 0, 2); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Orders -->
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-title">Monthly Orders</div>
                            <div class="stat-value"><?php echo $stats['month']['count'] ?? 0; ?></div>
                            <div class="stat-desc">
                                RM<?php echo number_format($stats['month']['total'] ?? 0, 2); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Total Users -->
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-title">Total Users</div>
                            <div class="stat-value"><?php echo $stats['users']['count'] ?? 0; ?></div>
                            <div class="stat-desc">Active Customers</div>
                        </div>
                    </div>

                    <!-- Unread Messages -->
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-title">Unread Messages</div>
                            <div class="stat-value"><?php echo $unread_messages['count'] ?? 0; ?></div>
                            <div class="stat-desc">Pending Responses</div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Order Status Distribution</h2>
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Recent Orders</h2>
                            <div class="overflow-x-auto">
                                <table class="table table-zebra">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                                <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $order['status'] === 'pending' ? 'warning' : 
                                                            ($order['status'] === 'processing' ? 'info' : 
                                                            ($order['status'] === 'completed' ? 'success' : 'error')); 
                                                    ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <script>
        // Order Status Chart
        const statusCtx = document.getElementById('orderStatusChart');
        const statusData = <?php echo json_encode(array_column($order_status, 'count')); ?>;
        const statusLabels = <?php echo json_encode(array_column($order_status, 'status')); ?>;

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: [
                        '#36A2EB', // Processing
                        '#FFCE56', // Pending
                        '#4BC0C0', // Completed
                        '#FF6384'  // Cancelled
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
