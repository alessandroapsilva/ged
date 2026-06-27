-- ========================================
-- OTIMIZAÇÕES DE PERFORMANCE - GED 2.0
-- ========================================
-- Este script adiciona índices e otimizações
-- para melhorar significativamente a performance
-- do sistema GED
-- ========================================

-- Índices para tabela documentos
CREATE INDEX IF NOT EXISTS idx_documentos_titulo ON documentos(titulo);
CREATE INDEX IF NOT EXISTS idx_documentos_data_upload ON documentos(data_upload);
CREATE INDEX IF NOT EXISTS idx_documentos_usuario_id ON documentos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_documentos_tipo ON documentos(tipo_documento_id);
CREATE INDEX IF NOT EXISTS idx_documentos_pasta ON documentos(pasta_id);
CREATE INDEX IF NOT EXISTS idx_documentos_apagado ON documentos(apagado_em);
CREATE INDEX IF NOT EXISTS idx_documentos_busca ON documentos(titulo, descricao);

-- Índice composto para queries comuns
CREATE INDEX IF NOT EXISTS idx_documentos_ativo_data ON documentos(apagado_em, data_upload DESC);
CREATE INDEX IF NOT EXISTS idx_documentos_ativo_usuario ON documentos(apagado_em, usuario_id);

-- Índices para tabela pastas
CREATE INDEX IF NOT EXISTS idx_pastas_nome ON pastas(nome);
CREATE INDEX IF NOT EXISTS idx_pastas_pai ON pastas(pasta_pai_id);
CREATE INDEX IF NOT EXISTS idx_pastas_apagado ON pastas(apagado_em);

-- Índices para tabela usuarios
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
CREATE INDEX IF NOT EXISTS idx_usuarios_ativo ON usuarios(ativo);
CREATE INDEX IF NOT EXISTS idx_usuarios_funcao ON usuarios(funcao_id);

-- Índices para tabela workflow_documentos
CREATE INDEX IF NOT EXISTS idx_workflow_docs_status ON workflow_documentos(status);
CREATE INDEX IF NOT EXISTS idx_workflow_docs_documento ON workflow_documentos(documento_id);
CREATE INDEX IF NOT EXISTS idx_workflow_docs_etapa ON workflow_documentos(etapa_atual);

-- Índices para tabela workflow_notificacoes
CREATE INDEX IF NOT EXISTS idx_workflow_notif_usuario ON workflow_notificacoes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_workflow_notif_lida ON workflow_notificacoes(lida);
CREATE INDEX IF NOT EXISTS idx_workflow_notif_data ON workflow_notificacoes(data_envio DESC);

-- Índice composto para notificações não lidas
CREATE INDEX IF NOT EXISTS idx_workflow_notif_ativas ON workflow_notificacoes(usuario_id, lida, data_envio DESC);

-- Índices para logs (se existir)
CREATE INDEX IF NOT EXISTS idx_logs_usuario ON logs_sistema(usuario_id);
CREATE INDEX IF NOT EXISTS idx_logs_data ON logs_sistema(data_hora DESC);
CREATE INDEX IF NOT EXISTS idx_logs_acao ON logs_sistema(acao);

-- Índices para compartilhamentos
CREATE INDEX IF NOT EXISTS idx_compartilhamentos_documento ON compartilhamentos_documento(documento_id);
CREATE INDEX IF NOT EXISTS idx_compartilhamentos_hash ON compartilhamentos_documento(hash_compartilhamento);
CREATE INDEX IF NOT EXISTS idx_compartilhamentos_expiracao ON compartilhamentos_documento(data_expiracao);

-- Índices para assinaturas
CREATE INDEX IF NOT EXISTS idx_assinaturas_documento ON assinaturas_documento(documento_id);
CREATE INDEX IF NOT EXISTS idx_assinaturas_usuario ON assinaturas_documento(usuario_id);
CREATE INDEX IF NOT EXISTS idx_assinaturas_data ON assinaturas_documento(data_assinatura DESC);

-- ========================================
-- OTIMIZAÇÕES DE TABELAS
-- ========================================

-- Atualiza estatísticas das tabelas
ANALYZE TABLE documentos;
ANALYZE TABLE pastas;
ANALYZE TABLE usuarios;
ANALYZE TABLE workflow_documentos;
ANALYZE TABLE workflow_notificacoes;

-- Otimiza tabelas (remove fragmentação)
OPTIMIZE TABLE documentos;
OPTIMIZE TABLE pastas;
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE workflow_documentos;
OPTIMIZE TABLE workflow_notificacoes;

-- ========================================
-- VIEWS PARA QUERIES COMUNS
-- ========================================

-- View para documentos ativos
CREATE OR REPLACE VIEW v_documentos_ativos AS
SELECT 
    d.*,
    u.nome as usuario_nome,
    u.email as usuario_email,
    t.nome as tipo_nome,
    p.nome as pasta_nome
FROM documentos d
LEFT JOIN usuarios u ON d.usuario_id = u.id
LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
LEFT JOIN pastas p ON d.pasta_id = p.id
WHERE d.apagado_em IS NULL;

-- View para estatísticas rápidas
CREATE OR REPLACE VIEW v_estatisticas_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL) as total_documentos,
    (SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL) as total_pastas,
    (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) as total_usuarios_ativos,
    (SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE()) as docs_hoje,
    (SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE() - INTERVAL 1 DAY) as docs_ontem,
    (SELECT SUM(tamanho_arquivo) FROM documentos WHERE apagado_em IS NULL) as tamanho_total;

-- View para atividade recente
CREATE OR REPLACE VIEW v_atividade_recente AS
SELECT 
    d.id,
    d.titulo,
    d.data_upload,
    u.nome as usuario_nome,
    t.nome as tipo_nome,
    'documento' as tipo_atividade
FROM documentos d
LEFT JOIN usuarios u ON d.usuario_id = u.id
LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
WHERE d.apagado_em IS NULL
ORDER BY d.data_upload DESC
LIMIT 50;

-- ========================================
-- STORED PROCEDURES PARA OPERAÇÕES COMUNS
-- ========================================

DELIMITER $$

-- Procedure para buscar documentos com filtros
DROP PROCEDURE IF EXISTS sp_buscar_documentos$$
CREATE PROCEDURE sp_buscar_documentos(
    IN p_termo VARCHAR(255),
    IN p_tipo_id INT,
    IN p_usuario_id INT,
    IN p_data_inicio DATE,
    IN p_data_fim DATE,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT 
        d.*,
        u.nome as usuario_nome,
        t.nome as tipo_nome
    FROM documentos d
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
    WHERE d.apagado_em IS NULL
        AND (p_termo IS NULL OR d.titulo LIKE CONCAT('%', p_termo, '%') OR d.descricao LIKE CONCAT('%', p_termo, '%'))
        AND (p_tipo_id IS NULL OR d.tipo_documento_id = p_tipo_id)
        AND (p_usuario_id IS NULL OR d.usuario_id = p_usuario_id)
        AND (p_data_inicio IS NULL OR d.data_upload >= p_data_inicio)
        AND (p_data_fim IS NULL OR d.data_upload <= p_data_fim)
    ORDER BY d.data_upload DESC
    LIMIT p_limit OFFSET p_offset;
END$$

-- Procedure para obter métricas do dashboard
DROP PROCEDURE IF EXISTS sp_dashboard_metricas$$
CREATE PROCEDURE sp_dashboard_metricas()
BEGIN
    SELECT * FROM v_estatisticas_dashboard;
    
    -- Documentos por tipo
    SELECT 
        t.nome,
        COUNT(d.id) as total
    FROM tipos_documento t
    LEFT JOIN documentos d ON t.id = d.tipo_documento_id AND d.apagado_em IS NULL
    GROUP BY t.id, t.nome
    ORDER BY total DESC;
    
    -- Documentos nos últimos 30 dias
    SELECT 
        DATE(data_upload) as dia,
        COUNT(*) as total
    FROM documentos
    WHERE data_upload >= CURDATE() - INTERVAL 30 DAY
        AND apagado_em IS NULL
    GROUP BY DATE(data_upload)
    ORDER BY dia ASC;
END$$

-- Procedure para limpar itens antigos da lixeira
DROP PROCEDURE IF EXISTS sp_limpar_lixeira$$
CREATE PROCEDURE sp_limpar_lixeira(IN p_dias INT)
BEGIN
    DECLARE v_data_limite DATETIME;
    SET v_data_limite = DATE_SUB(NOW(), INTERVAL p_dias DAY);
    
    -- Remove documentos antigos da lixeira
    DELETE FROM documentos 
    WHERE apagado_em IS NOT NULL 
        AND apagado_em < v_data_limite;
    
    -- Remove pastas antigas da lixeira
    DELETE FROM pastas 
    WHERE apagado_em IS NOT NULL 
        AND apagado_em < v_data_limite;
    
    SELECT ROW_COUNT() as total_removidos;
END$$

DELIMITER ;

-- ========================================
-- TRIGGERS PARA AUDITORIA E VALIDAÇÃO
-- ========================================

DELIMITER $$

-- Trigger para atualizar timestamp de modificação
DROP TRIGGER IF EXISTS tr_documentos_before_update$$
CREATE TRIGGER tr_documentos_before_update
BEFORE UPDATE ON documentos
FOR EACH ROW
BEGIN
    -- Atualiza data de modificação se houver
    IF NEW.data_modificacao IS NOT NULL THEN
        SET NEW.data_modificacao = NOW();
    END IF;
END$$

-- Trigger para log de exclusões
DROP TRIGGER IF EXISTS tr_documentos_after_delete$$
CREATE TRIGGER tr_documentos_after_delete
AFTER UPDATE ON documentos
FOR EACH ROW
BEGIN
    -- Se foi marcado como apagado, registra em log
    IF OLD.apagado_em IS NULL AND NEW.apagado_em IS NOT NULL THEN
        INSERT INTO logs_sistema (usuario_id, acao, tabela, registro_id, data_hora)
        VALUES (NEW.apagado_por, 'DELETE', 'documentos', NEW.id, NOW());
    END IF;
END$$

DELIMITER ;

-- ========================================
-- EVENTOS AGENDADOS (MANUTENÇÃO)
-- ========================================

-- Habilita eventos agendados
SET GLOBAL event_scheduler = ON;

-- Evento para limpar lixeira automaticamente (todo dia à meia-noite)
DROP EVENT IF EXISTS evt_limpar_lixeira_automatica;
CREATE EVENT evt_limpar_lixeira_automatica
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY)
DO
    CALL sp_limpar_lixeira(30); -- Remove itens com mais de 30 dias

-- Evento para otimizar tabelas semanalmente
DROP EVENT IF EXISTS evt_otimizar_tabelas;
CREATE EVENT evt_otimizar_tabelas
ON SCHEDULE EVERY 1 WEEK
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 WEEK)
DO
BEGIN
    OPTIMIZE TABLE documentos;
    OPTIMIZE TABLE pastas;
    OPTIMIZE TABLE usuarios;
    ANALYZE TABLE documentos;
    ANALYZE TABLE pastas;
END;

-- ========================================
-- CONFIGURAÇÕES DE PERFORMANCE
-- ========================================

-- Ajusta configurações do MySQL para melhor performance
-- (Opcional - ajuste conforme recursos do servidor)

-- SET GLOBAL innodb_buffer_pool_size = 256M; -- Ajuste conforme RAM disponível
-- SET GLOBAL query_cache_size = 64M;
-- SET GLOBAL query_cache_limit = 2M;
-- SET GLOBAL max_connections = 150;

-- ========================================
-- VERIFICAÇÃO FINAL
-- ========================================

SELECT 
    'Otimizações aplicadas com sucesso!' as mensagem,
    (SELECT COUNT(*) FROM information_schema.statistics 
     WHERE table_schema = DATABASE() 
     AND table_name = 'documentos') as indices_documentos,
    (SELECT COUNT(*) FROM information_schema.views 
     WHERE table_schema = DATABASE()) as views_criadas,
    (SELECT COUNT(*) FROM information_schema.routines 
     WHERE routine_schema = DATABASE() 
     AND routine_type = 'PROCEDURE') as procedures_criadas;
