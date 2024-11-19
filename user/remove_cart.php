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
    echo json_encode(['success' => false, 'message' => 'Missing cart_id parameter']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify cart item belongs to user and delete it
    $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt->execute([$cart_id, $_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
    }

} catch (Exception $e) {
    error_log("Remove from cart error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error removing item from cart'
    ]);
}
