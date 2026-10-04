<?php

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    $databaseUrl = getenv('MYSQL_URL');
}

if (!$databaseUrl) {
    $databaseUrl = getenv('RAILWAY_SERVICE_MYSQL_URL');
}

if (!$databaseUrl) {
    die("Database connection failed: No Railway MySQL connection URL found.");
}

$db = parse_url($databaseUrl);

$host = $db['host'] ?? 'localhost';
$port = $db['port'] ?? 3306;
$user = $db['user'] ?? '';
$pass = $db['pass'] ?? '';
$name = isset($db['path']) ? ltrim($db['path'], '/') : '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}