-- Criação das tabelas do Ingest (fila de captura e admissão de documentos)

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

CREATE TABLE IF NOT EXISTS `ingest_eventos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingest_arquivo_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `mensagem` VARCHAR(255) DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_arquivo_idx` (`ingest_arquivo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vínculo após criação para evitar erro de ordem de objetos
ALTER TABLE `ingest_eventos`
  ADD CONSTRAINT `fk_ingest_eventos_arquivo`
  FOREIGN KEY (`ingest_arquivo_id`) REFERENCES `ingest_arquivos`(`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;
