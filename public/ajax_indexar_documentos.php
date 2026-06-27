<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403);
    exit(json_encode(['erro' => 'Não autorizado']));
}
require_once '../core/init.php';
require_once '../helpers/auth_helper.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';

if (!usuario_tem_permissao('admin.access')) {
    http_response_code(403);
    exit(json_encode(['erro' => 'Permissão negada']));
}

try {
    $indexer = new PDFIndexer($pdo);
    $resultados = $indexer->indexarTodosDocumentos();
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => "Indexação concluída: {$resultados['sucessos']} documentos indexados, {$resultados['erros']} erros de {$resultados['total']} total.",
        'detalhes' => $resultados['falhas']
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro na indexação: ' . $e->getMessage()
    ]);
}
?>