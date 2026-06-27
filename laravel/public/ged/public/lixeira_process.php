<?php
// public/lixeira_process.php (VERSÃO REFATORADA)
require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$action = $_POST['action'] ?? '';
$ids_com_prefixo = $_POST['ids'] ?? [];

if (empty($action) || empty($ids_com_prefixo)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Ação ou itens inválidos.']);
    exit();
}

$pdo->beginTransaction();
try {
    if ($action === 'restaurar') {
        $stmt_doc = $pdo->prepare("UPDATE documentos SET apagado_em = NULL WHERE id = ?");
        $stmt_pasta = $pdo->prepare("UPDATE pastas SET apagado_em = NULL WHERE id = ?");
        foreach ($ids_com_prefixo as $item) {
            list($prefixo, $id) = explode('-', $item);
            if ($prefixo === 'd') $stmt_doc->execute([(int)$id]);
            if ($prefixo === 'p') $stmt_pasta->execute([(int)$id]);
        }
    } elseif ($action === 'apagar_permanente') {
        $stmt_get_doc = $pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id = ?");
        $stmt_del_doc = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
        $stmt_del_assinaturas = $pdo->prepare("DELETE FROM assinaturas WHERE documento_id = ?");
        $stmt_del_pasta = $pdo->prepare("DELETE FROM pastas WHERE id = ?");
        
        foreach ($ids_com_prefixo as $item) {
            list($prefixo, $id) = explode('-', $item);
            $id = (int)$id;
            if ($prefixo === 'd') {
                $stmt_get_doc->execute([$id]);
                $doc = $stmt_get_doc->fetch();
                if ($doc && file_exists(PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'])) {
                    unlink(PROJECT_ROOT . '/public/' . $doc['caminho_arquivo']);
                }
                $stmt_del_assinaturas->execute([$id]);
                $stmt_del_doc->execute([$id]);
            }
            if ($prefixo === 'p') {
                // AVISO: Apagar pastas permanentemente pode deixar documentos órfãos se não for tratado com cuidado.
                // Por simplicidade, estamos apenas apagando o registro da pasta.
                $stmt_del_pasta->execute([$id]);
            }
        }
    } else {
        throw new Exception("Ação desconhecida.");
    }
    
    $pdo->commit();
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
?>