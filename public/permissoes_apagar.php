<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

// Valida o ID recebido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: permissoes_listar.php');
    exit();
}
$id = (int)$_GET['id'];

try {
    // Passo 1: Verifica se a permissão está em uso por alguma função
    $sql_check = "SELECT COUNT(*) FROM funcao_permissao WHERE permissao_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$id]);
    $funcao_count = $stmt_check->fetchColumn();

    if ($funcao_count > 0) {
        // Se estiver em uso, impede a exclusão e redireciona com erro
        header('Location: permissoes_listar.php?erro=permissao_em_uso&count=' . $funcao_count);
        exit();
    }

    // Busca o nome para o log antes de apagar
    $sql_select = "SELECT nome FROM permissoes WHERE id = ?";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([$id]);
    $permissao = $stmt_select->fetch(PDO::FETCH_ASSOC);
    $nome_permissao_apagada = $permissao ? $permissao['nome'] : 'ID ' . $id;

    // Passo 2: Se não estiver em uso, pode apagar
    $sql_delete = "DELETE FROM permissoes WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id]);

    registrar_log($pdo, $_SESSION['user_id'], "Apagou a permissão '{$nome_permissao_apagada}'.");

    header('Location: permissoes_listar.php?sucesso=permissao_apagada');
    exit();

} catch (PDOException $e) {
    header('Location: permissoes_listar.php?erro=db_error');
    exit();
}
?>