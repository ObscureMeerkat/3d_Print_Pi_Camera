<?php
require 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Check if token is provided
if (!isset($_GET['token'])) {
    die("No token provided.");
}

$rawToken = $_GET['token'];
$tokenHash = hash('sha256', $rawToken);

// Step 2: Validate token
$stmt = $pdo->prepare("SELECT * FROM invites WHERE token_hash = ? LIMIT 1");
$stmt->execute([$tokenHash]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    die("Invalid invite token.");
}
if ($invite['used_at']) {
    die("This invite has already been used.");
}
if (strtotime($invite['expires_at']) < time()) {
    die("This invite has expired.");
}

// Step 3: If form submitted, process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
    } elseif (strlen($password) < 8) {
        echo "Password must be at least 8 characters.";
    } else {
        // Hash password with bcrypt
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        try {
            $stmt->execute([$email, $passwordHash]);

            // Mark invite as used
            $stmt = $pdo->prepare("UPDATE invites SET used_at = NOW() WHERE token_hash = ?");
            $stmt->execute([$tokenHash]);

            echo "Registration successful! You can now log in.";
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "That email is already registered.";
            } else {
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Step 4: Registration form -->
<h2>Register</h2>
<form method="post">
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Register</button>
</form>
