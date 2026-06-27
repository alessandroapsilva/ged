<?php
require_once '../core/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$token = $_POST['token'] ?? null;
$nova_senha = $_POST['nova_senha'] ?? null;
$confirmar_senha = $_POST['confirmar_senha'] ?? null;

// 1. Validações básicas dos dados recebidos
if (!$token || !$nova_senha || !$confirmar_senha) {
    // Redireciona de volta com erro se algum campo estiver vazio
    header('Location: redefinir_senha?token=' . urlencode($token) . '&erro=vazio');
    exit();
}

if ($nova_senha !== $confirmar_senha) {
    // Redireciona de volta com erro se as senhas não conferem
    header('Location: redefinir_senha?token=' . urlencode($token) . '&erro=nao_conferem');
    exit();
}

// Requisito mínimo de senha (ex: 8 caracteres)
if (strlen($nova_senha) < 8) {
    header('Location: redefinir_senha?token=' . urlencode($token) . '&erro=curta');
    exit();
}

try {
    // 2. Revalida o token antes de alterar a senha
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM usuarios WHERE reset_token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || new DateTime() > new DateTime($usuario['reset_expires'])) {
        // Se o token for inválido ou expirado, manda para o login com um erro genérico
    header('Location: login?erro=token_invalido');
        exit();
    }

    // 3. Se tudo estiver certo, criptografa a nova senha
    $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

    // 4. Atualiza a senha e LIMPA o token de recuperação
    $update_stmt = $pdo->prepare(
        "UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
    );
    $update_stmt->execute([$hash_senha, $usuario['id']]);
    
    // 5. Redireciona para o login com mensagem de sucesso
    header('Location: login?sucesso=senha_redefinida');
    exit();

} catch (Exception $e) {
    error_log("Erro em redefinir_senha_process.php: " . $e->getMessage());
    die("Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.");
}