<?php
require_once 'core/init.php';

try {
    $pdo = getDBConnection();

    // Verificar se a coluna já existe para evitar erros
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'google2fa_secret'");
    $column_exists = $stmt->rowCount() > 0;

    if (!$column_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN google2fa_secret VARCHAR(255) NULL DEFAULT NULL AFTER password_hash");
        echo "Coluna 'google2fa_secret' adicionada à tabela 'users' com sucesso!";
    } else {
        echo "A coluna 'google2fa_secret' já existe na tabela 'users'.";
    }

    echo "<br>Verificação do banco de dados concluída.";

} catch (Exception $e) {
    die("Erro ao atualizar o banco de dados: " . $e->getMessage());
}
?>