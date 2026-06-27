-- Criação da tabela emails_log para auditoria de envios
-- Data: 2025-11-08

CREATE TABLE IF NOT EXISTS `emails_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `template_slug` VARCHAR(100) DEFAULT NULL,
  `assunto` VARCHAR(255) DEFAULT NULL,
  `destinatario` VARCHAR(255) NOT NULL,
  `remetente` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('sucesso','falha') NOT NULL DEFAULT 'falha',
  `erro` TEXT DEFAULT NULL,
  `payload_json` TEXT DEFAULT NULL,
  `usuario_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_destinatario` (`destinatario`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_emails_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
