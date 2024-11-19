<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = $_POST['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if order exists and belongs to user
    $check_query = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if ($order['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
        exit;
    }

    // Update order status to cancelled
    $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    
    if ($update_stmt->execute([$order_id, $_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
