<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "All required fields must be filled out";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$username, $email]);

            if ($check_stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Insert new user
                $insert_query = "INSERT INTO users (username, email, password, full_name, address, phone, role) 
                                VALUES (?, ?, ?, ?, ?, ?, 'user')";
                $insert_stmt = $conn->prepare($insert_query);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                if ($insert_stmt->execute([$username, $email, $hashed_password, $full_name, $address, $phone])) {
                    $success = "Registration successful! You can now login.";
                    // Optionally, automatically log in the user
                    $_SESSION['user_id'] = $conn->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    header("Location: menu.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-base-200 flex flex-col">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow-md">
            <div class="flex-1">
                <a href="../index.php" class="btn btn-ghost normal-case text-xl">Aral's Food</a>
            </div>
            <div class="flex-none">
                <ul class="menu menu-horizontal px-1">
                    <li><a href="login.php" class="btn btn-ghost">Login</a></li>
                </ul>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="flex-grow flex items-center justify-center p-4">
            <div class="card w-full max-w-md bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold text-center">Create Account</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Username*</span>
                            </label>
                            <input type="text" name="username" class="input input-bordered" required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Email*</span>
                            </label>
                            <input type="email" name="email" class="input input-bordered" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Full Name*</span>
                            </label>
                            <input type="text" name="full_name" class="input input-bordered" required
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Password*</span>
                            </label>
                            <input type="password" name="password" class="input input-bordered" required minlength="6">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Confirm Password*</span>
                            </label>
                            <input type="password" name="confirm_password" class="input input-bordered" required minlength="6">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Address</span>
                            </label>
                            <textarea name="address" class="textarea textarea-bordered h-24"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Phone Number</span>
                            </label>
                            <input type="tel" name="phone" class="input input-bordered"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>

                        <div class="text-center mt-4">
                            Already have an account? <a href="login.php" class="link link-primary">Login here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
