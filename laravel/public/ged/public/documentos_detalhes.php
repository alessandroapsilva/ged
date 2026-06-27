<?php
// Proteção e conexão
require_once '../core/init.php';
header('Content-Type: application/json'); // Informa que a resposta será em formato JSON

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do documento não fornecido.']);
    exit();
}
$id = $_GET['id'];

try {
    // Usamos um JOIN para buscar também o nome do usuário e do tipo
    $sql = "SELECT d.*, u.nome as usuario_nome, t.nome as tipo_nome
            FROM documentos d
            JOIN usuarios u ON d.usuario_id = u.id
            JOIN tipos_documento t ON d.tipo_documento_id = t.id
            WHERE d.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($documento) {
        // Formata a data para o padrão brasileiro antes de enviar
        $documento['data_upload_formatada'] = date('d/m/Y H:i:s', strtotime($documento['data_upload']));
        echo json_encode(['sucesso' => true, 'dados' => $documento]);
    } else {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Documento não encontrado.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados.']);
}
?>