<?php
header('Content-Type: text/plain');
$u = getenv('MYSQL_URL');
echo "MYSQL_URL set: " . ($u ? 'YES' : 'NO') . "\n";
if ($u) {
    $p = parse_url($u);
    echo "host: " . ($p['host'] ?? '') . "\n";
    echo "port: " . ($p['port'] ?? '') . "\n";
    echo "db: " . ltrim($p['path'] ?? '', '/') . "\n";
}
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: '(empty)') . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? '') . "\n";
$f = file_get_contents(__DIR__ . '/config/db.php');
echo "db.php is NEW code: " . (strpos($f, '| Host:') !== false ? 'YES' : 'NO') . "\n";echo "ENV KEYS: " . implode(", ", array_keys(getenv())) . "\n";
