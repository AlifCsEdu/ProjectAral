<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['offer'])) {
    $_SESSION['active_offer'] = $_POST['offer'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No offer data provided']);
}
