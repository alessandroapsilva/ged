-- Criação da tabela app_settings para configurações do sistema
-- Data: 2025-11-08

CREATE TABLE IF NOT EXISTS `app_settings` (
  `chave` VARCHAR(100) NOT NULL,
  `valor` TEXT DEFAULT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão de SMTP (opcional - podem vir de variáveis de ambiente)
INSERT INTO `app_settings` (`chave`, `valor`, `descricao`) VALUES
('smtp_host', '', 'Servidor SMTP (ex: smtp.gmail.com)'),
('smtp_port', '587', 'Porta SMTP (587 para TLS, 465 para SSL)'),
('smtp_user', '', 'Usuário SMTP'),
('smtp_pass', '', 'Senha SMTP (recomendado usar variável de ambiente GED_SMTP_PASS)'),
('smtp_secure', 'tls', 'Segurança SMTP (tls ou ssl)'),
('mail_from', 'noreply@ged.enfas.com.br', 'E-mail remetente'),
('mail_from_name', 'ENFAS GED', 'Nome do remetente')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
