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

$cart_id = $_POST['cart_id'] ?? null;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Missing cart item ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Delete cart item (only if it belongs to the current user)
    $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if ($delete_stmt->execute([$cart_id, $_SESSION['user_id']])) {
        if ($delete_stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
