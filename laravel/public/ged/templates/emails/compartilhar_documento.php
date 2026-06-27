<!DOCTYPE html>
<html>
<head><title><?= htmlspecialchars($assunto_email) ?></title></head>
<body style="font-family: sans-serif; color: #333;">
    <div style="padding: 20px; border: 1px solid #ddd; max-width: 600px; margin: auto;">
    <h3>ENFAS GED — Gestão Eletrônica de Documentos</h3>
        <hr>
        <p>Olá!</p>
        <p><?= htmlspecialchars($nome_remetente) ?> acabou de compartilhar o documento <strong>"<?= htmlspecialchars($nome_documento) ?>"</strong> com você.</p>
        <p>Para sua conveniência, o documento está em anexo.</p>
        <br>
        <p><small><strong>NOTA JURÍDICA:</strong> você é legalmente responsável pelo sigilo desta mensagem. NÃO COMPARTILHE-A. Se recebeu por engano, apague-a imediatamente.</small></p>
        <br>
    <p>Atenciosamente,</p>
    <p><strong>ENFAS GED</strong><br>Gestão Eletrônica de Documentos</p>
    </div>
</body>
</html>