<?php
declare(strict_types=1);
// Require login + single-session validation
require __DIR__ . '/auth.php';
header('Content-Type: application/json');

// Allow only your email to generate invites
$isAdmin = (strcasecmp($_SESSION['email'] ?? '', 'Joshua.barrett00@gmail.com') === 0);
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// Enforce POST to avoid accidental link creation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Simple CSRF check (token set in stream.php)
$csrfHeader = $_SERVER['HTTP_X_CSRF'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrfHeader)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'CSRF validation failed']);
    exit;
}

require __DIR__ . '/db_connect.php';

// 1) Generate secure token (raw for link, hash for DB)
$rawToken  = bin2hex(random_bytes(16));      // 32 hex chars
$tokenHash = hash('sha256', $rawToken);

// 2) Optional email binding (leave null)
$email     = null;

// 3) Expiry: 7 days
$expiresAt = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

// 4) Insert
$stmt = $pdo->prepare(
    "INSERT INTO invites (token_hash, email, expires_at, created_at)
     VALUES (?, ?, ?, NOW())"
);
$stmt->execute([$tokenHash, $email, $expiresAt]);

// 5) Return JSON link
$domain = "https://stream.barrettprojects.work";
$link   = $domain . "/register.php?token=" . $rawToken;

echo json_encode(['ok' => true, 'link' => $link, 'expires_at' => $expiresAt]);
