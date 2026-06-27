<?php
// public/itens_mover.php
require_once '../core/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$itens = $_POST['itens'] ?? [];
$destino_id = $_POST['destino_id'] === '' ? null : (int)$_POST['destino_id'];

if (empty($itens)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum item selecionado.']);
    exit();
}

$pdo->beginTransaction();
try {
    $stmt_doc = $pdo->prepare("UPDATE documentos SET pasta_id = ? WHERE id = ?");
    $stmt_pasta = $pdo->prepare("UPDATE pastas SET pasta_pai_id = ? WHERE id = ?");

    foreach ($itens as $item) {
        $parts = explode('-', $item);
        $prefixo = $parts[0];
        $id = (int)$parts[1];

        if ($prefixo === 'd') {
            $stmt_doc->execute([$destino_id, $id]);
        } elseif ($prefixo === 'p') {
            $stmt_pasta->execute([$destino_id, $id]);
        }
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao mover itens: ' . $e->getMessage()]);
}