-- sql/assinaturas_migration.sql
-- Script de migração unificado para suporte completo a assinaturas (nova + legada)
-- Compatível com módulos esign + assinaturas_*

-- ============================================
-- 1. TABELA NOVA: documentos_assinaturas
-- ============================================
CREATE TABLE IF NOT EXISTS `documentos_assinaturas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `documento_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `data_assinatura` DATETIME NOT NULL,
    `tipo_assinatura` ENUM('ICP-Brasil', 'Simples', 'Eletronica') NOT NULL,
    `detalhes` JSON,
    INDEX `idx_documento` (`documento_id`),
    INDEX `idx_usuario` (`usuario_id`),
    INDEX `idx_data` (`data_assinatura`),
    FOREIGN KEY (`documento_id`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABELA LEGADA: assinaturas (compatibilidade esign)
-- ============================================
CREATE TABLE IF NOT EXISTS `assinaturas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `documento_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `nome_signatario` VARCHAR(255) NOT NULL,
    `cpf_cnpj_signatario` VARCHAR(20) DEFAULT NULL,
    `ip_assinatura` VARCHAR(45) NOT NULL,
    `verificador` VARCHAR(64) NOT NULL UNIQUE,
    `data_assinatura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pendente', 'assinado', 'recusado') DEFAULT 'assinado',
    `token` VARCHAR(64) DEFAULT NULL,
    `requisitante_id` INT DEFAULT NULL,
    `email_signatario` VARCHAR(255) DEFAULT NULL,
    `versao_id` INT DEFAULT NULL COMMENT 'FK para documento_versoes (se usar versionamento)',
    INDEX `idx_documento` (`documento_id`),
    INDEX `idx_usuario` (`usuario_id`),
    INDEX `idx_verificador` (`verificador`),
    INDEX `idx_token` (`token`),
    INDEX `idx_versao` (`versao_id`),
    FOREIGN KEY (`documento_id`) REFERENCES `documentos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`requisitante_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
    -- FOREIGN KEY (`versao_id`) REFERENCES `documento_versoes`(`id`) ON DELETE SET NULL -- descomente se usar versioning.sql
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CAMPOS ADICIONAIS EM documentos
-- ============================================
ALTER TABLE `documentos` 
ADD COLUMN IF NOT EXISTS `assinado` BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS `data_assinatura` DATETIME NULL,
ADD COLUMN IF NOT EXISTS `assinado_por` INT NULL;

-- Criar FK apenas se coluna assinado_por existir e FK não existir
-- (MySQL não permite IF NOT EXISTS em FK, então ignoramos erros)
-- ALTER TABLE `documentos` ADD CONSTRAINT `fk_documentos_assinado_por` FOREIGN KEY (`assinado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;

-- ============================================
-- 4. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================
-- Garante performance em buscas comuns
CREATE INDEX IF NOT EXISTS `idx_documentos_assinado` ON `documentos`(`assinado`);
CREATE INDEX IF NOT EXISTS `idx_documentos_data_assinatura` ON `documentos`(`data_assinatura`);

-- ============================================
-- OBSERVAÇÕES
-- ============================================
-- • documentos_assinaturas: Tabela nova, usada por assinaturas_assinar.php, assinaturas_verificar.php, assinaturas_minhas.php
-- • assinaturas: Tabela legada, usada por public/esign/* (index.php, assinar_process.php, verificar.php)
-- • Ambas convivem: processador assinaturas_assinar_process.php grava nas duas quando possível
-- • Migração futura: consolidar tudo em documentos_assinaturas e depreciar assinaturas legada
-- • Para ativar FK de versao_id, execute sql/versioning.sql primeiro
