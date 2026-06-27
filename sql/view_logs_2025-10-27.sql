-- Registro de visualizações internas

CREATE TABLE IF NOT EXISTS document_views (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documento_id INT NOT NULL,
  user_id INT NOT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_doc_user (documento_id, user_id, created_at),
  CONSTRAINT fk_views_doc FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_views_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
