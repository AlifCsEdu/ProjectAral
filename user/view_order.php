<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

if (!isset($_GET['id'])) {
    echo "Order ID not provided";
    exit;
}

$orderId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, f.name as menu_name, f.price, oi.quantity 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN food_items f ON oi.food_item_id = f.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orderDetails)) {
    echo "Order not found or unauthorized";
    exit;
}

// Calculate total
$total = 0;
foreach ($orderDetails as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get the order status and date from the first item
$orderStatus = $orderDetails[0]['status'];
$orderDate = $orderDetails[0]['created_at'];
?>

<div class="p-4">
    <h3 class="text-lg font-bold mb-4">Order #<?php echo $orderId; ?></h3>
    <div class="mb-4">
        <p>Date: <?php echo date('F j, Y g:i A', strtotime($orderDate)); ?></p>
        <p>Status: <span class="badge badge-<?php 
            echo match($orderStatus) {
                'pending' => 'warning',
                'processing' => 'info',
                'completed' => 'success',
                'cancelled' => 'error',
                default => 'ghost'
            };
        ?>"><?php echo ucfirst($orderStatus); ?></span></p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderDetails as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['menu_name']); ?></td>
                    <td>RM<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold">Total:</td>
                    <td class="font-bold">RM<?php echo number_format($total, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
