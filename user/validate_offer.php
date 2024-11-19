<?php
session_start();
require_once '../config/database.php';
require_once '../includes/special_offers.php';

header('Content-Type: application/json');

if (!isset($_POST['code'])) {
    echo json_encode([
        'valid' => false,
        'message' => 'No offer code provided'
    ]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$offers = new SpecialOffers($conn);

// Get the current cart total
$cart_total = 0;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT SUM(c.quantity * f.price) as total 
              FROM cart c 
              JOIN food_items f ON c.food_item_id = f.id 
              WHERE c.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cart_total = $result['total'] ?? 0;
}

// Validate the offer
$result = $offers->validateOffer(
    $_POST['code'],
    $_SESSION['user_id'] ?? null,
    $cart_total
);

echo json_encode($result);
?>
