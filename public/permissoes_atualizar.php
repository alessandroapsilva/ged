<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome']);
    $chave = trim($_POST['chave']);
    $descricao = trim($_POST['descricao']);

    if (empty($id) || empty($nome) || empty($chave)) {
        header('Location: permissoes_editar.php?id=' . $id . '&erro=campos_vazios');
        exit();
    }

    try {
        $sql = "UPDATE permissoes SET nome = ?, chave = ?, descricao = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $chave, $descricao, $id]);

        registrar_log($pdo, $_SESSION['user_id'], "Atualizou a permissão '{$nome}' (Chave: {$chave}).");

        header('Location: permissoes_listar.php?sucesso=permissao_atualizada');
        exit();

    } catch (PDOException $e) {
        // Erro mais comum: chave duplicada
        header('Location: permissoes_editar.php?id=' . $id . '&erro=db_error');
        exit();
    }
} else {
    header('Location: permissoes_listar.php');
    exit();
}
?>