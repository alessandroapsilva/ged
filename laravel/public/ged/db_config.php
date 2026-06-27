<?php
// Compat: camada antiga de config para scripts legados
// Fornece $pdo a partir de config.php e define BASE_URL quando necessário

// Raiz do projeto
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// Carrega configurações e função getDBConnection()
require_once __DIR__ . '/config.php';

// Instancia conexão se ainda não existir
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = getDBConnection();
}

// Garante BASE_URL para scripts que usam assets/paths
if (!defined('BASE_URL')) {
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['SCRIPT_NAME'])) {
        $dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if (preg_match('#^(.*?)(/public)(?:/.*)?$#', $dir, $m)) {
            $dir = rtrim($m[1], '/');
        }
        define('BASE_URL', $dir === '/' ? '' : $dir);
    } else {
        define('BASE_URL', '/ged');
    }
}
