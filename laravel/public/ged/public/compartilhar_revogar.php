<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/share_helper.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('usuario_tem_permissao') && !usuario_tem_permissao('document.share')) {
    http_response_code(403);
    exit('Acesso negado.');
}
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
$link_id = isset($_POST['link_id']) ? (int)$_POST['link_id'] : 0;
if ($documento_id <= 0 || $link_id <= 0) {
    header('Location: documentos_propriedades.php?id=' . $documento_id);
    exit();
}

try {
    // Garantir que o link pertence ao documento informado
    $st = $pdo->prepare('SELECT documento_id FROM documento_links WHERE id = ?');
    $st->execute([$link_id]);
    $docId = (int)($st->fetchColumn() ?: 0);
    if ($docId !== $documento_id) { throw new Exception('Link não pertence ao documento.'); }

    // Revoga (remoção simples)
    $del = $pdo->prepare('DELETE FROM documento_links WHERE id = ?');
    $del->execute([$link_id]);

    if (function_exists('registrar_log')) {
        registrar_log($pdo, (int)$_SESSION['user_id'], 'Revogar link', 'documento_links', $link_id);
    }
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Link revogado com sucesso.'];
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao revogar link: ' . $e->getMessage()];
}

header('Location: documentos_propriedades.php?id=' . $documento_id);
exit();
