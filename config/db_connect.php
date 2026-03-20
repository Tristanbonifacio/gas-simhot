<?php
/**
 * db_connect.php — Single source of truth for DB + URL helpers.
 * Location: config/db_connect.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Credentials ───────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'u442411629_dev_gasleak');
define('DB_PASS', 'Hs29/:E2£+YC');
define('DB_NAME', 'u442411629_gasleak');

// ── PPM thresholds ────────────────────────────────────────────
define('PPM_SAFE',    200);
define('PPM_WARNING', 350);
define('PPM_DANGER',  450);

// ── Site name ─────────────────────────────────────────────────
define('SITE_NAME', 'GAS-SIMHOT');

// ── MySQLi singleton ──────────────────────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            die('<!DOCTYPE html><html><head><title>DB Error</title>
            <style>body{background:#050d1a;color:#cfe8ff;font-family:Arial;display:flex;
            align-items:center;justify-content:center;height:100vh;margin:0;}
            .box{background:#0b1629;border:1px solid #ff4c4c;border-radius:10px;
            padding:2rem;max-width:480px;text-align:center;}h2{color:#ff4c4c;}</style>
            </head><body><div class="box">
            <h2>&#9888; Database Connection Failed</h2>
            <p>Please check your database credentials or contact your administrator.</p>
            </div></body></html>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── Base URL ──────────────────────────────────────────────────
// __DIR__ of this file = /home/.../public_html/gasleak/config
// So the project root folder name = basename(dirname(__DIR__))
// We derive the URL path from SCRIPT_NAME which always starts
// with /gasleak/... giving us the subfolder reliably.
function base_url(): string {
    static $base = null;
    if ($base !== null) return $base;

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // SCRIPT_NAME = /gasleak/admin/admin_dashboard.php
    // We need just /gasleak/ — remove filename + one subfolder
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $parts  = explode('/', trim($script, '/'));

    // parts = ['gasleak', 'admin', 'admin_dashboard.php'] — length 3
    // parts = ['gasleak', 'index.php']                    — length 2
    // parts = ['gasleak', 'debug.php']                    — length 2
    // Always remove the filename (last element)
    array_pop($parts);

    // If still more than 1 part, the last one is a subfolder — remove it
    // e.g. ['gasleak', 'admin'] → remove 'admin' → ['gasleak']
    // e.g. ['gasleak'] — root-level file, don't remove anything
    if (count($parts) > 1) {
        array_pop($parts);
    }

    // parts is now just ['gasleak']
    $path = '/' . implode('/', $parts) . '/';

    $base = $proto . '://' . $host . $path;
    return $base;
}

// ── URL helpers ───────────────────────────────────────────────
function login_url(): string    { return base_url() . 'auth/login.php'; }
function logout_url(): string   { return base_url() . 'auth/logout.php'; }
function register_url(): string { return base_url() . 'user/register.php'; }

function dashboard_for(string $role): string {
    $map = [
        'admin'   => base_url() . 'admin/admin_dashboard.php',
        'manager' => base_url() . 'manager/manager_dashboard.php',
        'staff'   => base_url() . 'user/user_dashboard.php',
    ];
    return $map[strtolower($role)] ?? login_url();
}