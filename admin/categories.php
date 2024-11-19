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
            case 'add':
                $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_POST['name'], $_POST['description']]);
                break;

            case 'edit':
                $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['id']]);
                break;

            case 'delete':
                // First check if category has any food items
                $check_query = "SELECT COUNT(*) FROM food_items WHERE category_id = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete category. Please remove all food items from this category first.";
                } else {
                    $query = "DELETE FROM categories WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$_POST['id']]);
                }
                break;
        }
        
        header("Location: categories.php");
        exit;
    }
}

// Get categories with item counts
$query = "SELECT c.*, COUNT(f.id) as item_count 
          FROM categories c 
          LEFT JOIN food_items f ON c.id = f.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Aral's Food</title>
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
                                <li>Categories</li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex-none gap-2">
                        <button class="btn btn-primary" onclick="document.getElementById('add_modal').showModal()">Add Category</button>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td class="font-bold"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                                            <td>
                                                <div class="badge badge-ghost">
                                                    <?php echo $category['item_count']; ?> items
                                                </div>
                                            </td>
                                            <td>
                                                <div class="join">
                                                    <button class="btn btn-sm join-item"
                                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-error join-item"
                                                            onclick="confirmDelete(<?php echo $category['id']; ?>)"
                                                            <?php echo $category['item_count'] > 0 ? 'disabled' : ''; ?>>
                                                        Delete
                                                    </button>
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

    <!-- Add Category Modal -->
    <dialog id="add_modal" class="modal">
        <form method="POST" class="modal-box">
            <h3 class="font-bold text-lg mb-4">Add Category</h3>
            <input type="hidden" name="action" value="add">
            
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Name</span>
                </label>
                <input type="text" name="name" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Description</span>
                </label>
                <textarea name="description" class="textarea textarea-bordered" required></textarea>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Add Category</button>
                <button type="button" class="btn" onclick="add_modal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Edit Category Modal -->
    <dialog id="edit_modal" class="modal">
        <form method="POST" class="modal-box">
            <h3 class="font-bold text-lg mb-4">Edit Category</h3>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Name</span>
                </label>
                <input type="text" name="name" id="edit_name" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Description</span>
                </label>
                <textarea name="description" id="edit_description" class="textarea textarea-bordered" required></textarea>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn" onclick="edit_modal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete_modal" class="modal">
        <form method="POST" class="modal-box" id="delete_form">
            <h3 class="font-bold text-lg mb-4">Delete Category</h3>
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
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description;
            
            document.getElementById('edit_modal').showModal();
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_form').submit();
            }
        }

        document.addEventListener('keydown', function(e) {
            // Press N to add new category
            if (e.key === 'n' && !e.ctrlKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.getElementById('add_modal').showModal();
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
    </script>
</body>
</html>
