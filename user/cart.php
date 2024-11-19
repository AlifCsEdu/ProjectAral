<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/special_offers.php';

// Check if user is logged in
checkUserLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get cart items for the current user
$query = "SELECT c.id as cart_id, c.quantity, f.* 
          FROM cart c 
          JOIN food_items f ON c.food_item_id = f.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active offers
$offers = new SpecialOffers($conn);
$active_offers = $offers->getActiveOffers();

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get active offer from session
$activeOffer = null;
if (isset($_SESSION['active_offer'])) {
    $activeOffer = $_SESSION['active_offer'];
}

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        .cart-bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
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
            border-radius: 1rem;
            transition: all 0.3s ease;
        }
        .cart-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 1rem;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        .quantity-btn {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        .quantity-btn:hover {
            transform: scale(1.1);
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: bold;
        }
        .remove-btn {
            color: #FF6B6B;
            transition: all 0.2s ease;
        }
        .remove-btn:hover {
            color: #FF5252;
            transform: scale(1.1);
        }
        .offer-section {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        .offer-input {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-right: 0.5rem;
        }
        .summary-card {
            position: sticky;
            top: 2rem;
        }
        .checkout-btn {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            border: none;
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF6B6B',
                        secondary: '#4ECDC4',
                        accent: '#45B7D1',
                        neutral: '#2D3436',
                        'base-100': '#FFFFFF',
                        'base-200': '#F7F7F7',
                        'base-300': '#E3E3E3',
                    }
                }
            }
        }
    </script>
</head>
<body class="cart-bg">
    <!-- Navigation -->
    <div class="glass-nav sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center gap-6">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                        Your Cart
                    </h1>
                    <a href="menu.php" class="btn btn-ghost btn-sm">Back to Menu</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <?php if (empty($cartItems)): ?>
            <div class="glass-card p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-xl font-bold mb-2">Your Cart is Empty</h2>
                <p class="text-gray-600 mb-6">Looks like you haven't added any items to your cart yet.</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="glass-card mb-8">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $item['price']; ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <img src="../<?php echo !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/default-food.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-16 h-16 object-cover rounded-lg">
                                        <div>
                                            <h3 class="font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <p class="text-sm text-gray-600">RM<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <button class="quantity-btn decrease-quantity" data-id="<?php echo $item['cart_id']; ?>">-</button>
                                            <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" data-id="<?php echo $item['cart_id']; ?>" readonly>
                                            <button class="quantity-btn increase-quantity" data-id="<?php echo $item['cart_id']; ?>">+</button>
                                        </div>
                                        <button class="remove-btn" data-id="<?php echo $item['cart_id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="glass-card p-6 summary-card">
                        <h2 class="text-xl font-bold mb-6">Order Summary</h2>
                        
                        <?php if (!empty($active_offers)): ?>
                        <div class="offer-section mb-6 p-4 rounded-lg bg-gradient-to-r from-primary to-secondary text-white">
                            <h3 class="font-bold mb-2">Available Offers</h3>
                            <?php foreach ($active_offers as $offer): ?>
                                <div class="text-sm mb-2">
                                    <span class="font-semibold"><?php echo htmlspecialchars($offer['code']); ?></span>: 
                                    <?php echo htmlspecialchars($offer['description']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Offer Code Section -->
                        <div class="mb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <input type="text" id="offerCode" class="input input-bordered w-full" placeholder="Enter offer code">
                                <button id="applyOffer" class="btn btn-primary">Apply</button>
                            </div>
                            <div id="offerMessage" class="text-sm"></div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span class="subtotal">RM<?php echo number_format($total, 2); ?></span>
                            </div>
                            
                            <!-- Discount Section -->
                            <div id="discountSection" class="flex justify-between <?php echo isset($_SESSION['active_offer']) ? '' : 'hidden'; ?>">
                                <span>Discount:</span>
                                <span class="text-success" id="discountAmount">
                                    <?php 
                                    if (isset($_SESSION['active_offer'])) {
                                        $discount = ($total * $_SESSION['active_offer']['discount_percentage']) / 100;
                                        echo "-RM" . number_format($discount, 2);
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex justify-between font-bold">
                                    <span>Total:</span>
                                    <span id="finalTotal">
                                        <?php 
                                        if (isset($_SESSION['active_offer'])) {
                                            $discountedTotal = $total - ($total * $_SESSION['active_offer']['discount_percentage'] / 100);
                                            echo "RM" . number_format($discountedTotal, 2);
                                        } else {
                                            echo "RM" . number_format($total, 2);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($cartItems)): ?>
                            <a href="checkout.php" class="btn btn-primary w-full mt-6">Proceed to Checkout</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            let activeDiscount = <?php echo isset($_SESSION['active_offer']) ? $_SESSION['active_offer']['discount_percentage'] : 0; ?>;
            let currentTotal = <?php echo $total; ?>;
            
            // Update quantity
            $('.increase-quantity, .decrease-quantity').click(function() {
                const btn = $(this);
                const cartId = btn.data('id');
                const input = $(`.quantity-input[data-id="${cartId}"]`);
                const currentQty = parseInt(input.val());
                const isIncrease = btn.hasClass('increase-quantity');
                const newQty = isIncrease ? currentQty + 1 : Math.max(1, currentQty - 1);

                if (newQty !== currentQty) {
                    updateCartItem(cartId, newQty);
                }
            });

            // Remove item
            $('.remove-btn').click(function() {
                const cartId = $(this).data('id');
                removeCartItem(cartId);
            });

            // Apply offer code
            $('#applyOffer').click(function() {
                const code = $('#offerCode').val().trim();
                if (!code) {
                    showToast('Please enter an offer code', 'error');
                    return;
                }

                $.ajax({
                    url: 'validate_offer.php',
                    method: 'POST',
                    data: {
                        code: code,
                        total: currentTotal
                    },
                    success: function(response) {
                        if (response.valid) {
                            // Store the offer in session
                            $.ajax({
                                url: 'store_offer.php',
                                method: 'POST',
                                data: {
                                    offer: {
                                        code: code,
                                        discount_percentage: response.discount_percentage
                                    }
                                },
                                success: function() {
                                    activeDiscount = response.discount_percentage;
                                    updateTotalDisplay();
                                    $('#discountSection').removeClass('hidden');
                                    $('#offerMessage').html(`<span class="text-success">${response.message}</span>`);
                                    showToast(response.message, 'success');
                                }
                            });
                        } else {
                            $('#offerMessage').html(`<span class="text-error">${response.message}</span>`);
                            showToast(response.message, 'error');
                        }
                    },
                    error: function() {
                        showToast('Error validating offer code', 'error');
                    }
                });
            });

            function updateCartItem(cartId, quantity) {
                const input = $(`.quantity-input[data-id="${cartId}"]`);
                const originalValue = input.val();
                const buttons = $(`.quantity-btn[data-id="${cartId}"]`);
                
                // Store original value and disable buttons
                input.data('original-value', originalValue);
                buttons.prop('disabled', true);
                
                $.ajax({
                    url: 'update_cart.php',
                    method: 'POST',
                    data: { cart_id: cartId, quantity: quantity },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            input.val(quantity);
                            currentTotal = response.total;
                            updateTotalDisplay();
                            showToast('Cart updated successfully');
                        } else {
                            input.val(input.data('original-value'));
                            showToast(response.message || 'Error updating cart', 'error');
                        }
                    },
                    error: function() {
                        input.val(input.data('original-value'));
                        showToast('Error updating cart', 'error');
                    },
                    complete: function() {
                        // Re-enable buttons after update
                        buttons.prop('disabled', false);
                    }
                });
            }

            function removeCartItem(cartId) {
                if (confirm('Are you sure you want to remove this item?')) {
                    $.ajax({
                        url: 'remove_from_cart.php',
                        method: 'POST',
                        data: { cart_id: cartId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`.cart-item[data-id="${cartId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                    if ($('.cart-item').length === 0) {
                                        location.reload();
                                    } else {
                                        currentTotal = response.total;
                                        updateTotalDisplay();
                                    }
                                });
                                showToast('Item removed from cart');
                            } else {
                                showToast(response.message || 'Error removing item', 'error');
                            }
                        },
                        error: function() {
                            showToast('Error removing item', 'error');
                        }
                    });
                }
            }

            function updateTotalDisplay() {
                const discount = (currentTotal * activeDiscount) / 100;
                const finalTotal = currentTotal - discount;
                
                // Update subtotal
                $('.subtotal').text(`RM${currentTotal.toFixed(2)}`);
                
                // Update discount amount if there's an active discount
                if (activeDiscount > 0) {
                    $('#discountAmount').text(`-RM${discount.toFixed(2)}`);
                    $('#discountSection').removeClass('hidden');
                }
                
                // Update final total
                $('#finalTotal').text(`RM${finalTotal.toFixed(2)}`);
            }

            function showToast(message, type = 'success') {
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "center",
                    backgroundColor: type === 'success' ? "#4CAF50" : "#F44336",
                }).showToast();
            }

            // Store original values for quantity inputs
            $('.quantity-input').each(function() {
                $(this).attr('data-original-value', $(this).val());
            });
        });
    </script>
</body>
</html>
