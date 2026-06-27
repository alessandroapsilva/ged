<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome = trim($_POST['nome']);
    $chave = trim($_POST['chave']);
    $descricao = trim($_POST['descricao']);

    if (empty($nome) || empty($chave)) {
        header('Location: permissoes_adicionar.php?erro=campos_vazios');
        exit();
    }

    try {
        $sql = "INSERT INTO permissoes (nome, chave, descricao) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $chave, $descricao]);

        $nova_permissao_id = $pdo->lastInsertId();
        registrar_log($pdo, $_SESSION['user_id'], "Criou a permissão '{$nome}' (Chave: {$chave}).");

        header('Location: permissoes_listar.php?sucesso=permissao_criada');
        exit();

    } catch (PDOException $e) {
        // O erro mais comum aqui é tentar criar uma 'chave' que já existe
        header('Location: permissoes_adicionar.php?erro=db_error');
        exit();
    }
} else {
    header('Location: permissoes_listar.php');
    exit();
}
?>