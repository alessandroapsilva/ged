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
        <h2>Requisição de Assinatura</h2>
        <p>Olá,</p>
        <p>Você foi solicitado(a) para assinar o documento "<strong><?= htmlspecialchars($nome_documento) ?></strong>".</p>
        <p>Por favor, acesse o link abaixo para visualizar o documento e realizar a assinatura digital:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($link_assinatura) ?>" class="button">Clique aqui para Assinar</a>
        </p>
        <p>Se o botão não funcionar, copie e cole o seguinte endereço no seu navegador:</p>
        <p><?= htmlspecialchars($link_assinatura) ?></p>
        <br>
    <p>Obrigado,<br>ENFAS GED</p>
    </div>
</body>
</html>