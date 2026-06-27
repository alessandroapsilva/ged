-- RBAC básico e seeds (2025-10-26)

-- Cria tabela de funções (papéis)
CREATE TABLE IF NOT EXISTS funcoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  descricao VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cria tabela de permissões
CREATE TABLE IF NOT EXISTS permissoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chave VARCHAR(150) NOT NULL UNIQUE,
  descricao VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de relacionamento função-permissão
CREATE TABLE IF NOT EXISTS funcao_permissao (
  funcao_id INT UNSIGNED NOT NULL,
  permissao_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (funcao_id, permissao_id),
  FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE CASCADE,
  FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adiciona coluna funcao_id em usuarios, se não existir
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS funcao_id INT UNSIGNED NULL;
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_funcao (funcao_id);

-- Conecta usuários a funcoes
ALTER TABLE usuarios ADD CONSTRAINT IF NOT EXISTS fk_usuarios_funcoes FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE SET NULL;

-- Seeds de funções
INSERT IGNORE INTO funcoes (id, nome, descricao) VALUES
  (1, 'Administrador', 'Acesso total ao sistema'),
  (2, 'Gestor', 'Gerencia documentos e workflows'),
  (3, 'Colaborador', 'Cria e consulta documentos');

-- Seeds de permissões principais
INSERT IGNORE INTO permissoes (chave, descricao) VALUES
  ('admin.access', 'Acesso ao painel administrativo'),
  ('document.create', 'Criar documentos'),
  ('document.view', 'Ver documentos'),
  ('document.edit', 'Editar documentos'),
  ('document.delete', 'Excluir documentos'),
  ('document.sign', 'Assinar documentos'),
  ('workflow.manage', 'Gerenciar workflows'),
  ('report.view', 'Ver relatórios'),
  ('config.manage', 'Gerenciar configurações');

-- Atribuições padrão
INSERT IGNORE INTO funcao_permissao (funcao_id, permissao_id)
SELECT 1, p.id FROM permissoes p; -- Admin recebe todas

INSERT IGNORE INTO funcao_permissao (funcao_id, permissao_id)
SELECT 2, p.id FROM permissoes p WHERE p.chave IN ('document.create','document.view','document.edit','document.sign','workflow.manage','report.view');

INSERT IGNORE INTO funcao_permissao (funcao_id, permissao_id)
SELECT 3, p.id FROM permissoes p WHERE p.chave IN ('document.create','document.view');

-- Garante que o usuário #1 seja Administrador
UPDATE usuarios SET funcao_id = 1 WHERE id = 1;
