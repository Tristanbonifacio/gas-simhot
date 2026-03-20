<?php
/**
 * auth_guard.php — Session guard. Include at top of every protected page.
 *
 * Usage:
 *   require_once __DIR__ . '/../core/auth_guard.php';
 *   guard('admin');   // pass required role, or omit for any logged-in user
 *
 * Location: core/auth_guard.php
 */

require_once __DIR__ . '/../config/db_connect.php';

/**
 * Abort with redirect if user is not logged in or wrong role.
 */
function guard(?string $required_role = null): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . login_url());
        exit();
    }
    if ($required_role !== null && strtolower($_SESSION['role']) !== strtolower($required_role)) {
        header('Location: ' . dashboard_for($_SESSION['role']));
        exit();
    }
}

/**
 * Returns current logged-in user data from session.
 */
function current_user(): array {
    return [
        'id'        => (int)($_SESSION['user_id']   ?? 0),
        'full_name' => $_SESSION['full_name'] ?? 'Unknown',
        'role'      => $_SESSION['role']      ?? 'staff',
        'location'  => $_SESSION['location']  ?? 'General Area',
    ];
}