<?php
$dsn = 'mysql:host=localhost;dbname=ged;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '');
$sqlFile = __DIR__ . '/../sql/20251030_create_ingest.sql';
$sql = file_get_contents($sqlFile);
echo "LEN SQL: ".strlen($sql)."\n";
$stmts = preg_split('/;\s*\n/', $sql);
echo "STMTS: ".count($stmts)."\n";
foreach ($stmts as $i => $stmtRaw) {
    $stmt = trim($stmtRaw);
    echo "-- STMT #$i LEN=".strlen($stmt)."\n";
    echo substr($stmt,0,80),"...\n";
    if ($stmt === '' || strpos(ltrim($stmt), '--') === 0) continue;
    try {
        $pdo->exec($stmt);
        echo "OK: ".$stmt."\n";
    } catch (Throwable $e) {
        echo "ERR: ".$e->getMessage()."\nSTMT: ".$stmt."\n";
    }
}
