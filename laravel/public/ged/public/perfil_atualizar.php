<?php
// public/perfil_atualizar.php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) { exit('Acesso negado.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $user_id = $_SESSION['user_id'];

    if (empty($nome)) {
    header('Location: perfil_editar?erro=nome_vazio');
        exit();
    }

    try {
        $sql = "UPDATE usuarios SET nome = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $user_id]);

        $_SESSION['user_name'] = $nome;

    header('Location: perfil?sucesso=atualizado');
        exit();
    } catch (PDOException $e) {
        die('Erro ao atualizar perfil: ' . $e->getMessage());
    }
}
?>