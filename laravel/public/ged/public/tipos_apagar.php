<?php
// Proteção e conexão
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once '../core/init.php';
require_once '../db_config.php';

// Pega o ID da URL e verifica se é válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tipos_listar.php');
    exit();
}
$id = $_GET['id'];

try {
    // Prepara e executa o comando SQL DELETE
    $sql = "DELETE FROM tipos_documento WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    // Redireciona de volta para a lista com uma mensagem de sucesso
    header("Location: tipos_listar.php?sucesso=apagado");
    exit();

} catch (PDOException $e) {
    // Se der um erro (ex: o tipo está em uso em outra tabela no futuro),
    // podemos redirecionar com uma mensagem de erro.
    header("Location: tipos_listar.php?erro=apagar");
    exit();
}
?>