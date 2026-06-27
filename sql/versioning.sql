-- SQL for Document Versioning Feature

-- 1. Create the new table for document versions
CREATE TABLE `documento_versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `caminho_arquivo` varchar(255) NOT NULL,
  `tamanho_arquivo` int(11) NOT NULL,
  `hash_arquivo` varchar(255) DEFAULT NULL,
  `motivo_alteracao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_id` (`documento_id`),
  CONSTRAINT `fk_documento_versoes_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_documento_versoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add a column to the main documents table to track the current version
ALTER TABLE `documentos` ADD `versao_atual` INT(11) NOT NULL DEFAULT 1 AFTER `titulo`;

-- 3. (Optional but Recommended) Populate the versions table for existing documents
-- This script assumes all existing documents are version 1.
INSERT INTO documento_versoes (documento_id, versao, usuario_id, data_criacao, caminho_arquivo, tamanho_arquivo, hash_arquivo, motivo_alteracao)
SELECT 
    id AS documento_id,
    1 AS versao, -- All existing docs are version 1
    NULL AS usuario_id, -- Or assign a default admin user ID if available
    data_upload AS data_criacao, -- Use the original upload date
    caminho_arquivo, -- The existing file path
    tamanho_arquivo, -- The existing file size
    hash_arquivo, -- The existing file hash
    'Versão inicial' AS motivo_alteracao -- Initial version comment
FROM 
    documentos;

