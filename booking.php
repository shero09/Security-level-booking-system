<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();

$sportId = (int) ($_GET['sport_id'] ?? $_POST['sport_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM sports WHERE id = ?');
$stmt->execute([$sportId]);
$sport = $stmt->fetch();

if (!$sport) {
    header('Location: sports.php');
    exit;
}

$errors = [];
$old = ['booking_date' => '', 'booking_time' => '', 'postcode' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['booking_date'] = $_POST['booking_date'] ?? '';
    $old['booking_time'] = $_POST['booking_time'] ?? '';
    $old['postcode']     = trim($_POST['postcode'] ?? '');

    $today = date('Y-m-d');
    if (!$old['booking_date'] || $old['booking_date'] < $today) {
        $errors[] = 'Please choose a valid future date.';
    }
    if (!$old['booking_time']) {
        $errors[] = 'Please choose a time.';
    }
    if (!preg_match('/^[A-Za-z0-9 ]{5,10}$/', $old['postcode'])) {
        $errors[] = 'Please enter a valid postcode.';
    }

    if (!$errors) {
        // Booking details are staged in the session — nothing is written
        // to the bookings table until both security levels are passed.
        $_SESSION['pending_booking'] = [
            'sport_id'     => $sport['id'],
            'booking_date' => $old['booking_date'],
            'booking_time' => $old['booking_time'],
            'venue_number' => $sport['venue_number'],
            'postcode'     => $old['postcode'],
        ];
        unset($_SESSION['security_level_1_passed'], $_SESSION['security_level_2_passed']);
        header('Location: security_level1.php');
        exit;
    }
}

$pageTitle = 'Book ' . $sport['sport_name'];
require 'includes/header.php';
?>

<div class="card" style="max-width:520px;margin:0 auto;">
    <h1>Book: <?php echo e($sport['sport_name']); ?></h1>
    <p class="sport-meta"><?php echo e($sport['venue_name']); ?> · Venue #<?php echo e($sport['venue_number']); ?> · £<?php echo number_format((float)$sport['price'], 2); ?></p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="sport_id" value="<?php echo (int)$sport['id']; ?>">
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($old['booking_date']); ?>" required>
        </div>
        <div class="form-group">
            <label>Time</label>
            <input type="time" name="booking_time" value="<?php echo e($old['booking_time']); ?>" required>
        </div>
        <div class="form-group">
            <label>Your Postcode</label>
            <input type="text" name="postcode" value="<?php echo e($old['postcode']); ?>" required>
        </div>
        <button type="submit" class="btn">Continue to Security Verification</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
