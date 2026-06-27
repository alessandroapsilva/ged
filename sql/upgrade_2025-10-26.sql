-- Upgrade: security and API hardening (2025-10-26)

-- Tabela de logs de auditoria (ações de usuários)
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(64) NOT NULL,
  entity_type VARCHAR(64) NULL,
  entity_id BIGINT UNSIGNED NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_created_at (user_id, created_at),
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabelas para chaves e tokens de API
CREATE TABLE IF NOT EXISTS api_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  nome VARCHAR(100) NULL,
  chave VARCHAR(255) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  expira_em DATETIME NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_ativo (ativo),
  INDEX idx_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  expira_em DATETIME NULL,
  escopos VARCHAR(255) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate limiting / métricas de uso da API
CREATE TABLE IF NOT EXISTS api_access_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identificador VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL,
  INDEX idx_ident_time (identificador, criado_em),
  INDEX idx_time (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para busca full-text (se não existirem)
CREATE TABLE IF NOT EXISTS documentos_indice (
  documento_id BIGINT UNSIGNED PRIMARY KEY,
  texto_completo LONGTEXT NULL,
  atualizado_em DATETIME NULL,
  atualizado_por BIGINT UNSIGNED NULL,
  FULLTEXT INDEX ft_texto (texto_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OCR por página (estrutura básica)
CREATE TABLE IF NOT EXISTS documentos_ocr (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT UNSIGNED NOT NULL,
  pagina INT NOT NULL,
  texto LONGTEXT NULL,
  processado_em DATETIME NOT NULL,
  processado_por BIGINT UNSIGNED NULL,
  INDEX idx_doc_pag (documento_id, pagina)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed opcional: criar uma chave de API de exemplo para o usuário 1
INSERT INTO api_keys (user_id, nome, chave, ativo)
SELECT 1, 'Chave padrão', 'edoc-api-key-2024', 1
WHERE NOT EXISTS (SELECT 1 FROM api_keys WHERE chave = 'edoc-api-key-2024');
