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
        try {
            switch ($_POST['action']) {
                case 'add':
                    // Handle image upload
                    $imagePath = '';
                    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../assets/images/';
                        $fileExtension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $newFileName;

                        // Validate file type
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($fileExtension, $allowedTypes)) {
                            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
                        }

                        // Move uploaded file
                        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                            $imagePath = 'assets/images/' . $newFileName;
                        } else {
                            throw new Exception('Failed to upload image.');
                        }
                    }

                    $query = "INSERT INTO food_items (name, description, price, category_id, image_path) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category_id'],
                        $imagePath
                    ]);
                    break;

                case 'edit':
                    $updateFields = ['name', 'description', 'price', 'category_id'];
                    $params = [
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category_id']
                    ];

                    // Handle image upload if new file is provided
                    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../assets/images/';
                        $fileExtension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $newFileName;

                        // Validate file type
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($fileExtension, $allowedTypes)) {
                            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
                        }

                        // Delete old image if it exists
                        $query = "SELECT image_path FROM food_items WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$_POST['id']]);
                        $oldImage = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($oldImage && $oldImage['image_path']) {
                            $oldImagePath = '../' . $oldImage['image_path'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }

                        // Move new image
                        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                            $updateFields[] = 'image_path';
                            $params[] = 'assets/images/' . $newFileName;
                        } else {
                            throw new Exception('Failed to upload image.');
                        }
                    }

                    // Add ID to params for WHERE clause
                    $params[] = $_POST['id'];

                    // Build the query
                    $query = "UPDATE food_items SET " . 
                            implode(" = ?, ", $updateFields) . " = ? " .
                            "WHERE id = ?";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute($params);
                    break;

                case 'delete':
                    // Get the image path before deleting
                    $query = "SELECT image_path FROM food_items WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$_POST['id']]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Delete the image file if it exists
                    if ($item && $item['image_path']) {
                        $fullPath = '../' . $item['image_path'];
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }

                    // Delete the database record
                    $query = "DELETE FROM food_items WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$_POST['id']]);
                    break;
            }
            
            $_SESSION['success'] = 'Operation completed successfully';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header("Location: menu.php");
        exit;
    }
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$stmt = $conn->prepare($categories_query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameters
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
$search = $_GET['search'] ?? '';

// Build query for menu items
$query = "SELECT f.*, c.name as category_name 
          FROM food_items f 
          INNER JOIN categories c ON f.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($category_filter !== null) {
    $query .= " AND f.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $query .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY c.name ASC, f.name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Aral's Food</title>
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
                                <li>Menu Items</li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex-none gap-2">
                        <button class="btn btn-primary" onclick="document.getElementById('add_modal').showModal()">Add Menu Item</button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Category</span>
                                </label>
                                <select name="category" class="select select-bordered w-full max-w-xs" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Search</span>
                                </label>
                                <div class="join">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                           placeholder="Search menu items..." class="input input-bordered join-item">
                                    <button class="btn join-item">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Menu Items Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="card bg-base-100 shadow-xl">
                            <figure class="px-4 pt-4">
                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="rounded-xl h-48 w-full object-cover" />
                            </figure>
                            <div class="card-body">
                                <h2 class="card-title">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <div class="badge badge-secondary"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                </h2>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <p class="text-xl font-bold">RM<?php echo number_format($item['price'], 2); ?></p>
                                <div class="card-actions justify-end">
                                    <button class="btn btn-sm" 
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-error" 
                                            onclick="confirmDelete(<?php echo $item['id']; ?>)">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Add keyboard shortcuts -->
    <script>
        document.addEventListener('keydown', function(e) {
            // Press N to add new item
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

        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_form').submit();
            }
        }
    </script>

    <!-- Add Item Modal -->
    <dialog id="add_modal" class="modal">
        <form method="POST" class="modal-box" enctype="multipart/form-data">
            <h3 class="font-bold text-lg mb-4">Add Menu Item</h3>
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

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Price</span>
                </label>
                <input type="number" name="price" step="0.01" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Category</span>
                </label>
                <select name="category_id" class="select select-bordered" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Image Upload</span>
                </label>
                <input type="file" name="image_file" accept="image/*" class="file-input file-input-bordered w-full">
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Add Item</button>
                <button type="button" class="btn" onclick="add_modal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Edit Item Modal -->
    <dialog id="edit_modal" class="modal">
        <form method="POST" class="modal-box" enctype="multipart/form-data">
            <h3 class="font-bold text-lg mb-4">Edit Menu Item</h3>
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

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Price</span>
                </label>
                <input type="number" name="price" id="edit_price" step="0.01" class="input input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Category</span>
                </label>
                <select name="category_id" id="edit_category_id" class="select select-bordered" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Image Upload</span>
                </label>
                <input type="file" name="image_file" accept="image/*" class="file-input file-input-bordered w-full">
                <div class="text-sm text-gray-500 mt-2">
                    Leave empty to keep the existing image
                </div>
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
            <h3 class="font-bold text-lg mb-4">Delete Menu Item</h3>
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
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_description').value = item.description;
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_category_id').value = item.category_id;
            
            document.getElementById('edit_modal').showModal();
        }
    </script>
</body>
</html>
