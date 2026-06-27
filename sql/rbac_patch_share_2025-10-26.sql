-- Adiciona permissão de compartilhamento e atribui a Admin e Gestor
INSERT INTO permissoes (chave, descricao)
SELECT 'document.share', 'Compartilhar documentos por link seguro'
WHERE NOT EXISTS (SELECT 1 FROM permissoes WHERE chave = 'document.share');

-- Atribui a Admin (1) e Gestor (2)
INSERT INTO funcao_permissao (funcao_id, permissao_id)
SELECT 1, id FROM permissoes WHERE chave = 'document.share'
ON DUPLICATE KEY UPDATE funcao_id = VALUES(funcao_id), permissao_id = VALUES(permissao_id);

INSERT INTO funcao_permissao (funcao_id, permissao_id)
SELECT 2, id FROM permissoes WHERE chave = 'document.share'
ON DUPLICATE KEY UPDATE funcao_id = VALUES(funcao_id), permissao_id = VALUES(permissao_id);
