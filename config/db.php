<?php
/**
 * Database connection (PDO / MySQL)
 * Works with Railway MYSQL_URL and local XAMPP MySQL.
 */

$databaseUrl = getenv('MYSQL_URL');

if ($databaseUrl) {
    // Railway MySQL
    $db = parse_url($databaseUrl);

    $DB_HOST = $db['host'] ?? '';
    $DB_PORT = $db['port'] ?? 3306;
    $DB_USER = isset($db['user']) ? urldecode($db['user']) : '';
    $DB_PASS = isset($db['pass']) ? urldecode($db['pass']) : '';
    $DB_NAME = isset($db['path']) ? ltrim($db['path'], '/') : '';

    // Debug (temporary)
    error_log("DB_HOST: $DB_HOST");
    error_log("DB_PORT: $DB_PORT");
    error_log("DB_NAME: $DB_NAME");
    error_log("DB_USER: $DB_USER");

    try {
        $pdo = new PDO(
            "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage() . ' | Host: ' . $DB_HOST . ' | Port: ' . $DB_PORT . ' | DB: ' . $DB_NAME);
    }

} else {
    // Local XAMPP MySQL
    $DB_HOST = '127.0.0.1';
    $DB_PORT = 3306;
    $DB_NAME = 'iba_appointment_system';
    $DB_USER = 'root';
    $DB_PASS = '';

    try {
        $pdo = new PDO(
            "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
