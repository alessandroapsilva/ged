-- Tabela para assinaturas digitais
CREATE TABLE IF NOT EXISTS documentos_assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_assinatura DATETIME NOT NULL,
    tipo_assinatura ENUM('ICP-Brasil', 'Simples', 'Eletronica') NOT NULL,
    detalhes JSON,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_documento (documento_id),
    INDEX idx_usuario (usuario_id)
);

-- Adicionando campos na tabela documentos
ALTER TABLE documentos 
ADD COLUMN assinado BOOLEAN DEFAULT 0,
ADD COLUMN data_assinatura DATETIME NULL,
ADD COLUMN assinado_por INT NULL,
ADD FOREIGN KEY (assinado_por) REFERENCES usuarios(id);