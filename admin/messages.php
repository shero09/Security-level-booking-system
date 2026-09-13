<?php
require '../includes/functions.php';
require '../includes/db.php';
require_admin();

$customers = $pdo->query('SELECT id, first_name, last_name, username FROM customers ORDER BY first_name')->fetchAll();

$selectedId = (int) ($_GET['customer_id'] ?? ($customers[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $customerId = (int) $_POST['customer_id'];
    $message = trim($_POST['message'] ?? '');
    if ($message !== '') {
        $stmt = $pdo->prepare('INSERT INTO messages (customer_id, sender_type, message) VALUES (?, ?, ?)');
        $stmt->execute([$customerId, 'admin', $message]);
    }
    header('Location: messages.php?customer_id=' . $customerId);
    exit;
}

$thread = [];
if ($selectedId) {
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE customer_id = ? ORDER BY created_at ASC');
    $stmt->execute([$selectedId]);
    $thread = $stmt->fetchAll();
}

$pageTitle = 'Admin Messages';
$assetPrefix = '../';
require '../includes/header.php';
?>

<h1>Customer Messages</h1>

<div class="card" style="display:flex; gap:24px; flex-wrap:wrap;">
    <div style="min-width:200px;">
        <h3>Customers</h3>
        <ul style="list-style:none; padding:0;">
            <?php foreach ($customers as $c): ?>
                <li style="margin-bottom:6px;">
                    <a href="messages.php?customer_id=<?php echo (int)$c['id']; ?>" style="<?php echo $c['id'] == $selectedId ? 'font-weight:700;' : ''; ?>">
                        <?php echo e($c['first_name'] . ' ' . $c['last_name']); ?> (<?php echo e($c['username']); ?>)
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if (!$customers): ?><li>No customers yet.</li><?php endif; ?>
        </ul>
    </div>

    <div style="flex:1; min-width:280px;">
        <?php if ($selectedId): ?>
            <div class="message-thread">
                <?php foreach ($thread as $msg): ?>
                    <div class="msg <?php echo $msg['sender_type'] === 'customer' ? 'msg-customer' : 'msg-admin'; ?>">
                        <?php echo nl2br(e($msg['message'])); ?>
                        <div class="msg-meta"><?php echo $msg['sender_type'] === 'customer' ? 'Customer' : 'You'; ?> · <?php echo e($msg['created_at']); ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$thread): ?><p style="color:#6b7280;">No messages with this customer yet.</p><?php endif; ?>
            </div>
            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="customer_id" value="<?php echo (int)$selectedId; ?>">
                <div class="form-group">
                    <textarea name="message" placeholder="Reply to customer..." required></textarea>
                </div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
