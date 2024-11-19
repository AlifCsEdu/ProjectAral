<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $contact_number = trim($_POST['contact_number']);

    if (empty($subject) || empty($message)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            $query = "INSERT INTO contact_messages (user_id, subject, message, contact_number, status, created_at) 
                      VALUES (:user_id, :subject, :message, :contact_number, 'pending', NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->execute();

            $success = "Your message has been sent successfully!";
        } catch (PDOException $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    }
}

// Fetch user's messages with replies
try {
    $query = "SELECT cm.*, 
              (SELECT COUNT(*) FROM contact_replies WHERE message_id = cm.id) as reply_count 
              FROM contact_messages cm 
              WHERE cm.user_id = :user_id 
              ORDER BY cm.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching messages: " . $e->getMessage();
    $messages = [];
}

// Get replies for a specific message if requested
$selected_message = null;
$replies = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    try {
        // Verify message belongs to user
        $query = "SELECT * FROM contact_messages WHERE id = :id AND user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_GET['view']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $selected_message = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_message) {
            // Get replies
            $query = "SELECT cr.*, u.username, u.role 
                     FROM contact_replies cr 
                     JOIN users u ON cr.user_id = u.id 
                     WHERE cr.message_id = :message_id 
                     ORDER BY cr.created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':message_id', $_GET['view']);
            $stmt->execute();
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Error fetching message details: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Aral's Food</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .user-bg {
            background: linear-gradient(135deg, rgba(255,107,107,0.1), rgba(78,205,196,0.1));
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        .message-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="min-h-screen user-bg">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($error)): ?>
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($selected_message): ?>
            <!-- Message Details View -->
            <div class="max-w-4xl mx-auto">
                <div class="glass-card rounded-2xl p-6 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($selected_message['subject']); ?></h2>
                            <p class="text-sm text-gray-500">
                                Sent: <?php echo date('F j, Y g:i A', strtotime($selected_message['created_at'])); ?>
                            </p>
                            <p class="text-sm">
                                Status: 
                                <span class="badge <?php 
                                    echo $selected_message['status'] === 'pending' ? 'badge-warning' : 
                                        ($selected_message['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                                ?>">
                                    <?php echo ucfirst($selected_message['status']); ?>
                                </span>
                            </p>
                        </div>
                        <a href="contact.php" class="btn btn-sm">Back to List</a>
                    </div>
                    <div class="bg-base-200 rounded-lg p-4 mb-4 message-content">
                        <?php echo nl2br(htmlspecialchars($selected_message['message'])); ?>
                    </div>
                    <?php if ($selected_message['contact_number']): ?>
                        <p class="text-sm text-gray-600 mb-4">
                            Contact Number: <?php echo htmlspecialchars($selected_message['contact_number']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Replies Section -->
                <?php if (!empty($replies)): ?>
                    <div class="space-y-4 mb-6">
                        <h3 class="text-xl font-bold mb-4">Responses</h3>
                        <?php foreach ($replies as $reply): ?>
                            <div class="glass-card rounded-2xl p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-bold">
                                            <?php echo htmlspecialchars($reply['username']); ?>
                                        </span>
                                        <span class="badge badge-sm ml-2">
                                            <?php echo ucfirst($reply['role']); ?>
                                        </span>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('F j, Y g:i A', strtotime($reply['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($reply['reply'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Contact Form -->
                    <div class="glass-card rounded-2xl p-6">
                        <h1 class="text-3xl font-bold mb-6">Contact Us</h1>
                        <form method="POST" class="space-y-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Subject *</span>
                                </label>
                                <input type="text" name="subject" class="input input-bordered" required>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Message *</span>
                                </label>
                                <textarea name="message" class="textarea textarea-bordered h-32" required></textarea>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Contact Number (Optional)</span>
                                </label>
                                <input type="tel" name="contact_number" class="input input-bordered" 
                                       pattern="[0-9]+" title="Please enter only numbers">
                            </div>

                            <div class="form-control mt-6">
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>

                    <!-- Previous Messages -->
                    <div class="glass-card rounded-2xl p-6">
                        <h2 class="text-2xl font-bold mb-6">Your Messages</h2>
                        <?php if (empty($messages)): ?>
                            <p class="text-gray-500">You haven't sent any messages yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($messages as $message): ?>
                                    <div class="card bg-base-100 shadow-xl">
                                        <div class="card-body">
                                            <div class="flex justify-between items-start">
                                                <h3 class="card-title"><?php echo htmlspecialchars($message['subject']); ?></h3>
                                                <span class="badge <?php 
                                                    echo $message['status'] === 'pending' ? 'badge-warning' : 
                                                        ($message['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                                                ?>">
                                                    <?php echo ucfirst($message['status']); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('F j, Y g:i A', strtotime($message['created_at'])); ?>
                                            </p>
                                            <p class="text-sm">
                                                <?php echo $message['reply_count']; ?> 
                                                <?php echo $message['reply_count'] === 1 ? 'reply' : 'replies'; ?>
                                            </p>
                                            <div class="card-actions justify-end">
                                                <a href="?view=<?php echo $message['id']; ?>" 
                                                   class="btn btn-sm btn-primary">View</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
