<?php
$dsn = 'mysql:host=localhost;dbname=ged;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '');
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `ingest_arquivos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_original` VARCHAR(255) NOT NULL,
  `caminho_relativo` VARCHAR(255) NOT NULL,
  `origem` VARCHAR(50) DEFAULT 'LOCAL',
  `tamanho_bytes` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('pendente','corrigir','corrigido','admitido','erro') NOT NULL DEFAULT 'pendente',
  `falha_motivo` VARCHAR(255) DEFAULT NULL,
  `documento_id` INT UNSIGNED DEFAULT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admitido_em` DATETIME DEFAULT NULL,
  `corrigido_em` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_idx` (`status`),
  KEY `admitido_em_idx` (`admitido_em`),
  KEY `criado_em_idx` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
try {
    $pdo->exec($sql);
    echo "OK CREATED ingest_arquivos\n";
} catch (Throwable $e) {
    echo "ERR: ".$e->getMessage()."\n";
}
