<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/special_offers.php';

$database = new Database();
$conn = $database->getConnection();
$offers = new SpecialOffers($conn);

// Get cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cart_count = $result['count'];
}

// Get active offers
$active_offers = $offers->getActiveOffers();

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get food items
$food_query = "SELECT f.*, c.name as category_name FROM food_items f 
               JOIN categories c ON f.category_id = c.id 
               ORDER BY f.name";
$food_stmt = $conn->prepare($food_query);
$food_stmt->execute();
$food_items = $food_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        .menu-bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .offer-banner {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .offer-banner:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        .search-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .category-btn {
            transition: all 0.2s ease;
        }
        .category-btn.active {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            border: none;
        }
        .food-badge {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .btn-add-cart {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-add-cart:active {
            transform: scale(0.95);
        }
        .btn-add-cart::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        .btn-add-cart.animate::after {
            animation: ripple 1s ease-out;
        }
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }
        .cart-count {
            transition: all 0.3s ease;
        }
        .cart-count.bump {
            animation: cartBump 0.3s ease-out;
        }
        @keyframes cartBump {
            0% { transform: scale(1); }
            50% { transform: scale(1.4); }
            100% { transform: scale(1); }
        }
        #cartPreview {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .cart-preview-item {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        .cart-preview-item:last-child {
            border-bottom: none;
        }
        .cart-preview-item:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="min-h-screen menu-bg">
    <!-- Navigation -->
    <div class="glass-nav sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center gap-6">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                        Our Menu
                    </h1>
                    <a href="../index.php" class="btn btn-ghost btn-sm">Home</a>
                </div>
                <div class="hidden lg:flex items-center gap-4">
                    <a href="menu.php" class="btn btn-ghost btn-sm">Menu</a>
                    <div class="relative">
                        <a href="cart.php" class="btn btn-ghost btn-sm" id="cartButton">
                            Cart
                            <?php if ($cart_count > 0): ?>
                            <span class="badge badge-sm badge-primary"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <!-- Cart Preview -->
                        <div id="cartPreview" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg z-50 transform transition-all duration-300 ease-in-out">
                            <div class="p-4 border-b">
                                <h3 class="text-lg font-bold">Cart Preview</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto" id="cartPreviewItems">
                                <!-- Cart items will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <a href="orders.php" class="btn btn-ghost btn-sm">Orders</a>
                    <a href="profile.php" class="btn btn-ghost btn-sm">Profile</a>
                    <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuButton" class="lg:hidden btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Mobile Menu -->
                <div id="mobileMenu" class="hidden lg:hidden fixed inset-0 z-50">
                    <div class="absolute inset-0 bg-black opacity-50"></div>
                    <div class="absolute right-0 top-0 h-full w-64 bg-base-100 p-4">
                        <button id="closeMenu" class="btn btn-ghost btn-sm mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="flex flex-col gap-2">
                            <a href="../index.php" class="btn btn-ghost btn-sm">Home</a>
                            <a href="menu.php" class="btn btn-ghost btn-sm">Menu</a>
                            <a href="cart.php" class="btn btn-ghost btn-sm">
                                Cart
                                <?php if ($cart_count > 0): ?>
                                <span class="badge badge-sm"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="orders.php" class="btn btn-ghost btn-sm">Orders</a>
                            <a href="profile.php" class="btn btn-ghost btn-sm">Profile</a>
                            <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Search and Filters -->
        <div class="search-container mb-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Search Bar -->
                <div class="w-full md:w-1/3">
                    <div class="relative">
                        <input type="text" id="searchInput" 
                               placeholder="Search menu items..." 
                               class="input input-bordered w-full pl-10" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-3 text-gray-400" 
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Category Filters -->
                <div class="flex flex-wrap gap-2">
                    <button class="category-btn btn btn-sm active" data-category="all">
                        All Items
                    </button>
                    <?php foreach ($categories as $category): ?>
                        <button class="category-btn btn btn-sm" 
                                data-category="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Active Offers -->
        <?php if (!empty($active_offers)): ?>
        <div class="mb-8">
            <h3 class="text-xl font-bold mb-4 bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                Special Offers
            </h3>
            <div class="flex gap-4 overflow-x-auto pb-4 custom-scrollbar">
                <?php foreach ($active_offers as $offer): ?>
                    <div class="offer-banner flex-none min-w-[300px]">
                        <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($offer['title']); ?></h3>
                        <p class="mb-3 opacity-90"><?php echo htmlspecialchars($offer['description']); ?></p>
                        <div class="bg-white/20 px-4 py-2 rounded-lg inline-block">
                            <span class="text-sm opacity-90">Use code:</span>
                            <span class="font-mono font-bold ml-2"><?php echo htmlspecialchars($offer['code']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu Items Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($food_items as $item): ?>
                <div class="food-item glass-card rounded-xl overflow-hidden" 
                     data-category="<?php echo htmlspecialchars($item['category_id']); ?>"
                     data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>"
                     data-description="<?php echo htmlspecialchars(strtolower($item['description'])); ?>"
                     data-category-name="<?php echo htmlspecialchars(strtolower($item['category_name'])); ?>">
                    <div class="relative">
                        <img src="../<?php echo !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/default-food.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="w-full h-48 object-cover">
                        <div class="food-badge">
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <h2 class="text-lg font-bold mb-2 food-name">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </h2>
                        <p class="text-gray-600 text-sm mb-4 food-description">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        <div class="flex items-center justify-between">
                            <div class="original-price" data-price="<?php echo $item['price']; ?>">
                                <span class="text-xl font-bold text-primary">
                                    RM<?php echo number_format($item['price'], 2); ?>
                                </span>
                            </div>
                            <button class="btn-add-cart btn btn-sm" 
                                    data-id="<?php echo $item['id']; ?>">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
            <div class="glass-card max-w-md mx-auto p-8 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3 class="text-xl font-bold mb-2">No Items Found</h3>
                <p class="text-gray-600">Try adjusting your search or filter criteria</p>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize variables
        let currentCategory = 'all';
        let currentSearch = '<?php echo addslashes($_GET['search'] ?? ''); ?>';
        let cartPreviewVisible = false;

        // Function to update cart count
        function updateCartCount() {
            $.ajax({
                url: 'get_cart_count.php',
                method: 'GET',
                dataType: 'json',
                cache: false,
                success: function(data) {
                    if (data.success) {
                        $('.cart-count').text(data.count);
                        $('.badge-sm').text(data.count);
                    }
                }
            });
        }

        // Update cart count periodically (every 5 seconds)
        setInterval(updateCartCount, 5000);

        // Initial cart count update
        updateCartCount();

        // Function to load cart preview
        function loadCartPreview() {
            $.ajax({
                url: 'get_cart_preview.php',
                method: 'GET',
                dataType: 'json',
                cache: false,
                success: function(data) {
                    if (data.success) {
                        // Update cart preview content
                        let html = '';
                        if (data.items && data.items.length > 0) {
                            data.items.forEach(item => {
                                html += `
                                    <div class="cart-preview-item">
                                        <div class="cart-item" data-id="${item.cart_id}">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <img src="../${item.image_path || 'assets/images/default-food.jpg'}" 
                                                         alt="${item.name}"
                                                         class="w-12 h-12 object-cover rounded-lg">
                                                    <div>
                                                        <h4 class="font-semibold">${item.name}</h4>
                                                        <p class="text-sm text-gray-600">
                                                            ${item.quantity} × RM${item.price.toFixed(2)}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button class="btn btn-ghost btn-xs quantity-btn" onclick="updateCart(${item.cart_id}, 'decrease')">-</button>
                                                    <span class="quantity-display">${item.quantity}</span>
                                                    <button class="btn btn-ghost btn-xs quantity-btn" onclick="updateCart(${item.cart_id}, 'increase')">+</button>
                                                    <button class="btn btn-ghost btn-xs text-error remove-btn" onclick="removeFromCart(${item.cart_id})">×</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            // Cart footer with total and view cart button
                            html += `
                                <div class="p-4 border-t">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="font-bold">Total:</span>
                                        <span class="font-bold text-primary">RM${data.total.toFixed(2)}</span>
                                    </div>
                                    <a href="cart.php" class="btn btn-primary w-full">View Cart</a>
                                </div>
                            `;
                        } else {
                            html = '<div class="p-4 text-center">Your cart is empty</div>';
                        }
                        $('#cartPreviewItems').html(html);
                    }
                }
            });
        }

        // Function to update cart quantity
        function updateCart(cartId, action) {
            const qtySpan = $(`.cart-item[data-id="${cartId}"] .quantity-display`);
            const currentQty = parseInt(qtySpan.text());
            const newQty = action === 'increase' ? currentQty + 1 : Math.max(1, currentQty - 1);

            const buttons = $(`.cart-item[data-id="${cartId}"] button`);
            buttons.prop('disabled', true);

            $.ajax({
                url: 'update_cart.php',
                method: 'POST',
                data: {
                    cart_id: cartId,
                    quantity: newQty
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadCartPreview();
                        updateCartCount();
                        showToast('Cart updated successfully');
                    } else {
                        showToast(response.message || 'Error updating cart', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Error updating cart', 'error');
                    console.error('Cart update error:', error);
                },
                complete: function() {
                    buttons.prop('disabled', false);
                }
            });
        }

        // Function to show toast messages
        function showToast(message, type = 'success') {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "center",
                backgroundColor: type === 'success' ? "#4CAF50" : "#F44336",
            }).showToast();
        }

        // Function to remove from cart
        function removeFromCart(cartId) {
            $.ajax({
                url: 'remove_cart.php',
                method: 'POST',
                data: {
                    cart_id: cartId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadCartPreview();
                        updateCartCount();
                    } else {
                        console.error('Remove from cart failed:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Remove from cart error:', error);
                }
            });
        }

        // Make functions globally accessible
        window.updateCart = updateCart;
        window.removeFromCart = removeFromCart;

        // Function to filter items
        function filterItems() {
            let hasVisibleItems = false;
            
            $('.food-item').each(function() {
                const $item = $(this);
                const matchesCategory = currentCategory === 'all' || $item.data('category') === currentCategory;
                const matchesSearch = currentSearch === '' || 
                    $item.data('name').includes(currentSearch.toLowerCase()) ||
                    $item.data('description').includes(currentSearch.toLowerCase()) ||
                    $item.data('category-name').includes(currentSearch.toLowerCase());

                if (matchesCategory && matchesSearch) {
                    $item.removeClass('hidden');
                    hasVisibleItems = true;
                } else {
                    $item.addClass('hidden');
                }
            });

            $('#noResults').toggleClass('hidden', hasVisibleItems);
        }

        // Category filter
        $('.category-btn').click(function() {
            $('.category-btn').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).data('category');
            filterItems();
        });

        // Search functionality with debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            searchTimeout = setTimeout(() => {
                currentSearch = searchTerm;
                filterItems();
            }, 300);
        });

        // Initial filter if search term exists
        if (currentSearch) {
            filterItems();
        }

        // Add to cart functionality
        $(document).on('click', '.btn-add-cart', function(e) {
            const btn = $(this);
            const foodId = btn.data('id');
            
            // Add ripple effect
            btn.removeClass('animate');
            btn.addClass('animate');
            setTimeout(() => btn.removeClass('animate'), 1000);
            
            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: { 
                    food_item_id: foodId,
                    quantity: 1
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Animate the button
                            btn.addClass('added');
                            setTimeout(() => btn.removeClass('added'), 1000);
                            
                            // Show toast
                            Toastify({
                                text: "Item added to cart!",
                                duration: 3000,
                                gravity: "top",
                                position: "center",
                                backgroundColor: "linear-gradient(to right, #4ECDC4, #45B7D1)",
                                className: "info",
                            }).showToast();
                            
                            // Update cart count and preview
                            updateCartCount();
                            loadCartPreview();
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error adding to cart:', error);
                }
            });
        });

        // Cart preview toggle
        $('#cartButton').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            cartPreviewVisible = !cartPreviewVisible;
            
            if (cartPreviewVisible) {
                loadCartPreview();
                $('#cartPreview').removeClass('hidden')
                    .css('opacity', '0')
                    .css('transform', 'translateY(-10px)')
                    .animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 300);
            } else {
                $('#cartPreview').animate({
                    opacity: 0,
                    transform: 'translateY(-10px)'
                }, 300, function() {
                    $(this).addClass('hidden');
                });
            }
        });

        // Close cart preview when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#cartPreview').length && !$(e.target).closest('#cartButton').length) {
                if (cartPreviewVisible) {
                    cartPreviewVisible = false;
                    $('#cartPreview').animate({
                        opacity: 0,
                        transform: 'translateY(-10px)'
                    }, 300, function() {
                        $(this).addClass('hidden');
                    });
                }
            }
        });

        // Handle offer code submission
        $('#offerForm').on('submit', function(e) {
            e.preventDefault();
            const code = $('#offerCode').val();
            
            $.ajax({
                url: 'validate_offer.php',
                method: 'POST',
                data: { code: code },
                success: function(response) {
                    const result = JSON.parse(response);
                    $('#offerMessage').html(result.message)
                        .removeClass('text-error text-success')
                        .addClass(result.valid ? 'text-success' : 'text-error');
                    
                    if (result.valid) {
                        // Store offer in session
                        sessionStorage.setItem('activeOffer', JSON.stringify(result));
                        updatePricesWithDiscount(result.discount_percentage);
                    }
                },
                error: function() {
                    $('#offerMessage').html('Error validating offer code')
                        .removeClass('text-success')
                        .addClass('text-error');
                }
            });
        });

        // Function to update prices with discount
        function updatePricesWithDiscount(discountPercentage) {
            $('.food-item').each(function() {
                const priceElement = $(this).find('.original-price');
                const originalPrice = parseFloat(priceElement.data('price'));
                const discountedPrice = originalPrice * (1 - discountPercentage / 100);
                
                if (discountPercentage > 0) {
                    priceElement.html(`
                        <span class="text-sm line-through text-gray-500">RM${originalPrice.toFixed(2)}</span>
                        <span class="text-xl font-bold text-primary">RM${discountedPrice.toFixed(2)}</span>
                    `);
                }
            });
        }

        // Check for existing offer in session
        const savedOffer = sessionStorage.getItem('activeOffer');
        if (savedOffer) {
            const offer = JSON.parse(savedOffer);
            $('#offerMessage').html(`Offer applied: ${offer.message}`)
                .removeClass('text-error')
                .addClass('text-success');
            updatePricesWithDiscount(offer.discount_percentage);
        }
    });
    </script>
</body>
</html>
