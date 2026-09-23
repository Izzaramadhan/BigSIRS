<?php

$host = '127.0.0.1';
$port = '3307';
$db   = 'simrs_legacy';
$user = 'legacy_reader';
$pass = 'Admin12345';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = 'simrs_legacy' AND (TABLE_NAME LIKE '%kategori%' OR TABLE_NAME LIKE '%tindakan%' OR TABLE_NAME LIKE '%tarif%')");
$tables = $stmt->fetchAll();

foreach ($tables as $row) {
    echo $row['TABLE_NAME'] . "\n";
}
