<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT b.*, s.sport_name, s.venue_name, s.price, p.transaction_ref, p.card_last4
     FROM bookings b
     JOIN sports s ON s.id = b.sport_id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.id = ? AND b.customer_id = ?'
);
$stmt->execute([$bookingId, $_SESSION['customer_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: sports.php');
    exit;
}

$pageTitle = 'Booking Confirmed';
require 'includes/header.php';
?>

<div class="card" style="max-width:520px;margin:0 auto;">
    <div class="alert alert-success">Payment successful — your booking is confirmed!</div>
    <h1><?php echo e($booking['sport_name']); ?></h1>
    <table>
        <tr><th>Venue</th><td><?php echo e($booking['venue_name']); ?></td></tr>
        <tr><th>Date</th><td><?php echo e($booking['booking_date']); ?></td></tr>
        <tr><th>Time</th><td><?php echo e($booking['booking_time']); ?></td></tr>
        <tr><th>Amount Paid</th><td>£<?php echo number_format((float)$booking['price'], 2); ?></td></tr>
        <tr><th>Card</th><td>**** **** **** <?php echo e($booking['card_last4']); ?></td></tr>
        <tr><th>Transaction Ref</th><td><?php echo e($booking['transaction_ref']); ?></td></tr>
    </table>
    <p style="margin-top:20px;">
        <a href="sports.php" class="btn">Browse More Sports</a>
        <a href="messages.php" class="btn btn-secondary">Message Admin</a>
    </p>
</div>

<?php require 'includes/footer.php'; ?>
