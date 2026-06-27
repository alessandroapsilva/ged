<?php
// public/2fa_db_migrate.php - migrações simples para 2FA e certificados por usuário
require_once '../core/init.php';
if (!is_object($pdo)) { die('PDO indisponível'); }

header('Content-Type: text/plain; charset=utf-8');

try {
    // Detecta tabela 'usuarios'
    $hasUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuarios'")->fetchColumn() > 0;
    if (!$hasUsuarios) {
        echo "Tabela 'usuarios' não encontrada. Nada a fazer.\n";
        exit;
    }

    // Adiciona twofa_enabled e twofa_secret se ausentes
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios'")->fetchAll(PDO::FETCH_COLUMN);
    $cols = array_map('strtolower', $cols);

    if (!in_array('twofa_enabled', $cols)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN twofa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER senha");
        echo "Adicionada coluna usuarios.twofa_enabled\n";
    } else {
        echo "Coluna usuarios.twofa_enabled já existe\n";
    }
    if (!in_array('twofa_secret', $cols)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN twofa_secret VARCHAR(255) NULL DEFAULT NULL AFTER twofa_enabled");
        echo "Adicionada coluna usuarios.twofa_secret\n";
    } else {
        echo "Coluna usuarios.twofa_secret já existe\n";
    }

    // Cria tabela usuario_certificados se não existir
    $hasTable = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuario_certificados'")->fetchColumn() > 0;
    if (!$hasTable) {
        $sql = "CREATE TABLE usuario_certificados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            caminho_pfx VARCHAR(255) NOT NULL,
            subject_cn VARCHAR(255) NULL,
            issuer_cn VARCHAR(255) NULL,
            valid_from DATETIME NULL,
            valid_to DATETIME NULL,
            thumbprint VARCHAR(128) NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (usuario_id),
            INDEX (ativo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
        echo "Tabela usuario_certificados criada\n";
    } else {
        echo "Tabela usuario_certificados já existe\n";
    }

    echo "OK\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erro: ".$e->getMessage()."\n";
}
