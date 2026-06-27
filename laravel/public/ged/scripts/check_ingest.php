<?php
// Quick check for ingest tables
$dsn = 'mysql:host=localhost;dbname=ged;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '');
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
if (!$tables) {
    echo "NO_TABLES\n";
    exit(0);
}
foreach ($tables as $row) {
    echo $row[0], "\n";
}
