<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get admin user data
$query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [];
        $params = [];

        // Basic information update
        if (isset($_POST['full_name']) && $_POST['full_name'] !== $user['full_name']) {
            $updates[] = "full_name = ?";
            $params[] = $_POST['full_name'];
        }
        if (isset($_POST['email']) && $_POST['email'] !== $user['email']) {
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
        if (isset($_POST['phone']) && $_POST['phone'] !== $user['phone']) {
            $updates[] = "phone = ?";
            $params[] = $_POST['phone'];
        }

        // Password update
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception("Current password is required to set a new password");
            }
            
            // Verify current password
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
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
            <!-- Page content -->
            <div class="p-4">
                <div class="navbar bg-base-100 shadow-lg rounded-box mb-4">
                    <div class="flex-1">
                        <label for="my-drawer-2" class="btn btn-ghost drawer-button lg:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </label>
                        <h1 class="text-2xl font-bold px-4">Admin Profile</h1>
                    </div>
                </div>

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

                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
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
        </div>
        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label> 
            <div class="menu p-4 w-80 min-h-full bg-base-200 text-base-content">
                <div class="flex items-center gap-4 mb-8">
                    <div class="avatar">
                        <div class="w-12 rounded-full">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" />
                        </div>
                    </div>
                    <div>
                        <p class="font-bold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="text-sm opacity-70"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                <!-- Sidebar content -->
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="menu.php">Menu Items</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
