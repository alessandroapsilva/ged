<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: usuarios_listar.php');
    exit();
}
$id = (int)$_GET['id'];
if ($id === $_SESSION['user_id']) {
    header('Location: usuarios_listar.php?erro=auto_delete');
    exit();
}
try {
    $sql_select = "SELECT nome FROM usuarios WHERE id = ?";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([$id]);
    $usuario = $stmt_select->fetch(PDO::FETCH_ASSOC);
    $nome_usuario_apagado = $usuario ? $usuario['nome'] : 'ID ' . $id;

    $sql_delete = "DELETE FROM usuarios WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id]);

    registrar_log($pdo, $_SESSION['user_id'], "Apagou o usuário '{$nome_usuario_apagado}'.");

    header('Location: usuarios_listar.php?sucesso=usuario_apagado');
    exit();
} catch (PDOException $e) {
    header('Location: usuarios_listar.php?erro=db_error');
    exit();
}
?>