<?php
include 'auth_check.php';
require_once 'classes/Document.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selected_docs']) || !isset($_POST['bulk_action'])) {
    header('Location: index.php');
    exit;
}

$document = new Document();
$selectedDocs = $_POST['selected_docs'];
$action = $_POST['bulk_action'];
$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

$successCount = 0;
$errors = [];

// Verificar permissões
$allowedActions = [
    'Diretor' => ['approve', 'reject', 'archive'],
    'Analista' => [] // Analistas não podem fazer ações em lote
];

if (!in_array($action, $allowedActions[$userRole] ?? [])) {
    $_SESSION['bulk_message'] = 'Você não tem permissão para executar esta ação.';
    $_SESSION['bulk_message_type'] = 'error';
    header('Location: index.php');
    exit;
}

foreach ($selectedDocs as $docId) {
    $doc = $document->getById($docId);
    if (!$doc) {
        $errors[] = "Documento #$docId não encontrado.";
        continue;
    }

    // Verificar se a ação é válida para o status atual
    $validTransitions = [
        'approve' => ['Em Análise'],
        'reject' => ['Em Análise', 'Aguardando Aprovação', 'Em Revisão'],
        'archive' => ['Aprovado']
    ];

    if (!in_array($doc['status'], $validTransitions[$action] ?? [])) {
        $errors[] = "Ação inválida para documento #$docId (status: {$doc['status']}).";
        continue;
    }

    $newStatus = '';
    switch ($action) {
        case 'approve':
            $newStatus = 'Aprovado';
            break;
        case 'reject':
            $newStatus = 'Reprovado';
            break;
        case 'archive':
            $newStatus = 'Arquivado';
            break;
    }

    if ($document->updateStatus($docId, $newStatus, $userId)) {
        // Notificar mudança de status
        require_once 'classes/Notification.php';
        $notification = new Notification();
        $notification->notifyStatusChange($docId, $doc['status'], $newStatus, $userId);
        $successCount++;
    } else {
        $errors[] = "Erro ao processar documento #$docId.";
    }
}

$message = "$successCount documento(s) processado(s) com sucesso.";
if (!empty($errors)) {
    $message .= " Erros: " . implode(', ', $errors);
}

$_SESSION['bulk_message'] = $message;
$_SESSION['bulk_message_type'] = empty($errors) ? 'success' : 'warning';

header('Location: index.php');
exit;
?>