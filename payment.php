<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();
require_security_cleared();

if (empty($_SESSION['pending_booking'])) {
    header('Location: sports.php');
    exit;
}

$pending = $_SESSION['pending_booking'];
$stmt = $pdo->prepare('SELECT * FROM sports WHERE id = ?');
$stmt->execute([$pending['sport_id']]);
$sport = $stmt->fetch();

if (!$sport) {
    header('Location: sports.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $cardholder = trim($_POST['cardholder_name'] ?? '');
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry     = trim($_POST['expiry'] ?? '');
    $cvv        = trim($_POST['cvv'] ?? '');

    if ($cardholder === '') {
        $errors[] = 'Cardholder name is required.';
    }
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
        $errors[] = 'Please enter a valid card number.';
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $errors[] = 'Expiry must be in MM/YY format.';
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = 'Please enter a valid CVV.';
    }

    if (!$errors) {
        // --- Simulated payment gateway -------------------------------
        // Only the last 4 digits and a generated reference are ever
        // stored. The full card number and CVV are used in-memory for
        // this request only and are discarded immediately afterwards —
        // exactly as a real integration with Stripe/PayPal would work,
        // where the card details never reach or persist on this server.
        $last4 = substr($cardNumber, -4);
        $transactionRef = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
        unset($cardNumber, $cvv);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (customer_id, sport_id, booking_date, booking_time, venue_number, postcode, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $_SESSION['customer_id'],
                $sport['id'],
                $pending['booking_date'],
                $pending['booking_time'],
                $pending['venue_number'],
                $pending['postcode'],
                'confirmed',
            ]);
            $bookingId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO payments (booking_id, customer_id, amount, cardholder_name, card_last4, transaction_ref, payment_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $bookingId,
                $_SESSION['customer_id'],
                $sport['price'],
                $cardholder,
                $last4,
                $transactionRef,
                'success',
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Payment/booking commit failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong processing your booking. Please try again.';
        }

        if (!$errors) {
            unset($_SESSION['pending_booking'], $_SESSION['security_level_1_passed'], $_SESSION['security_level_2_passed']);
            header('Location: confirmation.php?booking_id=' . $bookingId);
            exit;
        }
    }
}

$pageTitle = 'Payment';
require 'includes/header.php';
?>

<div class="card" style="max-width:480px;margin:0 auto;">
    <div class="security-steps">
        <div class="step done">1. Security Level 1</div>
        <div class="step done">2. Security Level 2</div>
        <div class="step active">3. Payment</div>
    </div>

    <h1>Payment</h1>
    <p><?php echo e($sport['sport_name']); ?> — <?php echo e($pending['booking_date']); ?> at <?php echo e($pending['booking_time']); ?></p>
    <p class="sport-price">Total: £<?php echo number_format((float)$sport['price'], 2); ?></p>

    <div class="alert alert-info">Demo payment form — this is a simulated gateway for the dissertation project. No real transaction is processed.</div>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Cardholder Name</label>
            <input type="text" name="cardholder_name" required>
        </div>
        <div class="form-group">
            <label>Card Number</label>
            <input type="text" name="card_number" placeholder="4242 4242 4242 4242" required>
        </div>
        <div class="form-group" style="display:flex; gap:12px;">
            <div style="flex:1;">
                <label>Expiry (MM/YY)</label>
                <input type="text" name="expiry" placeholder="12/28" required>
            </div>
            <div style="flex:1;">
                <label>CVV</label>
                <input type="text" name="cvv" placeholder="123" required>
            </div>
        </div>
        <button type="submit" class="btn">Pay & Confirm Booking</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
