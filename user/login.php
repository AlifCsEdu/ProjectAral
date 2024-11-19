<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            $query = "SELECT id, username, password, role FROM users WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: menu.php");
                    }
                    exit;
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
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
    <title>Login - Aral's Food</title>
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
                    <li><a href="register.php" class="btn btn-ghost">Register</a></li>
                </ul>
            </div>
        </div>

        <!-- Login Form -->
        <div class="flex-grow flex items-center justify-center p-4">
            <div class="card w-full max-w-sm bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold text-center">Login</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Username</span>
                            </label>
                            <input type="text" name="username" class="input input-bordered" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Password</span>
                            </label>
                            <input type="password" name="password" class="input input-bordered" required>
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>

                        <div class="text-center mt-4">
                            Don't have an account? <a href="register.php" class="link link-primary">Register here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
