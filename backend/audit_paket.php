<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=simrs_legacy', 'root', '');
$stmt = $pdo->query("SHOW TABLES LIKE '%paket%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables with 'paket':\n";
print_r($tables);

$stmt = $pdo->query("SHOW TABLES LIKE '%tindakan%'");
$tables2 = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "\nTables with 'tindakan':\n";
print_r($tables2);
