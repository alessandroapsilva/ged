<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/share_user_helper.php';

require_auth();
require_permission('document.share');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: documentos.php'); exit(); }
if (!csrf_validate()) { http_response_code(403); exit('CSRF inválido'); }

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
$share_id = isset($_POST['share_id']) ? (int)$_POST['share_id'] : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? (int)$_POST['value'] : 0;

if ($documento_id <= 0 || $share_id <= 0 || $field === '') { header('Location: documentos.php'); exit(); }

try {
    // Captura valor anterior para auditoria
    $prev = null;
    $stPrev = $pdo->prepare("SELECT view_only, can_download FROM documento_compartilhamentos_usuario WHERE id = ? AND documento_id = ?");
    $stPrev->execute([$share_id, $documento_id]);
    $prev = $stPrev->fetch(PDO::FETCH_ASSOC) ?: null;

    $ok = share_user_toggle_field($pdo, $share_id, $documento_id, $field, $value);
    if ($ok) {
        $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Permissão atualizada.'];
        // Auditoria em audit_logs (best-effort)
        try {
            $details = json_encode([
                'documento_id' => $documento_id,
                'share_id' => $share_id,
                'field' => $field,
                'old' => $prev[$field] ?? null,
                'new' => (int)$value
            ], JSON_UNESCAPED_UNICODE);
            $stLog = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
            $stLog->execute([
                (int)($_SESSION['user_id'] ?? 0),
                'INTERNAL_SHARE_TOGGLE',
                'documento_compartilhamentos_usuario',
                $share_id,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Throwable $e) { /* ignore */ }
    } else {
        $_SESSION['flash_message'] = ['type' => 'aviso', 'text' => 'Nada alterado (talvez já estivesse com esse valor ou foi revogado).'];
    }
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao atualizar: ' . $e->getMessage()];
}

header('Location: documentos_propriedades.php?id=' . $documento_id . '#shares-internos');
exit();
