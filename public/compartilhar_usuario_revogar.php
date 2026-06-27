<?php
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
if (file_exists(PROJECT_ROOT . '/helpers/csrf_helper.php')) { require_once PROJECT_ROOT . '/helpers/csrf_helper.php'; }
require_once PROJECT_ROOT . '/helpers/share_user_helper.php';

require_auth();
if (function_exists('require_permission')) { @require_permission('document.share'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: documentos.php'); exit(); }
if (function_exists('csrf_validate') && !csrf_validate()) { http_response_code(403); exit('CSRF inválido'); }

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
$share_id = isset($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
if ($documento_id <= 0 || $share_id <= 0) { header('Location: documentos.php'); exit(); }

try {
    $ok = share_user_revoke($pdo, $share_id, $documento_id);
    if ($ok) {
        $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Compartilhamento interno revogado.'];
        // Auditoria (best-effort)
        try {
            $stLog = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
            $stLog->execute([
                (int)($_SESSION['user_id'] ?? 0),
                'INTERNAL_SHARE_REVOKE',
                'documento_compartilhamentos_usuario',
                $share_id,
                json_encode(['documento_id' => $documento_id, 'share_id' => $share_id], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Throwable $e) { /* ignore */ }
    } else {
        $_SESSION['flash_message'] = ['type' => 'aviso', 'text' => 'Nada a revogar ou já estava revogado.'];
    }
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao revogar: ' . $e->getMessage()];
}

header('Location: documentos_propriedades.php?id=' . $documento_id . '#shares-internos');
exit();

