<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Check if user is admin
checkAdminLogin();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['status'])) {
    try {
        $query = "UPDATE contact_messages SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_POST['message_id']);
        $stmt->execute();
        $success = "Message status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle replies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['reply'])) {
    try {
        $query = "INSERT INTO contact_replies (message_id, user_id, reply, created_at) 
                  VALUES (:message_id, :user_id, :reply, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':message_id', $_POST['message_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':reply', $_POST['reply']);
        $stmt->execute();

        // Update message status to in-progress if it's pending
        $query = "UPDATE contact_messages SET status = CASE 
                    WHEN status = 'pending' THEN 'in-progress'
                    ELSE status 
                 END
                 WHERE id = :message_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':message_id', $_POST['message_id']);
        $stmt->execute();

        $success = "Reply sent successfully!";
    } catch (PDOException $e) {
        $error = "Error sending reply: " . $e->getMessage();
    }
}

// Fetch messages with user details and reply counts
$query = "SELECT cm.*, u.username, u.full_name, 
          (SELECT COUNT(*) FROM contact_replies WHERE message_id = cm.id) as reply_count
          FROM contact_messages cm 
          JOIN users u ON cm.user_id = u.id 
          ORDER BY 
            CASE cm.status 
                WHEN 'pending' THEN 1 
                WHEN 'in-progress' THEN 2 
                WHEN 'resolved' THEN 3 
            END,
            cm.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get replies for a specific message if requested
$selected_message = null;
$replies = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    try {
        // Get message details
        $query = "SELECT cm.*, u.username, u.full_name 
                 FROM contact_messages cm 
                 JOIN users u ON cm.user_id = u.id 
                 WHERE cm.id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_GET['view']);
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
    <title>Contact Messages - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .message-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
            <!-- Page content -->
            <div class="p-4">
                <!-- Mobile Nav -->
                <div class="navbar bg-base-100 shadow-lg rounded-box mb-4 lg:hidden">
                    <div class="flex-1">
                        <label for="my-drawer-2" class="btn btn-ghost drawer-button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </label>
                        <h1 class="text-xl font-bold px-4">Contact Messages</h1>
                    </div>
                </div>

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
                        <div class="card bg-base-100 shadow-xl mb-6">
                            <div class="card-body">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h2 class="card-title"><?php echo htmlspecialchars($selected_message['subject']); ?></h2>
                                        <p class="text-sm text-gray-500">
                                            From: <?php echo htmlspecialchars($selected_message['full_name']); ?> 
                                            (<?php echo htmlspecialchars($selected_message['username']); ?>)
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Sent: <?php echo date('F j, Y g:i A', strtotime($selected_message['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="contact_messages.php" class="btn btn-sm">Back to List</a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?php echo $selected_message['id']; ?>">
                                            <select name="status" class="select select-sm select-bordered" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $selected_message['status'] === 'pending' ? 'selected' : ''; ?>>
                                                    Pending
                                                </option>
                                                <option value="in-progress" <?php echo $selected_message['status'] === 'in-progress' ? 'selected' : ''; ?>>
                                                    In Progress
                                                </option>
                                                <option value="resolved" <?php echo $selected_message['status'] === 'resolved' ? 'selected' : ''; ?>>
                                                    Resolved
                                                </option>
                                            </select>
                                        </form>
                                    </div>
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
                        </div>

                        <!-- Replies Section -->
                        <div class="card bg-base-100 shadow-xl mb-6">
                            <div class="card-body">
                                <h3 class="card-title">Conversation History</h3>
                                <div class="space-y-4 mb-6">
                                    <?php foreach ($replies as $reply): ?>
                                        <div class="chat <?php echo $reply['role'] === 'admin' ? 'chat-end' : 'chat-start'; ?>">
                                            <div class="chat-header">
                                                <?php echo htmlspecialchars($reply['username']); ?>
                                                <time class="text-xs opacity-50">
                                                    <?php echo date('F j, Y g:i A', strtotime($reply['created_at'])); ?>
                                                </time>
                                            </div>
                                            <div class="chat-bubble <?php echo $reply['role'] === 'admin' ? 'chat-bubble-primary' : ''; ?>">
                                                <?php echo nl2br(htmlspecialchars($reply['reply'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Reply Form -->
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="message_id" value="<?php echo $selected_message['id']; ?>">
                                    <div class="form-control">
                                        <textarea name="reply" class="textarea textarea-bordered h-24" 
                                                placeholder="Type your reply here..." required></textarea>
                                    </div>
                                    <div class="form-control mt-4">
                                        <button type="submit" class="btn btn-primary">Send Reply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Messages List -->
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>From</th>
                                    <th>Status</th>
                                    <th>Replies</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td>
                                            <div class="font-bold"><?php echo htmlspecialchars($message['subject']); ?></div>
                                            <div class="text-sm opacity-50 truncate max-w-xs">
                                                <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?php echo htmlspecialchars($message['full_name']); ?></div>
                                            <div class="text-sm opacity-50"><?php echo htmlspecialchars($message['username']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $message['status'] === 'pending' ? 'badge-warning' : 
                                                    ($message['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                                            ?>">
                                                <?php echo ucfirst($message['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="badge badge-ghost">
                                                <?php echo $message['reply_count']; ?> replies
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="?view=<?php echo $message['id']; ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
    </div>
</body>
</html>
