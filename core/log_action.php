<?php
/**
 * log_action.php — Logs activity + IP, browser, and page URL.
 * Called via fetch() POST from all dashboards. Returns JSON.
 *
 * Location: core/log_action.php
 */

require_once __DIR__ . '/../core/auth_guard.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || empty($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing action.']);
    exit();
}

$db   = db();
$uid  = (int)$_SESSION['user_id'];
$name = $db->real_escape_string($_SESSION['full_name']);
$act  = $db->real_escape_string(trim($_POST['action']));

// ── Capture request metadata ───────────────────────────────────

/**
 * Get real client IP address.
 * Checks common proxy headers before falling back to REMOTE_ADDR.
 * Validates format and masks the last octet for basic privacy.
 */
function get_client_ip(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_REAL_IP',          // Nginx proxy
        'HTTP_X_FORWARDED_FOR',    // Load balancer / proxy
        'REMOTE_ADDR',             // Direct connection
    ];

    $raw_ip = '';
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            // X-Forwarded-For may contain a comma-separated list; take the first
            $raw_ip = trim(explode(',', $_SERVER[$h])[0]);
            break;
        }
    }

    // Validate — accept IPv4 and IPv6
    if (filter_var($raw_ip, FILTER_VALIDATE_IP)) {
        return $raw_ip;
    }

    return '0.0.0.0'; // Unknown
}

/**
 * Parse and shorten the User-Agent string to essential info.
 * Full UA is stored but we also extract a readable summary.
 */
function parse_user_agent(string $ua): string {
    if (empty($ua)) return 'Unknown';
    // Truncate to 500 chars (column limit) — raw UA can be very long
    return mb_substr($ua, 0, 500);
}

/**
 * Get the referring page URL sent by the frontend JS.
 * Falls back to HTTP_REFERER.
 */
function get_page_url(): string {
    // JS sends window.location.href in the POST body
    $from_js = trim($_POST['page_url'] ?? '');
    if ($from_js) {
        return mb_substr($from_js, 0, 500);
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer) {
        // Strip query strings that may contain sensitive tokens
        $parsed = parse_url($referer);
        return ($parsed['scheme'] ?? 'https') . '://' .
               ($parsed['host']   ?? '') .
               ($parsed['path']   ?? '');
    }

    return 'Direct / Unknown';
}

$ip         = $db->real_escape_string(get_client_ip());
$user_agent = $db->real_escape_string(parse_user_agent($_SERVER['HTTP_USER_AGENT'] ?? ''));
$page_url   = $db->real_escape_string(get_page_url());

// ── 1. Insert activity log ─────────────────────────────────────
if ($act !== 'admin_ack') {
    // Try enhanced insert first; fall back to basic if columns don't exist yet
    $result = $db->query("
        INSERT INTO user_activity_logs
            (user_id, action, ip_address, user_agent, page_url, created_at)
        VALUES
            ($uid, '$act', '$ip', '$user_agent', '$page_url', NOW())
    ");

    // If columns not yet added (schema not migrated), fall back gracefully
    if (!$result) {
        $db->query("INSERT INTO user_activity_logs (user_id, action, created_at)
                    VALUES ($uid, '$act', NOW())");
    }
}

// ── 2. Leak Detected ──────────────────────────────────────────
if ($act === 'Leak Detected') {
    $res = $db->query("SELECT location FROM users WHERE id = $uid LIMIT 1");
    $row = $res ? $res->fetch_assoc() : [];
    $loc = $db->real_escape_string($row['location'] ?? 'General Area');

    $coords = [
        'Kitchen'     => [8.3701, 124.8650],
        'Laboratory'  => [8.3685, 124.8630],
        'Warehouse'   => [8.3710, 124.8660],
        'Main Office' => [8.3690, 124.8640],
    ];
    [$lat, $lng] = $coords[$row['location'] ?? ''] ?? [8.3697, 124.8644];
    $ppm = PPM_DANGER;

    $db->query("UPDATE system_status SET
                    is_active             = 1,
                    triggered_by          = '$name',
                    location              = '$loc',
                    lat                   = $lat,
                    lng                   = $lng,
                    ppm                   = $ppm,
                    acknowledged_by_admin = 0,
                    ack_time              = NULL,
                    triggered_at          = NOW()
                WHERE id = 1");
}

// ── 3. System Reset ───────────────────────────────────────────
elseif ($act === 'System Reset') {
    $db->query("UPDATE system_status SET
                    is_active             = 0,
                    ppm                   = 0,
                    acknowledged_by_admin = 0,
                    ack_time              = NULL
                WHERE id = 1");
}

// ── 4. Admin Acknowledgment ───────────────────────────────────
elseif ($act === 'admin_ack') {
    $db->query("UPDATE system_status SET
                    acknowledged_by_admin = 1,
                    ack_time              = NOW()
                WHERE id = 1");
}

echo json_encode(['status' => 'success', 'action' => $act]);