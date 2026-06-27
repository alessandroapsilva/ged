<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($assunto_email) ?></title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 600px; margin: 20px auto; }
        .button { background-color: #007bff; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperação de Senha</h2>
        <p>Olá <?= htmlspecialchars($nome_usuario) ?>,</p>
    <p>Recebemos uma solicitação para redefinir sua senha no ENFAS GED. Se não foi você, por favor, ignore este e-mail.</p>
        <p>Para criar uma nova senha, clique no botão abaixo. Este link é válido por 1 hora.</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($link_redefinicao) ?>" class="button">Redefinir Minha Senha</a>
        </p>
        <p>Se o botão não funcionar, copie e cole o seguinte endereço no seu navegador:</p>
        <p><?= htmlspecialchars($link_redefinicao) ?></p>
        <br>
    <p>Obrigado,<br>ENFAS GED</p>
    </div>
</body>
</html>