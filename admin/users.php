<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit':
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['role'],
                    $_POST['id']
                ]);

                // If password is provided, update it separately
                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$password_hash, $_POST['id']]);
                }
                break;

            case 'delete':
                // Check if user has any orders
                $check_query = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete user. This user has existing orders.";
                } else {
                    $query = "DELETE FROM users WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$_POST['id']]);
                }
                break;
        }
        
        header("Location: users.php");
        exit;
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT u.*, 
                 COUNT(o.id) as order_count,
                 SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent
          FROM users u 
          LEFT JOIN orders o ON u.id = o.user_id 
          WHERE 1=1";
$params = [];

if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
}

if ($search) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Aral's Food</title>
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
                        <div class="text-sm breadcrumbs hidden sm:inline-block">
                            <ul>
                                <li><a href="dashboard.php">Dashboard</a></li>
                                <li>Users</li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex-none gap-2">
                        <button class="btn btn-primary" onclick="add_modal.showModal()">Add User</button>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Role</span>
                                </label>
                                <select name="role" class="select select-bordered w-full max-w-xs" onchange="this.form.submit()">
                                    <option value="">All Roles</option>
                                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Search</span>
                                </label>
                                <div class="join">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search users..." class="input input-bordered join-item">
                                    <button class="btn join-item">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Role</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="font-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div class="text-sm opacity-50"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <div class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'ghost'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="badge badge-ghost"><?php echo $user['order_count']; ?> orders</div>
                                            </td>
                                            <td>RM<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="join">
                                                    <button class="btn btn-sm join-item"
                                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        Edit
                                                    </button>
                                                    <?php if ($user['role'] !== 'admin'): ?>
                                                        <button class="btn btn-sm btn-error join-item"
                                                                onclick="confirmDelete(<?php echo $user['id']; ?>)"
                                                                <?php echo $user['order_count'] > 0 ? 'disabled' : ''; ?>>
                                                            Delete
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Edit User Modal -->
    <dialog id="edit_modal" class="modal">
        <form method="POST" class="modal-box">
            <h3 class="font-bold text-lg mb-4">Edit User</h3>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Full Name</span>
                </label>
                <input type="text" name="full_name" id="edit_full_name" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Email</span>
                </label>
                <input type="email" name="email" id="edit_email" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Phone</span>
                </label>
                <input type="tel" name="phone" id="edit_phone" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Role</span>
                </label>
                <select name="role" id="edit_role" class="select select-bordered" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">New Password (leave blank to keep current)</span>
                </label>
                <input type="password" name="password" class="input input-bordered">
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn" onclick="edit_modal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete_modal" class="modal">
        <form method="POST" class="modal-box">
            <h3 class="font-bold text-lg mb-4">Delete User</h3>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            
            <p>Are you sure you want to delete <span id="delete_name" class="font-bold"></span>?</p>
            <p class="text-error">This action cannot be undone.</p>

            <div class="modal-action">
                <button type="submit" class="btn btn-error">Delete</button>
                <button type="button" class="btn" onclick="delete_modal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <script>
        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_role').value = user.role;
            
            document.getElementById('edit_modal').showModal();
        }

        document.addEventListener('keydown', function(e) {
            // Press N to add new user
            if (e.key === 'n' && !e.ctrlKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                add_modal.showModal();
            }
            // Press / to focus search
            if (e.key === '/' && !e.ctrlKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.querySelector('input[type="search"]').focus();
            }
        });

        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                }
            });
        });

        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_form').submit();
            }
        }
    </script>
</body>
</html>
