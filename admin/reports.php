<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get date range parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sales by date
$sales_query = "SELECT DATE(created_at) as date,
                       COUNT(*) as order_count,
                       SUM(total_amount) as total_sales,
                       AVG(total_amount) as average_order
                FROM orders
                WHERE status = 'completed'
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
$stmt = $conn->prepare($sales_query);
$stmt->execute([$start_date, $end_date]);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling items
$items_query = "SELECT f.name,
                       COUNT(*) as order_count,
                       SUM(oi.quantity) as total_quantity,
                       SUM(oi.quantity * f.price) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN food_items f ON oi.food_item_id = f.id
                WHERE o.status = 'completed'
                AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY f.id
                ORDER BY total_quantity DESC
                LIMIT 10";
$stmt = $conn->prepare($items_query);
$stmt->execute([$start_date, $end_date]);
$top_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sales by category
$categories_query = "SELECT c.name,
                           COUNT(DISTINCT o.id) as order_count,
                           SUM(oi.quantity) as total_quantity,
                           SUM(oi.quantity * f.price) as total_revenue
                    FROM categories c
                    JOIN food_items f ON f.category_id = c.id
                    JOIN order_items oi ON oi.food_item_id = f.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.status = 'completed'
                    AND DATE(o.created_at) BETWEEN ? AND ?
                    GROUP BY c.id
                    ORDER BY total_revenue DESC";
$stmt = $conn->prepare($categories_query);
$stmt->execute([$start_date, $end_date]);
$category_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$summary_query = "SELECT COUNT(*) as total_orders,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as average_order,
                        COUNT(DISTINCT user_id) as unique_customers
                 FROM orders
                 WHERE status = 'completed'
                 AND DATE(created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($summary_query);
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Aral's Food</title>
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
                <div class="navbar bg-base-100 shadow-lg rounded-box mb-4">
                    <div class="flex-1">
                        <label for="my-drawer-2" class="btn btn-ghost drawer-button lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </label>
                        <div class="text-sm breadcrumbs hidden sm:inline-block">
                            <ul>
                                <li><a href="dashboard.php">Dashboard</a></li>
                                <li>Reports</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <form method="GET" class="space-y-4">
                            <!-- Date Range Presets -->
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="setDateRange('today')" class="btn btn-sm">Today</button>
                                <button type="button" onclick="setDateRange('yesterday')" class="btn btn-sm">Yesterday</button>
                                <button type="button" onclick="setDateRange('last7')" class="btn btn-sm">Last 7 Days</button>
                                <button type="button" onclick="setDateRange('last30')" class="btn btn-sm">Last 30 Days</button>
                                <button type="button" onclick="setDateRange('thisMonth')" class="btn btn-sm">This Month</button>
                                <button type="button" onclick="setDateRange('lastMonth')" class="btn btn-sm">Last Month</button>
                            </div>
                            
                            <div class="flex flex-wrap gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Start Date</span>
                                    </label>
                                    <div class="relative">
                                        <input type="date" id="start_date" name="start_date" 
                                               value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); ?>" 
                                               class="input input-bordered pr-10" 
                                               onchange="validateDates()">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">End Date</span>
                                    </label>
                                    <div class="relative">
                                        <input type="date" id="end_date" name="end_date" 
                                               value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>" 
                                               class="input input-bordered pr-10" 
                                               onchange="validateDates()">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">&nbsp;</span>
                                    </label>
                                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <script>
                    function setDateRange(range) {
                        const today = new Date();
                        let start = new Date();
                        let end = new Date();

                        switch(range) {
                            case 'today':
                                start = end = today;
                                break;
                            case 'yesterday':
                                start = end = new Date(today.setDate(today.getDate() - 1));
                                break;
                            case 'last7':
                                start = new Date(today.setDate(today.getDate() - 6));
                                end = new Date();
                                break;
                            case 'last30':
                                start = new Date(today.setDate(today.getDate() - 29));
                                end = new Date();
                                break;
                            case 'thisMonth':
                                start = new Date(today.getFullYear(), today.getMonth(), 1);
                                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                                break;
                            case 'lastMonth':
                                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                                end = new Date(today.getFullYear(), today.getMonth(), 0);
                                break;
                        }

                        document.getElementById('start_date').value = formatDate(start);
                        document.getElementById('end_date').value = formatDate(end);
                        document.querySelector('form').submit();
                    }

                    function formatDate(date) {
                        return date.toISOString().split('T')[0];
                    }

                    function validateDates() {
                        const startDate = new Date(document.getElementById('start_date').value);
                        const endDate = new Date(document.getElementById('end_date').value);

                        if (endDate < startDate) {
                            alert('End date cannot be earlier than start date');
                            document.getElementById('end_date').value = document.getElementById('start_date').value;
                        }
                    }
                    </script>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="card bg-primary text-primary-content">
                        <div class="card-body">
                            <h2 class="card-title">Total Orders</h2>
                            <p class="text-3xl font-bold"><?php echo number_format($summary['total_orders']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-secondary text-secondary-content">
                        <div class="card-body">
                            <h2 class="card-title">Total Revenue</h2>
                            <p class="text-3xl font-bold">RM<?php echo number_format($summary['total_revenue'], 2); ?></p>
                        </div>
                    </div>
                    <div class="card bg-accent text-accent-content">
                        <div class="card-body">
                            <h2 class="card-title">Average Order</h2>
                            <p class="text-3xl font-bold">RM<?php echo number_format($summary['average_order'], 2); ?></p>
                        </div>
                    </div>
                    <div class="card bg-neutral text-neutral-content">
                        <div class="card-body">
                            <h2 class="card-title">Unique Customers</h2>
                            <p class="text-3xl font-bold"><?php echo number_format($summary['unique_customers']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sales Chart -->
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Daily Sales</h2>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Top Selling Items -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Top Selling Items</h2>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Orders</th>
                                            <th>Quantity</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo number_format($item['order_count']); ?></td>
                                                <td><?php echo number_format($item['total_quantity']); ?></td>
                                                <td>RM<?php echo number_format($item['total_revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sales by Category -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Sales by Category</h2>
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Orders</th>
                                            <th>Items Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_sales as $category): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo number_format($category['order_count']); ?></td>
                                                <td><?php echo number_format($category['total_quantity']); ?></td>
                                                <td>RM<?php echo number_format($category['total_revenue'], 2); ?></td>
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
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <script>
        // Prepare data for the sales chart
        const salesData = <?php echo json_encode(array_reverse($daily_sales)); ?>;
        
        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: salesData.map(row => row.date),
                datasets: [
                    {
                        label: 'Daily Sales (RM)',
                        data: salesData.map(row => row.total_sales),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    },
                    {
                        label: 'Orders',
                        data: salesData.map(row => row.order_count),
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script>
        document.addEventListener('keydown', function(e) {
            // Press / to focus search
            if (e.key === '/' && !e.ctrlKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.querySelector('input[type="search"]').focus();
            }
        });

        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                }
            });
        });
    </script>
</body>
</html>
