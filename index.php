<?php
require 'includes/functions.php';
require 'includes/db.php';

$pageTitle = 'Home';
require 'includes/header.php';
?>

<div class="card">
    <h1>Welcome to SecureSport Bookings</h1>
    <p>
        Book sports venues online with confidence. Every booking on this platform is protected by a
        <strong>two-level security question verification</strong> step before any payment is taken —
        built to demonstrate how security-level techniques can mitigate fraud and account takeover on
        an online booking system.
    </p>
    <?php if (empty($_SESSION['customer_id'])): ?>
        <p>
            <a href="register.php" class="btn">Create an Account</a>
            <a href="login.php" class="btn btn-secondary">Log In</a>
        </p>
    <?php else: ?>
        <p><a href="sports.php" class="btn">Browse Sports</a></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>How the security levels work</h2>
    <ol>
        <li>Log in with your username and password.</li>
        <li>Choose a sport/venue and enter your booking details.</li>
        <li><strong>Security Level 1</strong> — answer your first security question.</li>
        <li><strong>Security Level 2</strong> — answer your second, different security question.</li>
        <li>Only after both levels pass are you able to reach the payment page.</li>
    </ol>
    <p style="color:#6b7280;font-size:0.9rem;">3 incorrect answers on either level locks the account for 15 minutes and signs you out, to prevent brute-force guessing.</p>
</div>

<?php require 'includes/footer.php'; ?>
