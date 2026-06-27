<?php
// A senha que queremos usar para o login
$senha_texto_puro = 'admin';

// Gera o hash seguro usando as configurações do SEU servidor
$hash = password_hash($senha_texto_puro, PASSWORD_DEFAULT);

// Mostra o hash gerado na tela de uma forma fácil de copiar
echo "<h3>Seu Novo Hash de Senha</h3>";
echo "<p>Copie esta linha inteira e cole no campo 'senha' do seu usuário no banco de dados:</p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hash) . "</textarea>";
?>