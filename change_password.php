<?php
declare(strict_types=1);
require __DIR__ . '/auth.php'; // requires login + validates single-session

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current'] ?? '';
    $new1    = $_POST['new1'] ?? '';
    $new2    = $_POST['new2'] ?? '';

    if ($new1 !== $new2) {
        $err = 'New passwords do not match.';
    } elseif (strlen($new1) < 8) {
        $err = 'New password must be at least 8 characters.';
    } else {
        // Fetch current hash
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['uid']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $err = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($new1, PASSWORD_BCRYPT);

            $pdo->beginTransaction();
            // Update password
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $_SESSION['uid']]);
            // Invalidate all sessions for this user (force re-login)
            $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$_SESSION['uid']]);
            $pdo->commit();

            // Destroy PHP session + cookie and send to login
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
            }
            session_destroy();
            setcookie('SESSION_TOKEN', '', time() - 3600, '/', '', true, true);

            header('Location: login.php?msg=' . urlencode('Password updated. Please sign in again.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
 body{background:#111;color:#fff;font-family:Arial,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;height:100vh}
 .card{background:#222;padding:2rem;border-radius:8px;width:min(420px, 92vw)}
 input{width:100%;padding:.6rem;margin:.4rem 0;border-radius:4px;border:none}
 button{width:100%;padding:.7rem;margin-top:.6rem;background:#3AAA35;border:none;border-radius:4px;color:#fff;font-weight:bold;cursor:pointer}
 .err{color:#ff9b9b;margin:.4rem 0}
 .msg{color:#9be79b;margin:.4rem 0}
 a{color:#9be79b}
</style>
</head>
<body>
  <div class="card">
    <h2>Change Password</h2>
    <?php if($err): ?><div class="err"><?=htmlspecialchars($err)?></div><?php endif; ?>
    <?php if($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>
    <form method="post" autocomplete="current-password">
      <label>Current password</label>
      <input type="password" name="current" required>
      <label>New password</label>
      <input type="password" name="new1" required>
      <label>Confirm new password</label>
      <input type="password" name="new2" required>
      <button type="submit">Update Password</button>
    </form>
    <p><a href="stream.php">Back to Stream</a></p>
  </div>
</body>
</html>
