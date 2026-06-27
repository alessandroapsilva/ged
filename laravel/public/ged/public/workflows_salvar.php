<?php
// public/workflows_salvar.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: workflows_listar.php');
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$status = $_POST['status'] ?? 'ativo';
$etapas = $_POST['etapas'] ?? [];

if (empty($nome) || empty($etapas)) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Nome e etapas são obrigatórios.'];
    header('Location: workflows_criar.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Criar workflow
    $stmt = $pdo->prepare("INSERT INTO workflows (nome, descricao, criado_por, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $descricao, $_SESSION['user_id'], $status]);
    $workflow_id = $pdo->lastInsertId();
    
    // Criar etapas
    foreach ($etapas as $ordem => $etapa) {
        $etapa_nome = trim($etapa['nome'] ?? '');
        $etapa_desc = trim($etapa['descricao'] ?? '');
        $prazo_dias = !empty($etapa['prazo_dias']) ? (int)$etapa['prazo_dias'] : null;
        $tipo_aprov = $etapa['tipo_aprovacao'] ?? 'individual';
        $percentual = ($tipo_aprov === 'percentual' && !empty($etapa['percentual_aprovacao'])) ? (int)$etapa['percentual_aprovacao'] : 100;
        
        $stmt_etapa = $pdo->prepare("INSERT INTO workflow_etapas (workflow_id, nome, descricao, ordem, tipo_aprovacao, percentual_aprovacao, prazo_dias) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_etapa->execute([$workflow_id, $etapa_nome, $etapa_desc, $ordem, $tipo_aprov, $percentual, $prazo_dias]);
        $etapa_id = $pdo->lastInsertId();
        
        // Adicionar aprovadores
        $aprovadores = $etapa['aprovadores'] ?? [];
        if (!empty($aprovadores)) {
            $stmt_aprov = $pdo->prepare("INSERT INTO workflow_aprovadores (etapa_id, usuario_id, tipo) VALUES (?, ?, 'aprovador')");
            foreach ($aprovadores as $usuario_id) {
                $stmt_aprov->execute([$etapa_id, (int)$usuario_id]);
            }
        }
    }
    
    $pdo->commit();
    registrar_log($pdo, $_SESSION['user_id'], "Criou o workflow '{$nome}' (ID: {$workflow_id}).");
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Workflow criado com sucesso!'];
    header('Location: workflows_listar.php');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao criar workflow: ' . $e->getMessage()];
    header('Location: workflows_criar.php');
    exit();
}