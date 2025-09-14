<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stream.php'); exit;
}

// If we have a token cookie, delete that session row
if (!empty($_COOKIE['SESSION_TOKEN']) && isset($_SESSION['uid'])) {
    $hash = hash('sha256', $_COOKIE['SESSION_TOKEN']);
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE token_hash = ? AND user_id = ?");
    $stmt->execute([$hash, (int)$_SESSION['uid']]);
}

// destroy PHP session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

// clear token cookie
setcookie('SESSION_TOKEN', '', time() - 3600, '/', '', true, true);

header("Location: login.php");
exit;
