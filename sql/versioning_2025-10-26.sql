-- Versionamento de documentos (2025-10-26)

CREATE TABLE IF NOT EXISTS documento_versoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT UNSIGNED NOT NULL,
  versao INT UNSIGNED NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT NULL,
  caminho_arquivo VARCHAR(500) NULL,
  hash_arquivo VARCHAR(128) NULL,
  quantidade_paginas INT NULL,
  criado_em DATETIME NOT NULL,
  criado_por BIGINT UNSIGNED NULL,
  motivo VARCHAR(255) NULL,
  UNIQUE KEY uniq_doc_versao (documento_id, versao),
  INDEX idx_doc (documento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
