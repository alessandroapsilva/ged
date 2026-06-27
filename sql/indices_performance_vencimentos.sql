-- sql/indices_performance_vencimentos.sql
-- Adiciona índices para otimizar queries de vencimento

-- Índice composto para filtros de vencimento
CREATE INDEX IF NOT EXISTS idx_documentos_vencimento 
ON documentos(data_vencimento, apagado_em);

-- Índice para ordenação por vencimento
CREATE INDEX IF NOT EXISTS idx_documentos_vencimento_data 
ON documentos(data_vencimento);

-- Análise de performance (opcional - executar após criar índices)
ANALYZE TABLE documentos;

-- Verificar índices criados
SHOW INDEX FROM documentos WHERE Key_name LIKE '%vencimento%';

SELECT 
    'Performance otimizada para queries de vencimento!' as status,
    COUNT(*) as total_documentos,
    COUNT(data_vencimento) as com_vencimento,
    COUNT(*) - COUNT(data_vencimento) as sem_vencimento
FROM documentos 
WHERE apagado_em IS NULL;
