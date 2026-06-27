<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: esqueci_senha.php');
    exit();
}

$email = $_POST['email'] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Se o e-mail for inválido, redireciona de volta.
    header('Location: esqueci_senha.php?sucesso=enviado'); // Mostra a mesma mensagem para não revelar quais e-mails existem
    exit();
}

try {
    // 1. Verifica se o usuário existe
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Se o usuário existir, prepara o token e o e-mail
    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $expira_em = date('Y-m-d H:i:s', time() + 3600); // Válido por 1 hora

        // 3. Salva o token e a data de expiração no banco de dados
        $update_stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update_stmt->execute([$token, $expira_em, $usuario['id']]);

        // 4. Prepara e envia o e-mail com a sua função
        // Detecta o domínio automaticamente
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base_path = dirname($_SERVER['SCRIPT_NAME']);
        $base_url = $protocol . '://' . $host . $base_path;
        
        $link = $base_url . "/redefinir_senha.php?token=" . $token;
        
        // Dados para o template (usa as variáveis corretas do template)
        $dados_email = [
            'usuario' => [
                'nome' => $usuario['nome']
            ],
            'link' => $link,
            'expiracao' => '1 hora'
        ];

        require_once PROJECT_ROOT . '/core/email.php';
        email_send_template($pdo, $email, 'recuperar_senha', $dados_email);
    }
    
    // 5. Redireciona para a mesma página com mensagem de sucesso, independentemente de o e-mail existir ou não.
    // Isso é uma prática de segurança para não informar a um invasor quais e-mails estão cadastrados.
    header('Location: esqueci_senha.php?sucesso=enviado');
    exit();

} catch (Exception $e) {
    error_log("Erro em esqueci_senha_process.php: " . $e->getMessage());
    // Em caso de erro grave, mostra uma mensagem genérica
    die("Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.");
}