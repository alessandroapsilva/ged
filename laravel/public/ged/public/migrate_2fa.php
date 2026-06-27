<?php
require_once '../core/init.php';

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN google_authenticator_secret VARCHAR(255) NULL DEFAULT NULL AFTER senha");
    echo "Tabela 'usuarios' atualizada com sucesso! A coluna 'google_authenticator_secret' foi adicionada.<br>";
    echo "Por favor, delete este arquivo (migrate_2fa.php) por segurança.";
} catch (PDOException $e) {
    // Verifica se o erro é de coluna já existente
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "A coluna 'google_authenticator_secret' já existe na tabela 'usuarios'. Nenhuma ação foi necessária.<br>";
        echo "Por favor, delete este arquivo (migrate_2fa.php) por segurança.";
    } else {
        die("Erro ao atualizar a tabela: " . $e->getMessage());
    }
}
?>