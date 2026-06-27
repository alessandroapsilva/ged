<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/ingest_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('require_permission')) { require_permission('ingest.import'); }

// Lê a pasta monitorada de app_settings ou configuracoes
$pasta = null;
try {
    // Tenta app_settings
    $st = $pdo->prepare("SELECT valor FROM app_settings WHERE chave = 'INGEST_PASTA_MONITORADA'");
    $st->execute();
    $pasta = $st->fetchColumn() ?: null;
    if (!$pasta) {
        // Fallback configuracoes
        $st2 = $pdo->prepare("SELECT config_valor FROM configuracoes WHERE config_chave = 'INGEST_PASTA_MONITORADA'");
        $st2->execute();
        $pasta = $st2->fetchColumn() ?: null;
    }
} catch (Throwable $e) { /* ignore */ }

if (!$pasta || !is_dir($pasta)) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Pasta monitorada não configurada ou inexistente.'];
    header('Location: ingest.php');
    exit();
}

$importados = 0; $falhas = 0; $erros = [];

$files = glob(rtrim($pasta, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.pdf');
foreach ($files as $fp) {
    try {
        ingest_importar_arquivo($pdo, $fp, basename($fp), 'SP', (int)$_SESSION['user_id']);
        $importados++;
    } catch (Throwable $e) {
        $falhas++; $erros[] = basename($fp) . ' (' . $e->getMessage() . ')';
    }
}

if ($falhas === 0) {
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => "Importação concluída: {$importados} arquivo(s)."];
} else {
    $msg = "Importação parcial: {$importados} sucesso(s), {$falhas} falha(s).";
    if (!empty($erros)) { $msg .= ' Erros: ' . implode('; ', array_slice($erros, 0, 5)) . (count($erros) > 5 ? '...' : ''); }
    $_SESSION['flash_message'] = ['type' => 'alerta', 'text' => $msg];
}

header('Location: ingest.php');
exit();
?>
