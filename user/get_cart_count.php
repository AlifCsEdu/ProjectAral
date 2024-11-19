<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get total items in cart
    $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $count = $result['count'] ?? 0;
    echo json_encode(['success' => true, 'count' => (int)$count]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
