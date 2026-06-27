<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $funcao_id = (int)$_POST['funcao_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($id) || empty($nome) || empty($email) || empty($funcao_id)) {
        header('Location: usuarios_editar.php?id=' . $id . '&erro=campos_vazios');
        exit();
    }

    try {
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, funcao_id = ?, ativo = ? WHERE id = ?";
            $params = [$nome, $email, $senha_hash, $funcao_id, $ativo, $id];
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, funcao_id = ?, ativo = ? WHERE id = ?";
            $params = [$nome, $email, $funcao_id, $ativo, $id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        registrar_log($pdo, $_SESSION['user_id'], "Atualizou o usuário '{$nome}' (ID: {$id}).");

        header('Location: usuarios_listar.php?sucesso=usuario_atualizado');
        exit();
    } catch (PDOException $e) {
        header('Location: usuarios_editar.php?id=' . $id . '&erro=db_error');
        exit();
    }
}
?>