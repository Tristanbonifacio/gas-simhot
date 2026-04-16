<?php
/**
 * auth_guard.php — Session guard + automatic page access logger.
 * Include at top of every protected page.
 * Location: core/auth_guard.php
 */

require_once __DIR__ . '/../config/db_connect.php';

/**
 * Abort with redirect if not logged in or wrong role.
 * Also auto-logs the page access with IP + browser info.
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
    // Auto-log page visit (GET only — skip AJAX/POST)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        log_page_access();
    }
}

/**
 * Records a "Visited: Page" entry — throttled to once per 30s
 * per user per page to prevent log flooding on refresh.
 */
function log_page_access(): void {
    $uid = (int)$_SESSION['user_id'];
    $db  = db();

    // Friendly page name from script filename
    $script   = basename($_SERVER['SCRIPT_NAME'] ?? 'page.php', '.php');
    $friendly = ucwords(str_replace(['_dashboard','_'], [' Dashboard',' '], $script));
    $action   = $db->real_escape_string('Visited: ' . trim($friendly));

    // Throttle — skip if same page visited in last 30s by same user
    $chk = $db->query("SELECT id FROM user_activity_logs
                        WHERE user_id=$uid AND action='$action'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                        LIMIT 1");
    if ($chk && $chk->num_rows > 0) return;

    $ip    = _get_client_ip();
    $ua    = $db->real_escape_string(mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500));
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url   = $db->real_escape_string(mb_substr($proto.'://'.$_SERVER['HTTP_HOST'].($_SERVER['REQUEST_URI'] ?? '/'), 0, 500));

    $result = $db->query("INSERT INTO user_activity_logs
        (user_id, action, ip_address, user_agent, page_url, created_at)
        VALUES ($uid, '$action', '$ip', '$ua', '$url', NOW())");

    // Graceful fallback if columns not migrated yet
    if (!$result) {
        $db->query("INSERT INTO user_activity_logs (user_id, action)
                    VALUES ($uid, '$action')");
    }
}

/**
 * Get real client IP — checks proxy headers in priority order.
 */
function _get_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Returns current logged-in user from session.
 */
function current_user(): array {
    return [
        'id'        => (int)($_SESSION['user_id']   ?? 0),
        'full_name' => $_SESSION['full_name']        ?? 'Unknown',
        'role'      => $_SESSION['role']             ?? 'staff',
        'location'  => $_SESSION['location']         ?? 'General Area',
    ];
}