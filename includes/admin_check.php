<?php
function checkAdminLogin() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in and is an admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
        
        // Redirect to main login page
        header("Location: ../login.php");
        exit();
    }
}
?>
