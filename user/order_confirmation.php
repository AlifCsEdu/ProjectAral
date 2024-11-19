<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['order_id'];

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get order details
$order_query = "SELECT o.*, u.full_name, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Get order items
$items_query = "SELECT oi.*, f.name, f.description 
                FROM order_items oi
                JOIN food_items f ON oi.food_item_id = f.id
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->execute([$order_id]);
$orderItems = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Aral's Food</title>
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
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Confirmation Content -->
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="text-center mb-8">
                            <h1 class="text-3xl font-bold mb-4">Order Confirmed!</h1>
                            <p class="text-xl">Thank you for your order</p>
                            <p class="text-primary font-bold">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></p>
                        </div>

                        <div class="space-y-6">
                            <!-- Order Status -->
                            <div>
                                <h2 class="text-xl font-bold mb-2">Order Status</h2>
                                <div class="badge badge-primary"><?php echo ucfirst($order['status']); ?></div>
                            </div>

                            <!-- Delivery Details -->
                            <div>
                                <h2 class="text-xl font-bold mb-2">Delivery Details</h2>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number']); ?></p>
                            </div>

                            <!-- Payment Details -->
                            <div>
                                <h2 class="text-xl font-bold mb-2">Payment Details</h2>
                                <p><strong>Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                <p><strong>Total Amount:</strong> RM<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>

                            <!-- Order Items -->
                            <div>
                                <h2 class="text-xl font-bold mb-2">Order Items</h2>
                                <div class="space-y-4">
                                    <?php foreach ($orderItems as $item): ?>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                                <p class="text-sm">Quantity: <?php echo $item['quantity']; ?></p>
                                            </div>
                                            <p class="font-bold">RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="text-center space-y-4">
                            <p class="text-sm">A confirmation email has been sent to <?php echo htmlspecialchars($order['email']); ?></p>
                            <div class="space-x-4">
                                <a href="orders.php" class="btn btn-primary">View All Orders</a>
                                <a href="menu.php" class="btn btn-ghost">Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
