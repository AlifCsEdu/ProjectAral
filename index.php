<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get featured categories
$query = "SELECT * FROM categories ORDER BY RAND() LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$featured_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured menu items
$query = "SELECT f.*, c.name as category_name 
          FROM food_items f 
          JOIN categories c ON f.category_id = c.id 
          ORDER BY RAND() 
          LIMIT 8";
$stmt = $conn->prepare($query);
$stmt->execute();
$featured_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cart_count = $result['count'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aral's Food - Delicious Food Delivered</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add custom styles -->
    <style>
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        .food-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .search-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="drawer">
        <input id="my-drawer-3" type="checkbox" class="drawer-toggle" /> 
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="w-full navbar bg-base-100 fixed top-0 z-50 shadow-lg">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer-3" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>
                </div> 
                <div class="flex-1 px-2 mx-2">
                    <a href="index.php" class="btn btn-ghost normal-case text-xl">
                        <span class="text-primary">Aral's</span> Food
                    </a>
                </div>
                <div class="flex-none hidden lg:block">
                    <ul class="menu menu-horizontal gap-2">
                        <!-- Menu -->
                        <li><a href="user/menu.php" class="btn btn-ghost btn-sm">Menu</a></li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php" class="btn btn-primary btn-sm">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li><a href="user/login.php" class="btn btn-ghost btn-sm">Login</a></li>
                            <li><a href="user/register.php" class="btn btn-primary btn-sm">Register</a></li>
                        <?php else: ?>
                            <li>
                                <a href="user/cart.php" class="btn btn-ghost btn-sm">
                                    Cart
                                    <?php if ($cart_count > 0): ?>
                                        <span class="badge badge-sm badge-primary"><?php echo $cart_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><a href="user/orders.php" class="btn btn-ghost btn-sm">Orders</a></li>
                            <li><a href="user/profile.php" class="btn btn-ghost btn-sm">Profile</a></li>
                            <li><a href="user/logout.php" class="btn btn-ghost btn-sm">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="min-h-screen pt-16">
                <!-- Hero Section with Search -->
                <div class="hero min-h-[70vh] hero-bg">
                    <div class="hero-overlay bg-opacity-60"></div>
                    <div class="hero-content text-center text-neutral-content">
                        <div class="max-w-md">
                            <h1 class="mb-5 text-5xl font-bold">Delicious Food Delivered</h1>
                            <p class="mb-8">Experience the finest selection of dishes delivered right to your doorstep.</p>
                            <!-- Search Bar -->
                            <div class="search-container rounded-lg p-2 mb-8 max-w-md mx-auto">
                                <form action="user/menu.php" method="GET" class="flex gap-2">
                                    <input type="text" name="search" placeholder="Search for dishes..." 
                                           class="input input-bordered flex-grow" />
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="flex gap-4 justify-center">
                                <a href="user/menu.php" class="btn btn-primary">View Menu</a>
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <a href="user/register.php" class="btn btn-ghost">Join Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories Section -->
                <section class="py-16 bg-base-200">
                    <div class="container mx-auto px-4">
                        <div class="text-center mb-12">
                            <h2 class="text-4xl font-bold mb-4">Browse Categories</h2>
                            <p class="text-base-content/60">Explore our wide range of delicious options</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($featured_categories as $category): ?>
                            <div class="card bg-base-100 shadow-xl image-full">
                                <figure>
                                    <img src="assets/images/categories/default.jpg" alt="<?php echo htmlspecialchars($category['name']); ?>" />
                                </figure>
                                <div class="card-body justify-end">
                                    <h3 class="card-title text-2xl"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <div class="card-actions">
                                        <a href="user/menu.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">View Items</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Featured Menu Items -->
                <section class="py-16 bg-base-100">
                    <div class="container mx-auto px-4">
                        <div class="text-center mb-12">
                            <h2 class="text-4xl font-bold mb-4">Featured Menu</h2>
                            <p class="text-base-content/60">Our most popular and delicious dishes</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <?php foreach ($featured_items as $item): ?>
                            <div class="card bg-base-100 shadow-xl food-card">
                                <figure class="px-4 pt-4">
                                    <img src="<?php echo !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/default-food.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="rounded-xl h-48 w-full object-cover">
                                </figure>
                                <div class="card-body">
                                    <h3 class="card-title">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <div class="badge badge-secondary"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                    </h3>
                                    <p class="text-sm text-base-content/70"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="flex justify-between items-center mt-4">
                                        <span class="text-xl font-bold">RM<?php echo number_format($item['price'], 2); ?></span>
                                        <a href="user/menu.php?action=add_to_cart&item_id=<?php echo $item['id']; ?>" 
                                           class="btn btn-primary btn-sm">Add to Cart</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </main>

            <!-- Footer -->
            <footer class="footer footer-center p-10 bg-base-200 text-base-content rounded">
                <div class="grid grid-flow-col gap-4">
                    <a href="about.php" class="link link-hover">About us</a>
                    <a href="contact.php" class="link link-hover">Contact</a>
                    <a href="terms.php" class="link link-hover">Terms of use</a>
                    <a href="privacy.php" class="link link-hover">Privacy policy</a>
                </div>
                <div>
                    <div class="grid grid-flow-col gap-4">
                        <a><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"></path></svg></a>
                        <a><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"></path></svg></a>
                        <a><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="fill-current"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path></svg></a>
                    </div>
                </div>
                <div>
                    <p>Copyright  2023 - All rights reserved by Aral's Food Ordering System</p>
                </div>
            </footer>
        </div> 

        <!-- Mobile Menu Drawer -->
        <div class="drawer-side">
            <label for="my-drawer-3" class="drawer-overlay"></label> 
            <ul class="menu p-4 w-80 min-h-full bg-base-200">
                <li><a href="user/menu.php">Menu</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin/dashboard.php" class="btn btn-primary">Admin Dashboard</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="user/cart.php">
                        Cart
                        <?php if ($cart_count > 0): ?>
                        <span class="badge badge-sm"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="user/orders.php">Orders</a></li>
                <li><a href="user/profile.php">Profile</a></li>
                <li><a href="user/logout.php">Logout</a></li>
                <?php else: ?>
                <li><a href="user/login.php">Login</a></li>
                <li><a href="user/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>
