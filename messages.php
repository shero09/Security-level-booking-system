<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        $errors[] = 'Message cannot be empty.';
    } elseif (strlen($message) > 1000) {
        $errors[] = 'Message is too long.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO messages (customer_id, sender_type, message) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['customer_id'], 'customer', $message]);
        header('Location: messages.php');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM messages WHERE customer_id = ? ORDER BY created_at ASC');
$stmt->execute([$_SESSION['customer_id']]);
$thread = $stmt->fetchAll();

// Mark admin messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE customer_id = ? AND sender_type = 'admin'")
    ->execute([$_SESSION['customer_id']]);

$pageTitle = 'Messages';
require 'includes/header.php';
?>

<div class="card" style="max-width:600px;margin:0 auto;">
    <h1>Messages with Admin</h1>
    <p>Send a message about your booking — an admin will reply here.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <div class="message-thread">
        <?php if (!$thread): ?>
            <p style="color:#6b7280;">No messages yet.</p>
        <?php endif; ?>
        <?php foreach ($thread as $msg): ?>
            <div class="msg <?php echo $msg['sender_type'] === 'customer' ? 'msg-customer' : 'msg-admin'; ?>">
                <?php echo nl2br(e($msg['message'])); ?>
                <div class="msg-meta"><?php echo $msg['sender_type'] === 'customer' ? 'You' : 'Admin'; ?> · <?php echo e($msg['created_at']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <textarea name="message" placeholder="Type your message..." required></textarea>
        </div>
        <button type="submit" class="btn">Send</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
