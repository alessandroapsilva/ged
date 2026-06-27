<?php
// public/itens_apagar_lote.php
// Script para mover múltiplos itens (documentos e pastas) para a lixeira.

require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acesso negado.');
}

$ids = $_POST['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    exit('Dados inválidos.');
}

$doc_ids = [];
$pasta_ids = [];

foreach ($ids as $id_com_prefixo) {
    if (preg_match('/^(p|d)-(\d+)$/', $id_com_prefixo, $matches)) {
        $prefixo = $matches[1];
        $id = (int)$matches[2];
        if ($prefixo === 'd') {
            $doc_ids[] = $id;
        } elseif ($prefixo === 'p') {
            $pasta_ids[] = $id;
        }
    }
}

if (empty($doc_ids) && empty($pasta_ids)) {
    http_response_code(400);
    exit('Nenhum item válido para apagar.');
}

try {
    $pdo->beginTransaction();

    if (!empty($doc_ids)) {
        $in_placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
        $stmt = $pdo->prepare("UPDATE documentos SET apagado_em = NOW() WHERE id IN ($in_placeholders)");
        $stmt->execute($doc_ids);
    }

    if (!empty($pasta_ids)) {
        $in_placeholders = implode(',', array_fill(0, count($pasta_ids), '?'));
        $stmt = $pdo->prepare("UPDATE pastas SET apagado_em = NOW() WHERE id IN ($in_placeholders)");
        $stmt->execute($pasta_ids);
    }

    $pdo->commit();
    echo 'sucesso';

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    // Em um ambiente de produção, é melhor logar a mensagem de erro em vez de exibi-la.
    error_log('Erro em itens_apagar_lote.php: ' . $e->getMessage());
    echo 'Erro ao mover itens para a lixeira.';
}
?>
