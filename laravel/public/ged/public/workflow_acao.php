<?php
// public/workflow_acao.php - Processa aprovação/rejeição de documentos
require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'erro' => 'Acesso negado']);
    exit();
}

$workflow_doc_id = (int)($_POST['workflow_documento_id'] ?? 0);
$acao = $_POST['acao'] ?? ''; // 'aprovado' ou 'rejeitado'
$comentario = trim($_POST['comentario'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$workflow_doc_id || !in_array($acao, ['aprovado', 'rejeitado'])) {
    echo json_encode(['ok' => false, 'erro' => 'Parâmetros inválidos']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Busca o workflow_documento
    $stmt = $pdo->prepare("SELECT * FROM workflow_documentos WHERE id = ? AND status = 'em_andamento'");
    $stmt->execute([$workflow_doc_id]);
    $wd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wd) {
        throw new Exception('Documento não encontrado ou já processado.');
    }
    
    $etapa_atual = (int)$wd['etapa_atual'];
    
    // Verifica se o usuário é aprovador desta etapa
    $stmt_aprov = $pdo->prepare("SELECT * FROM workflow_aprovadores WHERE etapa_id = ? AND usuario_id = ?");
    $stmt_aprov->execute([$etapa_atual, $user_id]);
    if (!$stmt_aprov->fetch()) {
        throw new Exception('Você não é aprovador desta etapa.');
    }
    
    // Registra a aprovação/rejeição
    $stmt_reg = $pdo->prepare("INSERT INTO workflow_aprovacoes (workflow_documento_id, etapa_id, usuario_id, acao, comentario) 
                                VALUES (?, ?, ?, ?, ?)");
    $stmt_reg->execute([$workflow_doc_id, $etapa_atual, $user_id, $acao, $comentario]);
    
    // Se rejeitado, marca o workflow como rejeitado
    if ($acao === 'rejeitado') {
        $stmt_upd = $pdo->prepare("UPDATE workflow_documentos SET status = 'rejeitado', data_conclusao = NOW() WHERE id = ?");
        $stmt_upd->execute([$workflow_doc_id]);
        
        // Notifica o iniciador
        $stmt_notif = $pdo->prepare("INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) 
                                      VALUES (?, ?, 'rejeicao', ?)");
        $stmt_notif->execute([
            $workflow_doc_id, 
            $wd['iniciado_por'], 
            "Documento rejeitado na etapa atual. Motivo: " . $comentario
        ]);
    } else {
        // Se aprovado, verifica se a etapa está completa
        $stmt_etapa = $pdo->prepare("SELECT * FROM workflow_etapas WHERE id = ?");
        $stmt_etapa->execute([$etapa_atual]);
        $etapa = $stmt_etapa->fetch(PDO::FETCH_ASSOC);
        
        $tipo_aprov = $etapa['tipo_aprovacao'];
        $percentual_req = (int)$etapa['percentual_aprovacao'];
        
        // Conta aprovadores necessários
        $stmt_count_aprov = $pdo->prepare("SELECT COUNT(*) FROM workflow_aprovadores WHERE etapa_id = ?");
        $stmt_count_aprov->execute([$etapa_atual]);
        $total_aprovadores = (int)$stmt_count_aprov->fetchColumn();
        
        // Conta aprovações já registradas
        $stmt_count_ok = $pdo->prepare("SELECT COUNT(*) FROM workflow_aprovacoes WHERE workflow_documento_id = ? AND etapa_id = ? AND acao = 'aprovado'");
        $stmt_count_ok->execute([$workflow_doc_id, $etapa_atual]);
        $aprovacoes = (int)$stmt_count_ok->fetchColumn();
        
        $etapa_completa = false;
        if ($tipo_aprov === 'individual') {
            $etapa_completa = true; // qualquer um pode aprovar
        } elseif ($tipo_aprov === 'todos') {
            $etapa_completa = ($aprovacoes >= $total_aprovadores);
        } elseif ($tipo_aprov === 'percentual') {
            $percentual_atual = ($aprovacoes / $total_aprovadores) * 100;
            $etapa_completa = ($percentual_atual >= $percentual_req);
        }
        
        if ($etapa_completa) {
            // Busca próxima etapa
            $stmt_prox = $pdo->prepare("SELECT * FROM workflow_etapas WHERE workflow_id = ? AND ordem > ? ORDER BY ordem ASC LIMIT 1");
            $stmt_prox->execute([$wd['workflow_id'], $etapa['ordem']]);
            $proxima_etapa = $stmt_prox->fetch(PDO::FETCH_ASSOC);
            
            if ($proxima_etapa) {
                // Avança para próxima etapa
                $stmt_av = $pdo->prepare("UPDATE workflow_documentos SET etapa_atual = ? WHERE id = ?");
                $stmt_av->execute([$proxima_etapa['id'], $workflow_doc_id]);
                
                // Notifica novos aprovadores
                $stmt_novos_aprov = $pdo->prepare("SELECT usuario_id FROM workflow_aprovadores WHERE etapa_id = ?");
                $stmt_novos_aprov->execute([$proxima_etapa['id']]);
                $novos = $stmt_novos_aprov->fetchAll(PDO::FETCH_COLUMN);
                
                $stmt_notif2 = $pdo->prepare("INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) 
                                               VALUES (?, ?, 'nova_tarefa', ?)");
                foreach ($novos as $novo_user) {
                    $stmt_notif2->execute([
                        $workflow_doc_id, 
                        $novo_user, 
                        "Novo documento aguardando aprovação na etapa: " . $proxima_etapa['nome']
                    ]);
                }
            } else {
                // Workflow concluído!
                $stmt_concl = $pdo->prepare("UPDATE workflow_documentos SET status = 'aprovado', data_conclusao = NOW() WHERE id = ?");
                $stmt_concl->execute([$workflow_doc_id]);
                
                // Notifica o iniciador
                $stmt_notif3 = $pdo->prepare("INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) 
                                               VALUES (?, ?, 'aprovacao', 'Documento aprovado em todas as etapas!')");
                $stmt_notif3->execute([$workflow_doc_id, $wd['iniciado_por']]);
            }
        }
    }
    
    $pdo->commit();
    registrar_log($pdo, $user_id, "Workflow: {$acao} documento ID {$wd['documento_id']}.");
    echo json_encode(['ok' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
