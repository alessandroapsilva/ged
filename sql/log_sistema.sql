-- SQL para criação da tabela de log do sistema
-- Uso: mysql -u root -p ged < sql/log_sistema.sql

CREATE TABLE IF NOT EXISTS `log_sistema` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `tabela` VARCHAR(100) NULL,
  `registro_id` INT NULL,
  `detalhes` TEXT NULL,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_tabela_registro` (`tabela`, `registro_id`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
