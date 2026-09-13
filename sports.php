<?php
require 'includes/functions.php';
require 'includes/db.php';
require_login();

$sports = $pdo->query('SELECT * FROM sports ORDER BY sport_name')->fetchAll();

$pageTitle = 'Available Sports';
require 'includes/header.php';
?>

<h1>Available Sports & Venues</h1>
<p>Select a sport to view details and start a booking.</p>

<div class="sports-grid">
    <?php foreach ($sports as $sport): ?>
        <div class="card sport-card">
            <h3><?php echo e($sport['sport_name']); ?></h3>
            <div class="sport-meta">
                <?php echo e($sport['venue_name']); ?><br>
                Venue #<?php echo e($sport['venue_number']); ?> · <?php echo e($sport['venue_postcode']); ?>
            </div>
            <p><?php echo e($sport['description']); ?></p>
            <div class="sport-price">£<?php echo number_format((float)$sport['price'], 2); ?></div>
            <a class="btn" href="booking.php?sport_id=<?php echo (int)$sport['id']; ?>">Book Now</a>
        </div>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>
