-- Tabela de Fluxos de Trabalho
CREATE TABLE IF NOT EXISTS workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    criado_por INT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de Etapas do Workflow
CREATE TABLE IF NOT EXISTS workflow_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL,
    tipo_aprovacao ENUM('individual', 'todos', 'percentual') DEFAULT 'individual',
    percentual_aprovacao INT DEFAULT 100,
    prazo_dias INT,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id)
);

-- Tabela de Aprovadores por Etapa
CREATE TABLE IF NOT EXISTS workflow_aprovadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('aprovador', 'observador') DEFAULT 'aprovador',
    FOREIGN KEY (etapa_id) REFERENCES workflow_etapas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de Documentos em Workflow
CREATE TABLE IF NOT EXISTS workflow_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    workflow_id INT NOT NULL,
    etapa_atual INT NOT NULL,
    status ENUM('em_andamento', 'aprovado', 'rejeitado', 'cancelado') DEFAULT 'em_andamento',
    data_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_conclusao DATETIME,
    iniciado_por INT NOT NULL,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (workflow_id) REFERENCES workflows(id),
    FOREIGN KEY (etapa_atual) REFERENCES workflow_etapas(id),
    FOREIGN KEY (iniciado_por) REFERENCES usuarios(id)
);

-- Tabela de Histórico de Aprovações
CREATE TABLE IF NOT EXISTS workflow_aprovacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_documento_id INT NOT NULL,
    etapa_id INT NOT NULL,
    usuario_id INT NOT NULL,
    acao ENUM('aprovado', 'rejeitado', 'comentario') NOT NULL,
    comentario TEXT,
    data_acao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_documento_id) REFERENCES workflow_documentos(id),
    FOREIGN KEY (etapa_id) REFERENCES workflow_etapas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de Notificações de Workflow
CREATE TABLE IF NOT EXISTS workflow_notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('nova_tarefa', 'aprovacao', 'rejeicao', 'comentario', 'prazo') NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (workflow_documento_id) REFERENCES workflow_documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);