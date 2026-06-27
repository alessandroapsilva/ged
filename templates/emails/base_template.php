<?php
// templates/emails/base_template.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $assunto_email ?></title>
    <style>
        /* Estilos gerais - Uso de CSS inline é crucial para compatibilidade */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dddddd; }
    .header { background-color: #2a3f54; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; color: #333333; line-height: 1.5; }
        .content p { margin: 0 0 15px; }
        .footer { background-color: #f4f4f4; color: #888888; text-align: center; padding: 20px; font-size: 12px; }
        .button { background-color: #28a745; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <table class="container" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="header">
                <h1>ENFAS GED</h1>
            </td>
        </tr>
        <tr>
            <td class="content">
                <?= $corpo_conteudo ?>
            </td>
        </tr>
        <tr>
            <td class="footer">
                <p>Este é um e-mail automático enviado pelo ENFAS GED. Por favor, não responda.</p>
                <p>&copy; <?= date('Y') ?> ENFAS GED. Todos os direitos reservados.</p>
            </td>
        </tr>
    </table>
</body>
</html>