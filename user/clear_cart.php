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

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Clear all items from cart for current user
    $clear_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_query);
    
    if ($clear_stmt->execute([$_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
