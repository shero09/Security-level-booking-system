<?php
require 'includes/functions.php';
require 'includes/db.php';

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'username' => '', 'email' => '', 'postcode' => '', 'q1' => '', 'q2' => ''];

$securityQuestions = [
    "What was the name of your first pet?",
    "What is your mother's maiden name?",
    "What was the name of your primary school?",
    "What city were you born in?",
    "What is your favourite sports team?",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['first_name'] = trim($_POST['first_name'] ?? '');
    $old['last_name']  = trim($_POST['last_name'] ?? '');
    $old['username']   = trim($_POST['username'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['postcode']   = trim($_POST['postcode'] ?? '');
    $old['q1']         = $_POST['security_question_1'] ?? '';
    $old['q2']         = $_POST['security_question_2'] ?? '';
    $password          = $_POST['password'] ?? '';
    $confirmPassword   = $_POST['confirm_password'] ?? '';
    $answer1           = trim($_POST['security_answer_1'] ?? '');
    $answer2           = trim($_POST['security_answer_2'] ?? '');

    // --- Validation ---------------------------------------------------
    if ($old['first_name'] === '' || $old['last_name'] === '') {
        $errors[] = 'First and last name are required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($old['username']) < 4) {
        $errors[] = 'Username must be at least 4 characters.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters and include an uppercase letter and a number.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (!preg_match('/^[A-Za-z0-9 ]{5,10}$/', $old['postcode'])) {
        $errors[] = 'Please enter a valid postcode.';
    }
    if ($old['q1'] === '' || $old['q2'] === '' || $old['q1'] === $old['q2']) {
        $errors[] = 'Please choose two different security questions.';
    }
    if ($answer1 === '' || $answer2 === '') {
        $errors[] = 'Both security answers are required.';
    }

    // Uniqueness check
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE username = ? OR email = ?');
        $stmt->execute([$old['username'], $old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'That username or email is already registered.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO customers
                (first_name, last_name, username, email, password_hash,
                 security_question_1, security_answer_1_hash,
                 security_question_2, security_answer_2_hash, postcode)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['first_name'],
            $old['last_name'],
            $old['username'],
            $old['email'],
            password_hash($password, PASSWORD_BCRYPT),
            $old['q1'],
            password_hash(mb_strtolower($answer1), PASSWORD_BCRYPT),
            $old['q2'],
            password_hash(mb_strtolower($answer2), PASSWORD_BCRYPT),
            $old['postcode'],
        ]);

        header('Location: login.php?registered=1');
        exit;
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>

<div class="card" style="max-width:560px;margin:0 auto;">
    <h1>Create Your Account</h1>
    <p>Registering sets up your login <strong>and</strong> the two security questions you'll be asked before every payment.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo e($old['first_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo e($old['last_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo e($old['email']); ?>" required>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo e($old['username']); ?>" required>
        </div>
        <div class="form-group">
            <label>Postcode</label>
            <input type="text" name="postcode" value="<?php echo e($old['postcode']); ?>" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <hr>
        <h3>Security Level 1</h3>
        <div class="form-group">
            <label>Security Question 1</label>
            <select name="security_question_1" required>
                <option value="">-- Choose a question --</option>
                <?php foreach ($securityQuestions as $q): ?>
                    <option value="<?php echo e($q); ?>" <?php echo $old['q1'] === $q ? 'selected' : ''; ?>><?php echo e($q); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Your Answer</label>
            <input type="text" name="security_answer_1" required>
        </div>

        <h3>Security Level 2</h3>
        <div class="form-group">
            <label>Security Question 2 (must differ from Question 1)</label>
            <select name="security_question_2" required>
                <option value="">-- Choose a question --</option>
                <?php foreach ($securityQuestions as $q): ?>
                    <option value="<?php echo e($q); ?>" <?php echo $old['q2'] === $q ? 'selected' : ''; ?>><?php echo e($q); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Your Answer</label>
            <input type="text" name="security_answer_2" required>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>
    <p style="margin-top:16px;">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require 'includes/footer.php'; ?>
