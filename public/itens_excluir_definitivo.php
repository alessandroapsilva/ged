<?php
// public/itens_excluir_definitivo.php (VERSÃO CORRIGIDA)

require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// [CORREÇÃO APLICADA AQUI] Usamos trim() para limpar o parâmetro 'tipo'.
$tipo = trim($_GET['tipo'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (($tipo !== 'documento' && $tipo !== 'pasta') || $id <= 0) {
    header('Location: lixeira.php?erro=invalido');
    exit();
}

$pdo->beginTransaction();
try {
    if ($tipo === 'documento') {
        $sql_files = "SELECT caminho_arquivo FROM documento_versoes WHERE documento_id = ?";
        $stmt_files = $pdo->prepare($sql_files);
        $stmt_files->execute([$id]);
        $versoes = $stmt_files->fetchAll(PDO::FETCH_ASSOC);
        
        $titulo = $pdo->query("SELECT titulo_original FROM documentos WHERE id = $id")->fetchColumn();
        if (!$titulo) $titulo = "ID ".$id;

        $sql_delete = "DELETE FROM documentos WHERE id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$id]);

        foreach ($versoes as $versao) {
            if (!empty($versao['caminho_arquivo'])) {
                $caminho_relativo_limpo = ltrim($versao['caminho_arquivo'], './');
                $caminho_absoluto = PROJECT_ROOT . '/' . $caminho_relativo_limpo;
                if (file_exists($caminho_absoluto)) {
                    unlink($caminho_absoluto);
                }
            }
        }
        
        registrar_log($pdo, $_SESSION['user_id'], "Excluiu permanentemente o documento '{$titulo}' (ID: {$id}).");

    } elseif ($tipo === 'pasta') {
        $nome = $pdo->query("SELECT nome FROM pastas WHERE id = $id")->fetchColumn();
        if (!$nome) $nome = "ID ".$id;

        $sql_delete = "DELETE FROM pastas WHERE id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$id]);

        registrar_log($pdo, $_SESSION['user_id'], "Excluiu permanentemente a pasta '{$nome}' (ID: {$id}).");
    }

    $pdo->commit();
    header('Location: lixeira.php?sucesso=excluido_perm');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: lixeira.php?erro=excluir_perm');
    exit();
}
?>