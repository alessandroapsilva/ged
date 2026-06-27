SET NAMES utf8mb4;

-- Create table email_templates if not exists
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `nome` VARCHAR(255) NOT NULL,
  `assunto` VARCHAR(255) NOT NULL,
  `corpo` MEDIUMTEXT,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional seed data
INSERT INTO `email_templates` (`slug`, `nome`, `assunto`, `corpo`, `ativo`) VALUES
  ('compartilhar_link', 'Compartilhamento por Link', 'Você recebeu acesso ao documento {{documento.titulo}}',
   'Olá {{nome}},

Você recebeu acesso ao documento: {{documento.titulo}}.
Acesse pelo link: {{link}}

Mensagem: {{mensagem|}}

Atenciosamente,
Equipe eDok', 1),
  ('recuperar_senha', 'Recuperação de Senha', 'Recupere sua senha',
   'Olá {{usuario.nome}},

Para redefinir sua senha, clique no link: {{link}}
Este link expira em {{expira_em}}.

Se não foi você, ignore este e-mail.', 1)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `assunto`=VALUES(`assunto`), `corpo`=VALUES(`corpo`), `ativo`=VALUES(`ativo`);
