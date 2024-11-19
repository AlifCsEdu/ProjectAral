<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get all orders for the user
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get order items
function getOrderItems($conn, $order_id) {
    $query = "SELECT oi.*, f.name 
              FROM order_items oi
              JOIN food_items f ON oi.food_item_id = f.id
              WHERE oi.order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get status badge color
function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending':
            return 'badge-warning';
        case 'processing':
            return 'badge-info';
        case 'delivered':
            return 'badge-success';
        case 'cancelled':
            return 'badge-error';
        default:
            return 'badge-ghost';
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-base-200">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow-md">
            <div class="flex-1">
                <a href="../index.php" class="btn btn-ghost normal-case text-xl">Aral's Food</a>
            </div>
            <div class="flex-none">
                <ul class="menu menu-horizontal px-1">
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="orders.php" class="active">Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Orders Content -->
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-8">My Orders</h1>

            <?php if (empty($orders)): ?>
                <div class="text-center py-8">
                    <h2 class="text-xl mb-4">You haven't placed any orders yet</h2>
                    <a href="menu.php" class="btn btn-primary">Browse Menu</a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($orders as $order): ?>
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h2 class="card-title">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></h2>
                                        <p class="text-sm">
                                            <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="badge <?php echo getStatusBadgeColor($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </div>
                                </div>

                                <div class="divider"></div>

                                <!-- Order Items -->
                                <div class="space-y-4">
                                    <?php 
                                    $orderItems = getOrderItems($conn, $order['id']);
                                    foreach ($orderItems as $item): 
                                    ?>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                                <p class="text-sm">Quantity: <?php echo $item['quantity']; ?></p>
                                            </div>
                                            <p class="font-bold">RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="divider"></div>

                                <!-- Order Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h3 class="font-bold mb-2">Delivery Details</h3>
                                        <p class="text-sm"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                        <p class="text-sm">Contact: <?php echo htmlspecialchars($order['contact_number']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="font-bold mb-2">Payment Details</h3>
                                        <p class="text-sm">Method: <?php echo ucfirst($order['payment_method']); ?></p>
                                        <p class="text-xl font-bold">Total: RM<?php echo number_format($order['total_amount'], 2); ?></p>
                                    </div>
                                </div>

                                <?php if ($order['status'] === 'pending'): ?>
                                    <div class="card-actions justify-end mt-4">
                                        <button class="btn btn-error btn-sm" 
                                                onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            Cancel Order
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling order');
                });
            }
        }
    </script>
</body>
</html>
