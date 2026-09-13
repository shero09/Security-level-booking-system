<?php
/**
 * Shared security + helper functions used across the whole system.
 */

// --- Session hardening -------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    // Set to 1 once the site is served over HTTPS in production.
    ini_set('session.cookie_secure', 0);
    session_start();
}

/** Regenerate the session id — call this on every privilege change (login, logout). */
function regenerate_session(): void
{
    session_regenerate_id(true);
}

// --- CSRF protection -----------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

// --- Output escaping -----------------------------------------------------
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// --- Auth guards -----------------------------------------------------------
function require_login(): void
{
    if (empty($_SESSION['customer_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/** A booking must have cleared BOTH security question levels before payment. */
function require_security_cleared(): void
{
    if (empty($_SESSION['security_level_1_passed']) || empty($_SESSION['security_level_2_passed'])) {
        header('Location: security_level1.php');
        exit;
    }
}

// --- Account lockout helpers ------------------------------------------------
const MAX_ATTEMPTS   = 3;
const LOCKOUT_MINUTES = 15;

/** Returns true if the given customer's account is currently locked. */
function is_locked(PDO $pdo, int $customerId): bool
{
    $stmt = $pdo->prepare('SELECT locked_until FROM customers WHERE id = ?');
    $stmt->execute([$customerId]);
    $lockedUntil = $stmt->fetchColumn();

    if ($lockedUntil && strtotime($lockedUntil) > time()) {
        return true;
    }
    return false;
}

function register_failed_attempt(PDO $pdo, int $customerId): void
{
    $stmt = $pdo->prepare('SELECT failed_attempts FROM customers WHERE id = ?');
    $stmt->execute([$customerId]);
    $attempts = (int) $stmt->fetchColumn() + 1;

    if ($attempts >= MAX_ATTEMPTS) {
        $lockUntil = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'));
        $stmt = $pdo->prepare('UPDATE customers SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $stmt->execute([$attempts, $lockUntil, $customerId]);
    } else {
        $stmt = $pdo->prepare('UPDATE customers SET failed_attempts = ? WHERE id = ?');
        $stmt->execute([$attempts, $customerId]);
    }
}

function reset_failed_attempts(PDO $pdo, int $customerId): void
{
    $stmt = $pdo->prepare('UPDATE customers SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
    $stmt->execute([$customerId]);
}

function remaining_attempts(PDO $pdo, int $customerId): int
{
    $stmt = $pdo->prepare('SELECT failed_attempts FROM customers WHERE id = ?');
    $stmt->execute([$customerId]);
    return max(0, MAX_ATTEMPTS - (int) $stmt->fetchColumn());
}
