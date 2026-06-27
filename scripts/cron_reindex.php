<?php
// CLI/cron: Reindexar todos os documentos
// Uso no Windows Task Scheduler: php c:\xampp\htdocs\ged\scripts\cron_reindex.php

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/db_config.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';

// cria $pdo compatível (db_config.php fornece $pdo ou função getDBConnection dependendo do projeto)
if (!isset($pdo)) {
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
    } else {
        // fallback minimal
        $host = 'localhost'; $db='ged'; $user='root'; $pass=''; $charset='utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}

$indexer = new PDFIndexer($pdo);
$inicio = microtime(true);
$res = $indexer->indexarTodosDocumentos();
$dur = microtime(true) - $inicio;

$summary = sprintf("%s - Indexação: %d sucesso(s), %d erro(s) de %d em %.2fs\n",
    date('Y-m-d H:i:s'), $res['sucessos'] ?? 0, $res['erros'] ?? 0, $res['total'] ?? 0, $dur);

echo $summary;

// Auditoria (se tabela existir)
try {
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
    $st->execute([1, 'REINDEX_ALL', 'system', null, json_encode(['summary'=>$summary, 'errors'=>$res['falhas'] ?? []], JSON_UNESCAPED_UNICODE), null]);
} catch (Throwable $e) {
    // ignora
}
