<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

if (!isset($_GET['id'])) {
    die('Order ID not provided');
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get order details
$order_query = "SELECT o.*, u.full_name, u.phone, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found');
}

// Get order items
$items_query = "SELECT oi.*, f.name, f.price 
                FROM order_items oi 
                JOIN food_items f ON oi.food_item_id = f.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->execute([$_GET['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Customer Information -->
    <div class="card bg-base-200">
        <div class="card-body">
            <h3 class="card-title">Customer Information</h3>
            <div class="space-y-2">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="card bg-base-200">
        <div class="card-body">
            <h3 class="card-title">Order Information</h3>
            <div class="space-y-2">
                <p><strong>Order ID:</strong> #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge badge-<?php 
                        echo match($order['status']) {
                            'pending' => 'warning',
                            'processing' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'error',
                            default => 'ghost'
                        };
                    ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order['contact_number']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Order Items -->
<div class="card bg-base-200 mt-4">
    <div class="card-body">
        <h3 class="card-title">Order Items</h3>
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
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>RM<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right font-bold">Total:</td>
                        <td class="font-bold">RM<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Order Notes -->
<?php if (!empty($order['notes'])): ?>
<div class="card bg-base-200 mt-4">
    <div class="card-body">
        <h3 class="card-title">Order Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
    </div>
</div>
<?php endif; ?>
