SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `chave` VARCHAR(191) NOT NULL,
  `valor` MEDIUMTEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeds iniciais para cabeçalho/rodapé de e-mail (vazios por padrão)
INSERT INTO `app_settings` (`chave`, `valor`) VALUES
('email_header_html', ''),
('email_footer_html', '')
ON DUPLICATE KEY UPDATE `valor`=VALUES(`valor`);
