<?php
// db_connect.php — PDO version (used by generate_invite.php and register.php)

$DB_HOST = 'localhost';
$DB_NAME = 'your_database';
$DB_USER = 'your_username';
$DB_PASS = 'your_password';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw exceptions (easier debugging)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // In production, don't echo $e->getMessage()
    http_response_code(500);
    exit('Database connection failed.');
}
