<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/special_offers.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
checkUserLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get cart items
$query = "SELECT c.id as cart_id, c.quantity, f.* 
          FROM cart c 
          JOIN food_items f ON c.food_item_id = f.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Get active discount from session
$discount_percentage = 0;
$discount_amount = 0;
if (isset($_SESSION['active_offer'])) {
    $active_offer = $_SESSION['active_offer'];
    $discount_percentage = floatval($active_offer['discount_percentage']);
    $discount_amount = $subtotal * ($discount_percentage / 100);
}

// Calculate final total
$total = $subtotal - $discount_amount;

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['delivery_address', 'contact_number', 'payment_method', 'full_name', 'email'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(", ", $missing_fields));
        }

        // Start transaction
        $conn->beginTransaction();

        // Insert into orders table
        $order_sql = "INSERT INTO orders (user_id, total_amount, status, payment_method, 
                                        delivery_address, contact_number) 
                     VALUES (?, ?, 'Pending', ?, ?, ?)";
        
        $order_stmt = $conn->prepare($order_sql);
        $success = $order_stmt->execute([
            $_SESSION['user_id'],
            $total,
            $_POST['payment_method'],
            $_POST['delivery_address'],
            $_POST['contact_number']
        ]);

        if (!$success) {
            throw new Exception("Failed to create order: " . implode(", ", $order_stmt->errorInfo()));
        }
        
        $order_id = $conn->lastInsertId();

        // Insert order items
        $items_sql = "INSERT INTO order_items (order_id, food_item_id, quantity, price) 
                     VALUES (?, ?, ?, ?)";
        $items_stmt = $conn->prepare($items_sql);
        
        foreach ($cartItems as $item) {
            $success = $items_stmt->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
            
            if (!$success) {
                throw new Exception("Failed to add item to order: " . implode(", ", $items_stmt->errorInfo()));
            }
        }

        // Clear user's cart
        $clear_cart = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_cart);
        $success = $clear_stmt->execute([$_SESSION['user_id']]);
        
        if (!$success) {
            throw new Exception("Failed to clear cart: " . implode(", ", $clear_stmt->errorInfo()));
        }

        // Clear any active offer
        unset($_SESSION['active_offer']);

        // Commit transaction
        $conn->commit();

        // Set success message in session
        $_SESSION['order_success'] = true;
        $_SESSION['order_id'] = $order_id;

        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        // Rollback on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
        // Log the error
        error_log("Order Error: " . $e->getMessage());
    }
}

// Check if cart is empty
if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Display any error messages at the top of the form
if (isset($error_message)): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .checkout-bg {
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
        .order-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .input-group {
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }
        .input-group input, .input-group select, .input-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus {
            outline: none;
            border-color: #4ECDC4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
        }
        .place-order-btn {
            background: linear-gradient(135deg, #4ECDC4, #556270);
            color: white;
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: bold;
            border: none;
            transition: all 0.3s ease;
        }
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .summary-card {
            position: sticky;
            top: 2rem;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .payment-method {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method.active {
            border-color: #4ECDC4;
            background: rgba(78, 205, 196, 0.1);
        }
        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .input-error {
            border-color: #F44336;
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
        }
    </style>
</head>
<body class="checkout-bg">
    <!-- Navigation -->
    <div class="glass-nav sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center gap-6">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                        Checkout
                    </h1>
                    <a href="cart.php" class="btn btn-ghost btn-sm">Back to Cart</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <form id="checkoutForm" method="POST" action="checkout.php">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2">
                    <div class="glass-card p-6 mb-8">
                        <h2 class="text-xl font-bold mb-6">Delivery Information</h2>
                        <div class="space-y-6">
                            <!-- Full Name -->
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="full_name" name="full_name" 
                                       class="input input-bordered w-full" required
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                       placeholder="Enter your full name">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" name="email" 
                                       class="input input-bordered w-full" required
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       placeholder="Enter your email address">
                            </div>

                            <!-- Delivery Address -->
                            <div>
                                <label for="delivery_address" class="block text-sm font-medium text-gray-700">Delivery Address</label>
                                <textarea id="delivery_address" name="delivery_address" rows="3" 
                                        class="textarea textarea-bordered w-full" required
                                        placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <!-- Contact Number -->
                            <div>
                                <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" 
                                       class="input input-bordered w-full" required
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       placeholder="Enter your contact number">
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer payment-method">
                                        <input type="radio" name="payment_method" value="Cash on Delivery" class="radio" checked>
                                        <span>Cash on Delivery</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer payment-method">
                                        <input type="radio" name="payment_method" value="GCash" class="radio">
                                        <span>GCash</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="glass-card p-6 summary-card">
                        <h2 class="text-xl font-bold mb-6">Order Summary</h2>
                        
                        <!-- Order Items -->
                        <div class="mb-6">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="order-item">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start gap-3">
                                            <img src="../<?php echo !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/default-food.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-12 h-12 object-cover rounded">
                                            <div>
                                                <h4 class="font-medium"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?></p>
                                            </div>
                                        </div>
                                        <span class="font-medium">RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-bold">RM<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <?php if ($discount_amount > 0): ?>
                                <div class="flex justify-between text-primary">
                                    <span>Discount (<?php echo $discount_percentage; ?>%)</span>
                                    <span>-RM<?php echo number_format($discount_amount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span>RM<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <button type="submit" class="place-order-btn">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Payment method selection
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', () => {
                // Remove active class from all parent labels
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
                // Add active class to selected method's parent label
                method.closest('.payment-method').classList.add('active');
            });
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const requiredFields = ['full_name', 'email', 'delivery_address', 'contact_number'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input || !input.value.trim()) {
                    isValid = false;
                    input?.classList.add('input-error');
                } else {
                    input?.classList.remove('input-error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }

            // Form is valid, let it submit
            return true;
        });
    </script>
</body>
</html>
