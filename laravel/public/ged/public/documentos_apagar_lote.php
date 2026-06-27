<?php
// public/documentos_apagar_lote.php
require_once '../core/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$ids = $_POST['ids'] ?? [];
if (empty($ids)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum item selecionado.']);
    exit();
}

$doc_ids = [];
$pasta_ids = [];

foreach ($ids as $id_com_prefixo) {
    list($prefixo, $id) = explode('-', $id_com_prefixo);
    if ($prefixo === 'd') {
        $doc_ids[] = (int)$id;
    } elseif ($prefixo === 'p') {
        $pasta_ids[] = (int)$id;
    }
}

$pdo->beginTransaction();
try {
    if (!empty($doc_ids)) {
        $placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
        $stmt_doc = $pdo->prepare("UPDATE documentos SET apagado_em = NOW() WHERE id IN ($placeholders)");
        $stmt_doc->execute($doc_ids);
    }
    if (!empty($pasta_ids)) {
        $placeholders = implode(',', array_fill(0, count($pasta_ids), '?'));
        $stmt_pasta = $pdo->prepare("UPDATE pastas SET apagado_em = NOW() WHERE id IN ($placeholders)");
        $stmt_pasta->execute($pasta_ids);
    }
    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor.']);
}