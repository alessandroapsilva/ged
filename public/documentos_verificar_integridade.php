<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';

require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: documentos.php');
    exit();
}
if (!csrf_validate()) { http_response_code(403); exit('CSRF inválido'); }

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
if ($documento_id <= 0) { header('Location: documentos.php'); exit(); }

try {
    $stmt = $pdo->prepare("SELECT id, caminho_arquivo, hash_arquivo FROM documentos WHERE id = ? AND apagado_em IS NULL");
    $stmt->execute([$documento_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Documento não encontrado.'];
        header('Location: documentos.php');
        exit();
    }
    $path = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];
    if (!file_exists($path)) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Arquivo físico não encontrado para verificar.'];
        header('Location: documentos_propriedades.php?id=' . $documento_id . '#integridade');
        exit();
    }
    $hashAtual = hash_file('sha256', $path);
    $hashArmazenado = $doc['hash_arquivo'] ?? '';
    $ok = (!empty($hashArmazenado) && hash_equals($hashArmazenado, $hashAtual));

    // Auditoria simples
    try {
        $det = json_encode(['documento_id' => $documento_id, 'ok' => $ok, 'hash_atual' => $hashAtual, 'hash_stored' => $hashArmazenado], JSON_UNESCAPED_UNICODE);
        $stLog = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
        $stLog->execute([(int)($_SESSION['user_id'] ?? 0), 'INTEGRITY_RECHECK', 'documento', $documento_id, $det, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { /* ignore */ }

    $_SESSION['flash_message'] = [
        'type' => $ok ? 'sucesso' : 'erro',
        'text' => $ok ? 'Integridade válida: o arquivo corresponde ao verificador arquivado.' : 'Integridade inválida: o arquivo não corresponde ao verificador arquivado.'
    ];
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao reverificar: ' . $e->getMessage()];
}

header('Location: documentos_propriedades.php?id=' . $documento_id . '#integridade');
exit();
