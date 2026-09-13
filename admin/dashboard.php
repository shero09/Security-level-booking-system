<?php
require '../includes/functions.php';
require '../includes/db.php';
require_admin();

$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking_id'])) {
    verify_csrf();
    $bookingId = (int) $_POST['delete_booking_id'];
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $notice = 'Booking #' . $bookingId . ' was deleted.';
}

$bookings = $pdo->query(
    "SELECT b.id, b.booking_date, b.booking_time, b.venue_number, b.status,
            c.first_name, c.last_name, c.username, c.email,
            s.sport_name, p.amount, p.transaction_ref
     FROM bookings b
     JOIN customers c ON c.id = b.customer_id
     JOIN sports s ON s.id = b.sport_id
     LEFT JOIN payments p ON p.booking_id = b.id
     ORDER BY b.created_at DESC"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
$assetPrefix = '../';
require '../includes/header.php';
?>

<h1>Admin Dashboard</h1>
<p>All customer bookings. Delete only when the customer has requested a change or cancellation.</p>

<?php if ($notice): ?><div class="alert alert-success"><?php echo e($notice); ?></div><?php endif; ?>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Customer</th><th>Sport</th><th>Date</th><th>Time</th>
                <th>Venue</th><th>Amount</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo (int)$b['id']; ?></td>
                    <td><?php echo e($b['first_name'] . ' ' . $b['last_name']); ?><br><small><?php echo e($b['email']); ?></small></td>
                    <td><?php echo e($b['sport_name']); ?></td>
                    <td><?php echo e($b['booking_date']); ?></td>
                    <td><?php echo e($b['booking_time']); ?></td>
                    <td><?php echo e($b['venue_number']); ?></td>
                    <td><?php echo $b['amount'] !== null ? '£' . number_format((float)$b['amount'], 2) : '—'; ?></td>
                    <td><span class="badge badge-<?php echo e($b['status'] === 'confirmed' ? 'confirmed' : ($b['status'] === 'cancelled' ? 'cancelled' : 'pending')); ?>"><?php echo e($b['status']); ?></span></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this booking?');" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="delete_booking_id" value="<?php echo (int)$b['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="9">No bookings yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../includes/footer.php'; ?>
