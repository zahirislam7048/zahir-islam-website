<?php
declare(strict_types=1);
$cfgFile = __DIR__ . '/../config.php';
if (!is_file($cfgFile)) { http_response_code(500); exit('Missing config.php. Copy config.sample.php to config.php and fill it in.'); }
$CFG = require $cfgFile;

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function cfg(string $k) { global $CFG; return $CFG[$k] ?? null; }

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(cfg('db_dsn'), cfg('db_user'), cfg('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) { http_response_code(403); exit('Invalid request token. Reload the page and try again.'); }
}

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $u;
    if ($u === null) {
        $s = db()->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
        $s->execute([$_SESSION['uid']]);
        $u = $s->fetch() ?: null;
    }
    return $u;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}
function json_out($data, int $code = 200): void {
    http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit;
}
function api_user(): array {
    $u = current_user();
    if (!$u) json_out(['error' => 'Please log in again.'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'POST only'], 405);
    csrf_check();
    return $u;
}
function scans_this_month(int $uid): int {
    $s = db()->prepare('SELECT COUNT(*) FROM scans WHERE user_id = ? AND created_at >= ?');
    $s->execute([$uid, date('Y-m-01 00:00:00')]);
    return (int)$s->fetchColumn();
}
function now(): string { return date('Y-m-d H:i:s'); }
