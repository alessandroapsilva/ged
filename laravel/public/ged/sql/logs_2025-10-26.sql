-- Tabela de logs genéricos (compatível com helpers/log_helper.php)
CREATE TABLE IF NOT EXISTS logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  acao VARCHAR(255) NOT NULL,
  categoria VARCHAR(100) DEFAULT 'Atividade',
  documento_id BIGINT UNSIGNED NULL,
  pasta_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (usuario_id),
  INDEX idx_cat (categoria),
  INDEX idx_doc (documento_id),
  INDEX idx_pasta (pasta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
