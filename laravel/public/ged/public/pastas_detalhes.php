<?php
require_once '../core/init.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.'])); }

if (!isset($_GET['id']) || empty($_GET['id'])) { http_response_code(400); exit(json_encode(['sucesso' => false, 'mensagem' => 'ID da pasta não fornecido.'])); }
$id = $_GET['id'];

try {
    // Busca os dados da pasta
    $sql = "SELECT * FROM pastas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $pasta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pasta) {
        // Conta subpastas
        $count_pastas_sql = "SELECT COUNT(*) as total FROM pastas WHERE pasta_pai_id = ?";
        $count_pastas_stmt = $pdo->prepare($count_pastas_sql);
        $count_pastas_stmt->execute([$id]);
        $pasta['total_subpastas'] = $count_pastas_stmt->fetchColumn();

        // Conta documentos
        $count_docs_sql = "SELECT COUNT(*) as total FROM documentos WHERE pasta_id = ?";
        $count_docs_stmt = $pdo->prepare($count_docs_sql);
        $count_docs_stmt->execute([$id]);
        $pasta['total_documentos'] = $count_docs_stmt->fetchColumn();
        
        $pasta['data_criacao_formatada'] = date('d/m/Y H:i:s', strtotime($pasta['data_criacao']));

        echo json_encode(['sucesso' => true, 'dados' => $pasta]);
    } else {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Pasta não encontrada.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados.']);
}
?>