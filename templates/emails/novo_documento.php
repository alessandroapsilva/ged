<?php
// templates/emails/novo_documento.php

// Inicia o "buffer" para capturar o HTML
ob_start();
?>

<p>Olá,</p>
<p>Um novo documento foi cadastrado no sistema por <strong><?= htmlspecialchars($nome_usuario) ?></strong>.</p>
<ul>
    <li><strong>Título do Documento:</strong> <?= htmlspecialchars($titulo_documento) ?></li>
    <li><strong>Data de Envio:</strong> <?= date('d/m/Y H:i') ?></li>
</ul>
<p>Você pode acessá-lo no sistema.</p>
<br>
<p style="text-align:center;">
    <a href="<?= BASE_URL ?>/documentos.php" class="button">Acessar o Sistema</a>
</p>
<br>
<p>Atenciosamente,<br>Equipe ENFAS GED</p>

<?php
// Captura o HTML e coloca na variável que o template base espera
$corpo_conteudo = ob_get_clean();

// Inclui o "molde" principal, que vai usar a variável $corpo_conteudo
require 'base_template.php';
?>