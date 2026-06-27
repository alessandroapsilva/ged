<?php
// public/ajax_get_metadados_fields.php
require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit();
}

$tipo_id = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : 0;

if ($tipo_id > 0) {
    try {
        // Usa a coluna 'rotulo' para exibir no formulário
        $stmt_campos = $pdo->prepare("SELECT id, rotulo FROM metadado_campos WHERE tipo_documento_id = ? ORDER BY ordem ASC");
        $stmt_campos->execute([$tipo_id]);
        $campos = $stmt_campos->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($campos);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro de banco de dados.']);
    }
} else {
    echo json_encode([]); // Retorna um array vazio se nenhum tipo for selecionado
}