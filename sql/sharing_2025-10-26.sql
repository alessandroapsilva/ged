-- Compartilhamento seguro de documentos (2025-10-26)

CREATE TABLE IF NOT EXISTS documento_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  expi#res_at DATETIME NULL,
  max_downloads INT NULL,
  downloads INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_doc (documento_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
