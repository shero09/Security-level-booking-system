<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();

if (empty($_SESSION['pending_booking']) || empty($_SESSION['security_level_1_passed'])) {
    header('Location: security_level1.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch();

if (is_locked($pdo, $customer['id'])) {
    session_destroy();
    header('Location: login.php?security_failed=1');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $answer = mb_strtolower(trim($_POST['answer'] ?? ''));

    if (password_verify($answer, $customer['security_answer_2_hash'])) {
        reset_failed_attempts($pdo, $customer['id']);
        $_SESSION['security_level_2_passed'] = true;
        header('Location: payment.php');
        exit;
    }

    register_failed_attempt($pdo, $customer['id']);
    $left = remaining_attempts($pdo, $customer['id']);

    if ($left <= 0) {
        session_destroy();
        header('Location: login.php?security_failed=1');
        exit;
    }
    $error = "Incorrect answer. $left attempt(s) remaining.";
}

$pageTitle = 'Security Level 2';
require 'includes/header.php';
?>

<div class="card" style="max-width:480px;margin:0 auto;">
    <div class="security-steps">
        <div class="step done">1. Security Level 1</div>
        <div class="step active">2. Security Level 2</div>
        <div class="step">3. Payment</div>
    </div>

    <h1>Security Verification — Level 2</h1>
    <p>One more check before you can pay.</p>

    <?php if ($error): ?><div class="alert alert-error attempts-warning"><?php echo e($error); ?></div><?php endif; ?>

    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label><?php echo e($customer['security_question_2']); ?></label>
            <input type="text" name="answer" required autofocus>
        </div>
        <button type="submit" class="btn">Verify & Continue</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
