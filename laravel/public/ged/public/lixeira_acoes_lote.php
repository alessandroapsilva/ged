<?php
// public/lixeira_acoes_lote.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { exit('Acesso negado.'); }

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];

if (empty($action) || empty($ids)) { exit('Dados inválidos.'); }

$doc_ids = [];
$pasta_ids = [];
foreach ($ids as $id_com_prefixo) {
    list($prefixo, $id) = explode('-', $id_com_prefixo);
    if ($prefixo === 'd') $doc_ids[] = (int)$id;
    elseif ($prefixo === 'p') $pasta_ids[] = (int)$id;
}

$pdo->beginTransaction();
try {
    if ($action === 'restaurar') {
        if (!empty($doc_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
            $stmt = $pdo->prepare("UPDATE documentos SET apagado_em = NULL WHERE id IN ($in_placeholders)");
            $stmt->execute($doc_ids);
        }
        if (!empty($pasta_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($pasta_ids), '?'));
            $stmt = $pdo->prepare("UPDATE pastas SET apagado_em = NULL WHERE id IN ($in_placeholders)");
            $stmt->execute($pasta_ids);
        }
    } 
    elseif ($action === 'apagar_permanente') {
        if (!empty($doc_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
            $stmt_paths = $pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id IN ($in_placeholders)");
            $stmt_paths->execute($doc_ids);
            foreach($stmt_paths->fetchAll(PDO::FETCH_COLUMN) as $caminho) {
                if ($caminho && file_exists(PROJECT_ROOT . '/public/' . $caminho)) { unlink(PROJECT_ROOT . '/public/' . $caminho); }
            }

            // [CORREÇÃO] Apaga os registros da tabela de índice de busca primeiro
            $stmt_delete_index = $pdo->prepare("DELETE FROM documentos_indice WHERE documento_id IN ($in_placeholders)");
            $stmt_delete_index->execute($doc_ids);

            $stmt_delete = $pdo->prepare("DELETE FROM documentos WHERE id IN ($in_placeholders)");
            $stmt_delete->execute($doc_ids);
        }
        if (!empty($pasta_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($pasta_ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM pastas WHERE id IN ($in_placeholders)");
            $stmt->execute($pasta_ids);
        }
    }
    $pdo->commit();
    echo 'sucesso';
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo 'Erro: ' . $e->getMessage();
}
?>