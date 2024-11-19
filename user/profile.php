<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
checkUserLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [];
        $params = [];

        // Basic information update
        if (isset($_POST['full_name']) && $_POST['full_name'] !== $_SESSION['user_name']) {
            $updates[] = "full_name = ?";
            $params[] = $_POST['full_name'];
        }
        if (isset($_POST['email']) && $_POST['email'] !== $_SESSION['user_email']) {
            // Check if email is already taken
            $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$_POST['email'], $_SESSION['user_id']]);
            if ($check_stmt->fetch()) {
                throw new Exception("Email is already taken");
            }
            $updates[] = "email = ?";
            $params[] = $_POST['email'];
        }
        if (isset($_POST['phone']) && $_POST['phone'] !== $_SESSION['user_phone']) {
            $updates[] = "phone = ?";
            $params[] = $_POST['phone'];
        }
        if (isset($_POST['address']) && $_POST['address'] !== $_SESSION['user_address']) {
            $updates[] = "address = ?";
            $params[] = $_POST['address'];
        }

        // Password update
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception("Current password is required to set a new password");
            }
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Validate new password
            if (strlen($_POST['new_password']) < 8) {
                throw new Exception("New password must be at least 8 characters long");
            }
            
            $updates[] = "password = ?";
            $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        // Update profile if there are changes
        if (!empty($updates)) {
            $params[] = $_SESSION['user_id']; // Add user_id for WHERE clause
            $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            $_SESSION['success'] = "Profile updated successfully";
            header("Location: profile.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get user data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$stmt = $conn->prepare("
    SELECT o.*, f.name as menu_name, f.price 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN food_items f ON oi.food_item_id = f.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/user_navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success mb-4">
                <span><?php echo $_SESSION['success']; ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error mb-4">
                <span><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Profile Information</h2>
                        <form method="POST" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Full Name</span>
                                    </label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                           class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Email</span>
                                    </label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Phone</span>
                                    </label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                           class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Delivery Address</span>
                                    </label>
                                    <textarea name="address" class="textarea textarea-bordered" required><?php 
                                        echo htmlspecialchars($user['address']); 
                                    ?></textarea>
                                </div>
                            </div>

                            <div class="divider">Change Password</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Current Password</span>
                                    </label>
                                    <input type="password" name="current_password" class="input input-bordered">
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">New Password</span>
                                    </label>
                                    <input type="password" name="new_password" class="input input-bordered" 
                                           minlength="8" placeholder="Minimum 8 characters">
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Order History</h2>
                        <div class="space-y-4">
                            <?php foreach ($orders as $order): ?>
                                <div class="card bg-base-200">
                                    <div class="card-body p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-bold">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></h3>
                                                <p class="text-sm opacity-70">
                                                    <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                                </p>
                                                <p class="mt-1">
                                                    <?php echo $order['menu_name']; ?> (RM<?php echo number_format($order['price'], 2); ?>)
                                                </p>
                                            </div>
                                            <div class="badge badge-<?php 
                                                echo match($order['status']) {
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'error',
                                                    default => 'ghost'
                                                };
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-block mt-4" 
                                                onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <dialog id="order_modal" class="modal">
        <form method="dialog" class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg mb-4">Order Details</h3>
            <div id="order_details">Loading...</div>
            <div class="modal-action">
                <button class="btn">Close</button>
            </div>
        </form>
    </dialog>

    <script>
        function viewOrder(orderId) {
            const modal = document.getElementById('order_modal');
            const detailsContainer = document.getElementById('order_details');
            
            modal.showModal();
            
            // Fetch order details
            fetch(`view_order.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    detailsContainer.innerHTML = html;
                })
                .catch(error => {
                    detailsContainer.innerHTML = 'Error loading order details.';
                });
        }
    </script>
</body>
</html>
