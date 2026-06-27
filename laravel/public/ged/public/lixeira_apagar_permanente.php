<?php
// public/lixeira_apagar_permanente.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$id = (int)($_GET['id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';

if ($id > 0) {
    if ($tipo === 'd') { // Se for um documento
        // Primeiro, pega o caminho do arquivo para deletar o físico
        $stmt_path = $pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id = ?");
        $stmt_path->execute([$id]);
        $caminho = $stmt_path->fetchColumn();
        if ($caminho && file_exists(PROJECT_ROOT . '/public/' . $caminho)) {
            unlink(PROJECT_ROOT . '/public/' . $caminho);
        }

        // [CORREÇÃO] Apaga o registro da tabela de índice de busca primeiro
        $stmt_delete_index = $pdo->prepare("DELETE FROM documentos_indice WHERE documento_id = ?");
        $stmt_delete_index->execute([$id]);

        // Apaga o registro do banco
        $stmt_delete = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
        $stmt_delete->execute([$id]);
    } elseif ($tipo === 'p') { // Se for uma pasta
        // Apenas apaga o registro do banco
        $stmt_delete = $pdo->prepare("DELETE FROM pastas WHERE id = ?");
        $stmt_delete->execute([$id]);
    }
}

header('Location: lixeira.php');
exit();
?>