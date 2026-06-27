<?php
// public/health.php - endpoint simples de healthcheck para produção
require_once __DIR__ . '/../core/init.php';
header('Content-Type: application/json');

$checks = [];
$status = 'ok';

// Versão
$checks['version'] = [
  'version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
  'revision' => defined('APP_REVISION') ? APP_REVISION : 'unknown',
  'build_date' => defined('APP_BUILD_DATE') ? APP_BUILD_DATE : 'unknown'
];

// DB
try {
  $pdo->query('SELECT 1');
  $checks['database'] = ['ok' => true];
} catch (Throwable $e) {
  $checks['database'] = ['ok' => false, 'error' => $e->getMessage()];
  $status = 'degraded';
}

// Diretórios de escrita
$writeDirs = [
  __DIR__ . '/../uploads',
  __DIR__ . '/../uploads/tmp',
  __DIR__ . '/../uploads/thumbs'
];
$checks['writable'] = [];
foreach ($writeDirs as $dir) {
  $ok = is_dir($dir) && is_writable($dir);
  $checks['writable'][$dir] = $ok;
  if (!$ok) $status = 'degraded';
}

// Extensões PHP essenciais
$requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'gd'];
$ext = [];
foreach ($requiredExt as $ex) { $ext[$ex] = extension_loaded($ex); if (!$ext[$ex]) $status = 'degraded'; }
$checks['extensions'] = $ext;

http_response_code($status === 'ok' ? 200 : 206);

echo json_encode(['status' => $status, 'checks' => $checks], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
