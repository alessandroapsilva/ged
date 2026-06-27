<?php
// public/documentos_combinar_process.php (VERSÃO REFATORADA)
require_once '../core/init.php';
// Este script pode demorar, então aumentamos o tempo limite
set_time_limit(300); 
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$ghostscript_command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'gswin64c' : 'gs';
$ids_com_prefixo = $_POST['ids'] ?? [];
$usuario_id = $_SESSION['user_id'];
$doc_ids = [];

// Filtra para pegar apenas IDs de documentos
foreach ($ids_com_prefixo as $item) {
    if (strpos($item, 'd-') === 0) {
        $doc_ids[] = (int)substr($item, 2);
    }
}

if (count($doc_ids) < 2) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione pelo menos 2 documentos para combinar.']);
    exit();
}

$pdo->beginTransaction();
try {
    $placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, titulo, caminho_arquivo, pasta_id, tipo_documento_id FROM documentos WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
    $stmt->execute($doc_ids);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $arquivos_para_combinar = [];
    $ids_originais = [];
    foreach ($documentos as $doc) {
        $caminho_fisico = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];
        if (file_exists($caminho_fisico)) {
            $arquivos_para_combinar[] = escapeshellarg($caminho_fisico);
            $ids_originais[] = $doc['id'];
        }
    }

    if (count($arquivos_para_combinar) < 2) { throw new Exception("Arquivos físicos para combinação não foram encontrados."); }
    
    // Gera nome e caminho para o novo arquivo combinado
    $novo_titulo = "COMBINADO-" . strtoupper(uniqid());
    $nova_descricao = "Documento criado a partir da combinação dos documentos IDs: " . implode(', ', $ids_originais);
    $novo_nome_arquivo = strtolower($novo_titulo) . '_' . time() . '.pdf';
    $caminho_novo_db = 'storage/uploads/' . $novo_nome_arquivo;
    $caminho_novo_servidor = PROJECT_ROOT . '/public/' . $caminho_novo_db;

    // Comando Ghostscript para combinar
    $comando = sprintf(
        '%s -sDEVICE=pdfwrite -o %s %s',
        $ghostscript_command,
        escapeshellarg($caminho_novo_servidor),
        implode(' ', $arquivos_para_combinar)
    );

    exec($comando . ' 2>&1', $output, $return_var);
    if ($return_var !== 0) { throw new Exception("Falha ao combinar os PDFs com o Ghostscript."); }

    // Insere o novo documento combinado no banco
    $stmt_insert = $pdo->prepare("INSERT INTO documentos (titulo, descricao, caminho_arquivo, tipo_documento_id, pasta_id, usuario_id, data_upload) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt_insert->execute([$novo_titulo, $nova_descricao, $caminho_novo_db, $documentos[0]['tipo_documento_id'], $documentos[0]['pasta_id'], $usuario_id]);

    // Move os documentos originais para a lixeira
    $stmt_delete = $pdo->prepare("UPDATE documentos SET apagado_em = NOW() WHERE id IN ($placeholders)");
    $stmt_delete->execute($doc_ids);

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Documentos combinados com sucesso.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
?>