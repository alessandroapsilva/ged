-- SQL de migração: Templates de E-mail e Logs de Envio
-- Data: 2025-10-26

-- Tabela principal de templates
CREATE TABLE IF NOT EXISTS email_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  nome VARCHAR(150) NOT NULL,
  assunto VARCHAR(200) NOT NULL,
  corpo_html MEDIUMTEXT NULL,
  corpo_texto MEDIUMTEXT NULL,
  variaveis_json TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico de versões (auditoria simples do conteúdo)
CREATE TABLE IF NOT EXISTS email_template_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  assunto VARCHAR(200) NOT NULL,
  corpo_html MEDIUMTEXT NULL,
  corpo_texto MEDIUMTEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  criado_por INT NULL,
  CONSTRAINT fk_email_template_versions_template
    FOREIGN KEY (template_id) REFERENCES email_templates(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log de envios de e-mail
CREATE TABLE IF NOT EXISTS emails_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_slug VARCHAR(100) NULL,
  assunto VARCHAR(200) NULL,
  destinatario VARCHAR(255) NOT NULL,
  remetente VARCHAR(255) NULL,
  status ENUM('sucesso','falha') NOT NULL,
  erro TEXT NULL,
  payload_json TEXT NULL,
  enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissão para gestão de templates (somente TI/Admin)
INSERT INTO permissoes (chave, nome)
SELECT 'email.templates.manage', 'Gerenciar templates de e-mail'
WHERE NOT EXISTS (SELECT 1 FROM permissoes WHERE chave = 'email.templates.manage');

-- Cria função TI (Tecnologia da Informação) se não existir
INSERT INTO funcoes (nome, descricao)
SELECT 'TI', 'Tecnologia da Informação'
WHERE NOT EXISTS (SELECT 1 FROM funcoes WHERE nome = 'TI');

-- Concede permissão de gestão de templates a perfis comuns de administração
INSERT INTO funcao_permissao (funcao_id, permissao_id)
SELECT f.id, p.id
FROM funcoes f
JOIN permissoes p ON p.chave = 'email.templates.manage'
LEFT JOIN funcao_permissao fp ON fp.funcao_id = f.id AND fp.permissao_id = p.id
WHERE f.nome IN ('Administrador','Admin','Gestor','TI') AND fp.funcao_id IS NULL;

-- Seeds de templates padrão (inserção apenas se ausentes)
INSERT INTO email_templates (slug, nome, assunto, corpo_html, corpo_texto, variaveis_json)
SELECT 'compartilhar_link', 'Compartilhamento de Documento',
       'Documento compartilhado: {{titulo}}',
       '<p>Olá,</p><p>Um documento foi compartilhado com você.</p><p><strong>Título:</strong> {{titulo}}</p><p><a href="{{url}}">Acessar documento</a></p><hr><p>Validade: {{validade}}</p>',
       'Olá,\nUm documento foi compartilhado com você.\nTítulo: {{titulo}}\nAcessar: {{url}}\nValidade: {{validade}}',
       '{"titulo":"Título do documento","url":"URL de acesso","validade":"Data/hora de expiração"}'
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE slug = 'compartilhar_link');

INSERT INTO email_templates (slug, nome, assunto, corpo_html, corpo_texto, variaveis_json)
SELECT 'redefinir_senha', 'Redefinição de Senha',
       'Redefinição de senha do GED',
       '<p>Olá {{nome}},</p><p>Use o link abaixo para redefinir sua senha:</p><p><a href="{{link}}">Redefinir senha</a></p><p>Se você não solicitou, ignore este e-mail.</p>',
       'Olá {{nome}},\nUse o link para redefinir sua senha: {{link}}',
       '{"nome":"Nome do usuário","link":"Link de redefinição"}'
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE slug = 'redefinir_senha');

INSERT INTO email_templates (slug, nome, assunto, corpo_html, corpo_texto, variaveis_json)
SELECT 'aviso_sistema', 'Aviso do Sistema',
       '{{assunto}}',
       '<p>{{mensagem}}</p>',
       '{{mensagem}}',
       '{"assunto":"Assunto do aviso","mensagem":"Texto do aviso"}'
WHERE NOT EXISTS (SELECT 1 FROM email_templates WHERE slug = 'aviso_sistema');
