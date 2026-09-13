<?php
require 'includes/functions.php';
require 'includes/db.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: sports.php');
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE username = ?');
    $stmt->execute([$username]);
    $customer = $stmt->fetch();

    if ($customer && is_locked($pdo, $customer['id'])) {
        $errors[] = 'This account is temporarily locked due to repeated failed attempts. Please try again later.';
    } elseif ($customer && password_verify($password, $customer['password_hash'])) {
        reset_failed_attempts($pdo, $customer['id']);
        regenerate_session();
        $_SESSION['customer_id']       = $customer['id'];
        $_SESSION['customer_username'] = $customer['username'];
        // Any earlier security-level clearance is invalid for a brand new session.
        unset($_SESSION['security_level_1_passed'], $_SESSION['security_level_2_passed'], $_SESSION['pending_booking']);
        header('Location: sports.php');
        exit;
    } else {
        if ($customer) {
            register_failed_attempt($pdo, $customer['id']);
        }
        $errors[] = 'Incorrect username or password.';
    }
}

$pageTitle = 'Login';
require 'includes/header.php';
?>

<div class="card" style="max-width:420px;margin:0 auto;">
    <h1>Customer Login</h1>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">Account created — you can now log in.</div>
    <?php endif; ?>
    <?php if (isset($_GET['security_failed'])): ?>
        <div class="alert alert-error">Security verification failed too many times. Please log in again.</div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo e($username); ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Log In</button>
    </form>
    <p style="margin-top:16px;">No account yet? <a href="register.php">Register here</a></p>
</div>

<?php require 'includes/footer.php'; ?>
