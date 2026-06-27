-- Compartilhamentos internos por usuário (2025-10-27)

CREATE TABLE IF NOT EXISTS documento_compartilhamentos_usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documento_id INT NOT NULL,
  user_id INT NOT NULL,
  granted_by INT NOT NULL,
  can_download TINYINT(1) NOT NULL DEFAULT 1,
  view_only TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at DATETIME NULL,
  KEY idx_doc (documento_id),
  KEY idx_user (user_id),
  UNIQUE KEY uq_doc_user_active (documento_id, user_id, revoked_at),
  CONSTRAINT fk_share_doc FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_share_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_share_granted_by FOREIGN KEY (granted_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- View: ativos apenas (não expirado e não revogado)
-- SELECT * FROM documento_compartilhamentos_usuario WHERE (revoked_at IS NULL) AND (expires_at IS NULL OR expires_at > NOW());
