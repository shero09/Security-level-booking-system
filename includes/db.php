<?php
/**
 * Database connection (PDO, prepared statements only — no raw string
 * concatenation of user input is used anywhere in this project, which
 * is what prevents SQL injection).
 */

$DB_HOST = 'localhost';
$DB_NAME = 'sports_booking_system';
$DB_USER = 'root';       // change to your MySQL username
$DB_PASS = '';           // change to your MySQL password

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never leak DB credentials or raw exception details to the browser.
    error_log('DB connection failed: ' . $e->getMessage());
    die('A system error occurred. Please try again later.');
}
