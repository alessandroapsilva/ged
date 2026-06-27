<?php
require_once dirname(__DIR__) . '/core/init.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
    exit();
}

$documento_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo_novo = trim(filter_input(INPUT_POST, 'titulo'));
$tipo_documento_id = filter_input(INPUT_POST, 'tipo_documento_id', FILTER_VALIDATE_INT);
$motivo_alteracao = trim(filter_input(INPUT_POST, 'motivo_alteracao'));

if (!$documento_id || empty($titulo_novo)) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID e Título são obrigatórios.']);
    exit();
}
$novo_arquivo_enviado = isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK;
if ($novo_arquivo_enviado && empty($motivo_alteracao)) {
    echo json_encode(['sucesso' => false, 'erro' => 'O motivo da alteração é obrigatório ao subir uma nova versão.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
    $stmt->execute([$documento_id]);
    $doc_atual = $stmt->fetch();

    if (!$doc_atual) { throw new Exception("Documento não encontrado."); }
    
    if ($novo_arquivo_enviado) {
        // Arquiva a versão antiga na tabela 'versoes', que usa a coluna 'titulo'
        $sql_versao = "INSERT INTO versoes (documento_id, versao, titulo, caminho_arquivo, tipo_documento_id, usuario_id, motivo_alteracao, data_upload) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql_versao)->execute([
            $doc_atual['id'], $doc_atual['versao_atual'], $doc_atual['titulo_original'], 
            $doc_atual['caminho_arquivo'], $doc_atual['tipo_documento_id'] ?? null,
            $doc_atual['id_usuario_criador'], $motivo_alteracao, $doc_atual['data_criacao']
        ]);

        $upload_dir = PROJECT_ROOT . '/public/storage/uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $nome_arquivo_novo = uniqid() . '-' . basename($_FILES['arquivo']['name']);
        $caminho_relativo = 'storage/uploads/' . $nome_arquivo_novo;
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $upload_dir . $nome_arquivo_novo)) {
            throw new Exception("Falha ao mover o novo arquivo.");
        }
        
        $nova_versao = $doc_atual['versao_atual'] + 1;
        // Atualiza a tabela 'documentos', que usa a coluna 'titulo_original'
        $sql_update = "UPDATE documentos SET titulo_original = ?, tipo_documento_id = ?, caminho_arquivo = ?, versao_atual = ?, data_criacao = NOW() WHERE id = ?";
        $pdo->prepare($sql_update)->execute([$titulo_novo, $tipo_documento_id, $caminho_relativo, $nova_versao, $documento_id]);

    } else {
        // Atualiza a tabela 'documentos', que usa a coluna 'titulo_original'
        $sql_update = "UPDATE documentos SET titulo_original = ?, tipo_documento_id = ? WHERE id = ?";
        $pdo->prepare($sql_update)->execute([$titulo_novo, $tipo_documento_id, $documento_id]);
    }
    
    $pdo->commit();
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
exit();
?>