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

$pdo = new PDO($dsn, $user, $pass, $options);

// Inspect columns
$stmt = $pdo->query("DESCRIBE ref_kategori_tindakan");
$columns = $stmt->fetchAll();
echo "COLUMNS:\n";
foreach ($columns as $col) {
    echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']} - Extra: {$col['Extra']}\n";
}

echo "\nSAMPLE DATA:\n";
$stmt = $pdo->query("SELECT * FROM ref_kategori_tindakan LIMIT 5");
$sample = $stmt->fetchAll();
print_r($sample);

echo "\nCOUNT:\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ref_kategori_tindakan");
$count = $stmt->fetch();
echo "Total rows: {$count['total']}\n";

// check duplicates, trailing spaces, nulls
echo "\nPROFILING:\n";

// Check if description is null/empty
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ref_kategori_tindakan WHERE deskripsi IS NULL OR deskripsi = ''");
$res = $stmt->fetch();
echo "Empty/Null description: {$res['total']}\n";

// Check deleted_at mechanism
$stmt = $pdo->query("SELECT deleted_at, COUNT(*) as cnt FROM ref_kategori_tindakan GROUP BY deleted_at");
$res = $stmt->fetchAll();
echo "Deleted_at distribution:\n";
print_r($res);

// Check status distribution
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM ref_kategori_tindakan GROUP BY status");
$res = $stmt->fetchAll();
echo "Status distribution:\n";
print_r($res);

// Check duplicate names (trim and case-insensitive)
$stmt = $pdo->query("SELECT LOWER(TRIM(nama)) as n, COUNT(*) as cnt FROM ref_kategori_tindakan GROUP BY LOWER(TRIM(nama)) HAVING cnt > 1");
$res = $stmt->fetchAll();
echo "Duplicate names:\n";
print_r($res);

// Check missing or duplicate kode
$stmt = $pdo->query("SELECT kode, COUNT(*) as cnt FROM ref_kategori_tindakan GROUP BY kode HAVING cnt > 1 OR kode IS NULL OR kode = ''");
$res = $stmt->fetchAll();
echo "Duplicate or Null kode:\n";
print_r($res);

$stmt = $pdo->query("SELECT id_kategori, COUNT(*) as cnt FROM ref_tarif_tindakan GROUP BY id_kategori");
$usages = $stmt->fetchAll();
echo "Usages in ref_tarif_tindakan:\n";
print_r($usages);

