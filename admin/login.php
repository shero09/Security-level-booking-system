<?php
require '../includes/functions.php';
require '../includes/db.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        regenerate_session();
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    }
    $errors[] = 'Incorrect admin username or password.';
}

$pageTitle = 'Admin Login';
$assetPrefix = '../';
require '../includes/header.php';
?>

<div class="card" style="max-width:400px;margin:0 auto;">
    <h1>Admin Login</h1>
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
    <p style="margin-top:12px;font-size:0.85rem;color:#6b7280;">Default demo credentials: admin / Admin@12345 — change immediately in production.</p>
</div>

<?php require '../includes/footer.php'; ?>
