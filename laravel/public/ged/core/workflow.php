<?php
/**
 * Classe para gerenciamento de Workflows
 */
class Workflow {
    private $db;
    private $usuario_atual;

    public function __construct($db, $usuario_atual) {
        $this->db = $db;
        $this->usuario_atual = $usuario_atual;
    }

    /**
     * Cria um novo workflow
     */
    public function criar($nome, $descricao) {
        try {
            $sql = "INSERT INTO workflows (nome, descricao, criado_por) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nome, $descricao, $this->usuario_atual]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erro ao criar workflow: " . $e->getMessage());
        }
    }

    /**
     * Adiciona uma etapa ao workflow
     */
    public function adicionarEtapa($workflow_id, $nome, $descricao, $ordem, $tipo_aprovacao = 'individual', $percentual = 100, $prazo = null) {
        try {
            $sql = "INSERT INTO workflow_etapas (workflow_id, nome, descricao, ordem, tipo_aprovacao, percentual_aprovacao, prazo_dias) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow_id, $nome, $descricao, $ordem, $tipo_aprovacao, $percentual, $prazo]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erro ao adicionar etapa: " . $e->getMessage());
        }
    }

    /**
     * Define aprovadores para uma etapa
     */
    public function definirAprovadores($etapa_id, $aprovadores, $tipo = 'aprovador') {
        try {
            $sql = "INSERT INTO workflow_aprovadores (etapa_id, usuario_id, tipo) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($aprovadores as $usuario_id) {
                $stmt->execute([$etapa_id, $usuario_id, $tipo]);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Erro ao definir aprovadores: " . $e->getMessage());
        }
    }

    /**
     * Inicia um workflow para um documento
     */
    public function iniciarWorkflow($documento_id, $workflow_id) {
        try {
            // Pega a primeira etapa do workflow
            $sql = "SELECT id FROM workflow_etapas WHERE workflow_id = ? ORDER BY ordem ASC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow_id]);
            $primeira_etapa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$primeira_etapa) {
                throw new Exception("Workflow não possui etapas definidas");
            }

            // Inicia o workflow
            $sql = "INSERT INTO workflow_documentos (documento_id, workflow_id, etapa_atual, iniciado_por) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$documento_id, $workflow_id, $primeira_etapa['id'], $this->usuario_atual]);
            
            $workflow_documento_id = $this->db->lastInsertId();
            
            // Notifica os aprovadores da primeira etapa
            $this->notificarAprovadores($workflow_documento_id, $primeira_etapa['id']);
            
            return $workflow_documento_id;
        } catch (Exception $e) {
            throw new Exception("Erro ao iniciar workflow: " . $e->getMessage());
        }
    }

    /**
     * Registra uma aprovação/rejeição em uma etapa
     */
    public function registrarAprovacao($workflow_documento_id, $aprovado, $comentario = null) {
        try {
            $this->db->beginTransaction();

            // Obtém informações do workflow
            $sql = "SELECT wd.*, we.workflow_id, we.ordem, we.tipo_aprovacao, we.percentual_aprovacao 
                    FROM workflow_documentos wd 
                    JOIN workflow_etapas we ON we.id = wd.etapa_atual 
                    WHERE wd.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow_documento_id]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);

            // Registra a ação
            $sql = "INSERT INTO workflow_aprovacoes (workflow_documento_id, etapa_id, usuario_id, acao, comentario) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $acao = $aprovado ? 'aprovado' : 'rejeitado';
            $stmt->execute([$workflow_documento_id, $workflow['etapa_atual'], $this->usuario_atual, $acao, $comentario]);

            // Verifica se precisa avançar para próxima etapa
            if ($aprovado) {
                $this->verificarProgressaoEtapa($workflow_documento_id, $workflow);
            } else {
                // Se rejeitado, finaliza o workflow
                $sql = "UPDATE workflow_documentos SET status = 'rejeitado', data_conclusao = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$workflow_documento_id]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erro ao registrar aprovação: " . $e->getMessage());
        }
    }

    /**
     * Verifica se deve avançar para próxima etapa
     */
    private function verificarProgressaoEtapa($workflow_documento_id, $workflow) {
        // Verifica o tipo de aprovação necessária
        $pode_avancar = false;

        if ($workflow['tipo_aprovacao'] == 'individual') {
            $pode_avancar = true;
        } else {
            // Conta quantas aprovações tem na etapa atual
            $sql = "SELECT COUNT(*) as total, 
                    (SELECT COUNT(*) FROM workflow_aprovadores WHERE etapa_id = ?) as total_aprovadores 
                    FROM workflow_aprovacoes 
                    WHERE workflow_documento_id = ? AND etapa_id = ? AND acao = 'aprovado'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow['etapa_atual'], $workflow_documento_id, $workflow['etapa_atual']]);
            $contagem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($workflow['tipo_aprovacao'] == 'todos') {
                $pode_avancar = ($contagem['total'] == $contagem['total_aprovadores']);
            } else {
                // Calcula o percentual de aprovações
                $percentual = ($contagem['total'] / $contagem['total_aprovadores']) * 100;
                $pode_avancar = ($percentual >= $workflow['percentual_aprovacao']);
            }
        }

        if ($pode_avancar) {
            // Busca próxima etapa
            $sql = "SELECT id FROM workflow_etapas 
                    WHERE workflow_id = ? AND ordem > ? 
                    ORDER BY ordem ASC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow['workflow_id'], $workflow['ordem']]);
            $proxima_etapa = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($proxima_etapa) {
                // Atualiza para próxima etapa
                $sql = "UPDATE workflow_documentos SET etapa_atual = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$proxima_etapa['id'], $workflow_documento_id]);

                // Notifica os aprovadores da nova etapa
                $this->notificarAprovadores($workflow_documento_id, $proxima_etapa['id']);
            } else {
                // Finaliza o workflow como aprovado
                $sql = "UPDATE workflow_documentos SET status = 'aprovado', data_conclusao = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$workflow_documento_id]);
            }
        }
    }

    /**
     * Notifica os aprovadores de uma etapa
     */
    private function notificarAprovadores($workflow_documento_id, $etapa_id) {
        $sql = "SELECT wa.usuario_id 
                FROM workflow_aprovadores wa 
                WHERE wa.etapa_id = ? AND wa.tipo = 'aprovador'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etapa_id]);
        $aprovadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aprovadores as $aprovador) {
            $sql = "INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) 
                    VALUES (?, ?, 'nova_tarefa', 'Você tem um novo documento para aprovar')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow_documento_id, $aprovador['usuario_id']]);
        }
    }

    /**
     * Lista workflows disponíveis
     */
    public function listarWorkflows() {
        try {
            $sql = "SELECT w.*, u.nome as criado_por_nome 
                    FROM workflows w 
                    JOIN usuarios u ON u.id = w.criado_por 
                    WHERE w.status = 'ativo' 
                    ORDER BY w.nome";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erro ao listar workflows: " . $e->getMessage());
        }
    }

    /**
     * Obtém detalhes de um workflow específico
     */
    public function getWorkflow($workflow_id) {
        try {
            $sql = "SELECT w.*, 
                    (SELECT COUNT(*) FROM workflow_etapas WHERE workflow_id = w.id) as total_etapas 
                    FROM workflows w 
                    WHERE w.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflow_id]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($workflow) {
                // Busca as etapas
                $sql = "SELECT we.*, 
                        (SELECT COUNT(*) FROM workflow_aprovadores WHERE etapa_id = we.id) as total_aprovadores 
                        FROM workflow_etapas we 
                        WHERE we.workflow_id = ? 
                        ORDER BY we.ordem";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$workflow_id]);
                $workflow['etapas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $workflow;
        } catch (Exception $e) {
            throw new Exception("Erro ao obter workflow: " . $e->getMessage());
        }
    }
}