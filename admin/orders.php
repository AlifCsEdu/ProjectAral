<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    
    header("Location: orders.php");
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT o.*, u.full_name, u.phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $query .= " AND DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

if ($search) {
    $query .= " AND (u.full_name LIKE ? OR o.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
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
                                <li>Orders</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Status</span>
                                </label>
                                <select name="status" class="select select-bordered w-full max-w-xs" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Date</span>
                                </label>
                                <input type="date" name="date" value="<?php echo $date_filter; ?>" 
                                       class="input input-bordered w-full max-w-xs" onchange="this.form.submit()">
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Search</span>
                                </label>
                                <div class="join">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search orders..." class="input input-bordered join-item">
                                    <button class="btn join-item">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                            <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <div class="badge badge-<?php 
                                                    echo match($order['status']) {
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'error',
                                                        default => 'ghost'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="join">
                                                    <button class="btn btn-sm join-item" onclick="viewOrder(<?php echo $order['id']; ?>)">View</button>
                                                    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <form method="POST" class="join-item">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="status" value="processing">
                                                                <button type="submit" class="btn btn-sm btn-primary">Mark Processing</button>
                                                            </form>
                                                            <form method="POST" class="join-item">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-sm btn-error">Cancel Order</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($order['status'] === 'processing'): ?>
                                                            <form method="POST" class="join-item">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-sm btn-success">Mark Completed</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
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
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <dialog id="order_modal" class="modal">
        <form method="dialog" class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg mb-4">Order Details</h3>
            <div id="order_details">Loading...</div>
            <div class="modal-action">
                <button class="btn">Close</button>
            </div>
        </form>
    </dialog>

    <script>
        function viewOrder(orderId) {
            const modal = document.getElementById('order_modal');
            const detailsContainer = document.getElementById('order_details');
            
            modal.showModal();
            
            // Fetch order details
            fetch(`view_order.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    detailsContainer.innerHTML = html;
                })
                .catch(error => {
                    detailsContainer.innerHTML = 'Error loading order details.';
                });
        }
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
