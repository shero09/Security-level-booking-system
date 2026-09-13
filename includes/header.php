<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' | ' : ''; ?>Security-Level Sports Booking System</title>
<link rel="stylesheet" href="<?php echo $assetPrefix ?? ''; ?>css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?php echo $assetPrefix ?? ''; ?>index.php" class="logo">SecureSport Booking</a>
        <nav>
            <?php if (!empty($_SESSION['customer_id'])): ?>
                <span class="nav-user">Hi, <?php echo e($_SESSION['customer_username']); ?></span>
                <a href="<?php echo $assetPrefix ?? ''; ?>sports.php">Sports</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>messages.php">Messages</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>logout.php">Logout</a>
            <?php elseif (!empty($_SESSION['admin_id'])): ?>
                <span class="nav-user">Admin: <?php echo e($_SESSION['admin_username']); ?></span>
                <a href="<?php echo $assetPrefix ?? ''; ?>admin/dashboard.php">Dashboard</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>admin/messages.php">Messages</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $assetPrefix ?? ''; ?>login.php">Customer Login</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>register.php">Register</a>
                <a href="<?php echo $assetPrefix ?? ''; ?>admin/login.php">Admin Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
