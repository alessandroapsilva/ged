<?php
require_once '../core/init.php';
require_once '../core/workflow.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Acesso negado']));
}

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception('Método inválido');
    }

    $workflow = new Workflow($pdo, $_SESSION['usuario']['id']);
    
    // Verifica se o usuário é aprovador da etapa atual
    $sql = "SELECT COUNT(*) as total 
            FROM workflow_documentos wd
            JOIN workflow_aprovadores wa ON wa.etapa_id = wd.etapa_atual
            WHERE wd.id = ? AND wa.usuario_id = ? AND wa.tipo = 'aprovador'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['workflow_documento_id'], $_SESSION['usuario']['id']]);
    
    if ($stmt->fetch(PDO::FETCH_ASSOC)['total'] == 0) {
        throw new Exception('Você não tem permissão para aprovar/rejeitar esta etapa');
    }

    // Processa a ação
    $aprovado = ($_POST['acao'] == 'aprovar');
    $workflow->registrarAprovacao(
        $_POST['workflow_documento_id'],
        $aprovado,
        $_POST['comentario']
    );
    
    // Retorna sucesso
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}