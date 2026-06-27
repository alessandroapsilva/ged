<?php
// public/perfil_alterar_senha.php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) { exit('Acesso negado.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $user_id = $_SESSION['user_id'];

    if ($nova_senha !== $confirmar_senha) {
    header('Location: perfil_editar?erro=senhas_nao_conferem');
        exit();
    }
    if (strlen($nova_senha) < 6) {
    header('Location: perfil_editar?erro=senha_curta');
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha_atual, $user['senha'])) {
            $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            $update_stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $update_stmt->execute([$novo_hash, $user_id]);

            header('Location: perfil?sucesso=senha_alterada');
            exit();
        } else {
            header('Location: perfil_editar?erro=senha_atual_invalida');
            exit();
        }
    } catch (PDOException $e) {
        die("Erro ao alterar a senha: " . $e->getMessage());
    }
}
?>