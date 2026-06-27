SET NAMES utf8mb4;

-- Corrige textos com acentos e quebras de linha reais
UPDATE `email_templates`
SET `nome` = 'Compartilhamento por Link',
    `assunto` = 'Você recebeu acesso ao documento {{documento.titulo}}',
    `corpo` = 'Olá {{nome}},

Você recebeu acesso ao documento: {{documento.titulo}}.
Acesse pelo link: {{link}}

Mensagem: {{mensagem|}}

Atenciosamente,
Equipe eDok'
WHERE `slug` = 'compartilhar_link';

UPDATE `email_templates`
SET `nome` = 'Recuperação de Senha',
    `assunto` = 'Recupere sua senha',
    `corpo` = 'Olá {{usuario.nome}},

Para redefinir sua senha, clique no link: {{link}}
Este link expira em {{expira_em}}.

Se não foi você, ignore este e-mail.'
WHERE `slug` = 'recuperar_senha';

-- Adiciona 3 templates extras úteis
INSERT INTO `email_templates` (`slug`, `nome`, `assunto`, `corpo`, `ativo`) VALUES
  ('convite_usuario', 'Convite de Usuário', 'Você foi convidado para acessar o eDok',
   'Olá {{nome}},

Você foi convidado para acessar o eDok.
Para ativar sua conta, acesse: {{link}}

Se você não esperava este convite, pode ignorar este e-mail.', 1),
  ('alerta_vencimento', 'Alerta de Vencimento', 'Documento {{documento.titulo}} vence em {{dias}} dia(s)',
   'Olá {{nome}},

O documento "{{documento.titulo}}" vence em {{dias}} dia(s).
Data de vencimento: {{documento.vencimento|sem data}}.

Acesse o documento: {{link}}', 1),
  ('notificacao_upload', 'Novo Documento Enviado', 'Novo documento disponível: {{documento.titulo}}',
   'Olá {{nome}},

Um novo documento foi enviado: {{documento.titulo}} por {{usuario.nome}}.
Acesse: {{link}}', 1)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `assunto`=VALUES(`assunto`), `corpo`=VALUES(`corpo`), `ativo`=VALUES(`ativo`);
