<?php
// public/verificar_entrada.php (VERSÃO FUNCIONAL)

// Não precisa de session_start() aqui, o init.php já cuida disso.
require_once '../core/init.php';

// session_start() precisa ser chamado ANTES de qualquer acesso a $_SESSION
// Como o init.php já faz isso, podemos processar o login.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = trim($_POST['email']);
$senha_submetida = trim($_POST['senha']);

if (empty($email) || empty($senha_submetida)) {
    $_SESSION['login_error'] = 'Email e senha são obrigatórios.';
    header('Location: login.php');
    exit();
}

// 1. Encontra o usuário pelo email
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

// 2. Verifica se o usuário existe E se a senha criptografada bate
if ($usuario && password_verify($senha_submetida, $usuario['senha'])) {
    // Sucesso!
    session_regenerate_id(true);
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_name'] = $usuario['nome'];
    unset($_SESSION['login_error']);
    
    // Redireciona para a página principal
    header('Location: index.php');
    exit();

} else {
    // Falha!
    $_SESSION['login_error'] = 'Email ou senha inválidos.';
    header('Location: login.php');
    exit();
}
?>