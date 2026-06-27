<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

require_auth();
require_permission('admin.access');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_workflows.php');
    exit();
}

$pdo->beginTransaction();

try {
    $workflow_id = $_POST['workflow_id'] ?: null;

    // Create or update the workflow
    if ($workflow_id) {
        $stmt = $pdo->prepare("UPDATE workflows SET nome = ?, descricao = ?, status = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['status'], $workflow_id]);

        // Clean up old steps and approvers
        $stmt_find_etapas = $pdo->prepare("SELECT id FROM workflow_etapas WHERE workflow_id = ?");
        $stmt_find_etapas->execute([$workflow_id]);
        $old_etapa_ids = $stmt_find_etapas->fetchAll(PDO::FETCH_COLUMN);

        if ($old_etapa_ids) {
            $in_clause = implode(',', array_fill(0, count($old_etapa_ids), '?'));
            $stmt_delete_aprovadores = $pdo->prepare("DELETE FROM workflow_aprovadores WHERE etapa_id IN ($in_clause)");
            $stmt_delete_aprovadores->execute($old_etapa_ids);

            $stmt_delete_etapas = $pdo->prepare("DELETE FROM workflow_etapas WHERE workflow_id = ?");
            $stmt_delete_etapas->execute([$workflow_id]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO workflows (nome, descricao, status, criado_por) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['status'], $_SESSION['user_id']]);
        $workflow_id = $pdo->lastInsertId();
    }

    // Insert new steps and approvers
    if (isset($_POST['etapas'])) {
        foreach ($_POST['etapas'] as $ordem => $etapa_data) {
            $stmt_etapa = $pdo->prepare("
                INSERT INTO workflow_etapas (workflow_id, nome, descricao, ordem, tipo_aprovacao, prazo_dias)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt_etapa->execute([
                $workflow_id,
                $etapa_data['nome'],
                $etapa_data['descricao'],
                $ordem,
                $etapa_data['tipo_aprovacao'],
                $etapa_data['prazo_dias'] ?: null
            ]);
            $etapa_id = $pdo->lastInsertId();

            if (isset($etapa_data['aprovadores'])) {
                foreach ($etapa_data['aprovadores'] as $usuario_id) {
                    $stmt_aprovador = $pdo->prepare("INSERT INTO workflow_aprovadores (etapa_id, usuario_id) VALUES (?, ?)");
                    $stmt_aprovador->execute([$etapa_id, $usuario_id]);
                }
            }
        }
    }

    $pdo->commit();

    // Redirect with a success message (if flash messages are supported)
    // For now, just redirect.
    header('Location: admin_workflows.php');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    // In a real app, you should log the error and show a user-friendly message.
    die("Erro ao salvar o workflow: " . $e->getMessage());
}
