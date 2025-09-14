<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connect.php';

// Secure session cookie settings (good practice)
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_secure','1');     // you’re behind HTTPS via Cloudflare
ini_set('session.cookie_samesite','Lax');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Create a brand-new login session token
        $rawToken  = bin2hex(random_bytes(32));            // 64 hex chars (cookie)
        $tokenHash = hash('sha256', $rawToken);            // store only hash in DB

        $pdo->beginTransaction();

        // Ensure only ONE active session per user by replacing any existing row
        $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$user['id']]);
        $pdo->prepare("INSERT INTO sessions (token_hash, user_id, user_agent, ip) VALUES (?, ?, ?, ?)")
            ->execute([
                $tokenHash,
                (int)$user['id'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

        $pdo->commit();

        // Rotate PHP session and set identifiers
        session_regenerate_id(true);
        $_SESSION['email'] = $user['email'];
        $_SESSION['uid']   = (int)$user['id'];

        // Set our token cookie (HTTP-only, Secure)
        setcookie('SESSION_TOKEN', $rawToken, [
            'expires'  => time() + 60*60*24*7,  // 7 days
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        header("Location: stream.php");
        exit;
    } else {
        echo "❌ Invalid email or password.<br><a href='login.php'>Try again</a>";
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Database error.";
}
