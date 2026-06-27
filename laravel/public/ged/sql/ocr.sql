-- Tabela para armazenar o texto extraído por OCR
CREATE TABLE IF NOT EXISTS documentos_ocr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    pagina INT NOT NULL,
    texto LONGTEXT,
    processado_em DATETIME NOT NULL,
    processado_por INT NOT NULL,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (processado_por) REFERENCES usuarios(id),
    INDEX idx_documento_pagina (documento_id, pagina)
);

-- Tabela para índice de busca full-text
CREATE TABLE IF NOT EXISTS documentos_indice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    texto_completo LONGTEXT,
    atualizado_em DATETIME NOT NULL,
    atualizado_por INT NOT NULL,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id),
    FULLTEXT INDEX idx_texto_completo (texto_completo)
);