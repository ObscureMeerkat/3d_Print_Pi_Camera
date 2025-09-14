<?php
// auth.php — include this at the top of any protected page
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connect.php';

function force_logout_and_redirect(string $reason = ''): void {
    // destroy PHP session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    // clear our session token cookie
    setcookie('SESSION_TOKEN', '', time() - 3600, '/', '', true, true);
    $loc = 'login.php';
    if ($reason !== '') $loc .= '?msg=' . urlencode($reason);
    header("Location: $loc");
    exit;
}

// Must be logged in at PHP level
if (!isset($_SESSION['uid'], $_SESSION['email'])) {
    force_logout_and_redirect();
}

// Must have our token cookie
if (empty($_COOKIE['SESSION_TOKEN'])) {
    force_logout_and_redirect('Signed out (missing token).');
}

// Validate token matches the one stored in DB for this user
$raw = $_COOKIE['SESSION_TOKEN'];
$hash = hash('sha256', $raw);

$stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE token_hash = ? LIMIT 1");
$stmt->execute([$hash]);
$row = $stmt->fetch();

if (!$row || (int)$row['user_id'] !== (int)$_SESSION['uid']) {
    // Token is not the current one for this user -> they logged in somewhere else
    force_logout_and_redirect('You were signed out because your account was used on another device.');
}

// Optional: touch last_seen
$pdo->prepare("UPDATE sessions SET last_seen = NOW() WHERE token_hash = ?")->execute([$hash]);
