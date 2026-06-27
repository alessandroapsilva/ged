<?php
// public/documentos_separar_process.php (VERSÃO REFATORADA)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { 
    header('Content-Type: application/json'); echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']); exit();
}

header('Content-Type: application/json');

function parse_page_range(string $range_string): array {
    $pages = []; $parts = explode(',', $range_string);
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, '-') !== false) {
            list($start, $end_range) = explode('-', $part); $start = (int)$start; $end_range = (int)$end_range;
            if ($start > 0 && $end_range >= $start) { $pages = array_merge($pages, range($start, $end_range)); }
        } else { $page = (int)$part; if ($page > 0) { $pages[] = $page; } }
    }
    return array_unique($pages);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']); exit();
}

$ghostscript_command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'gswin64c' : 'gs';

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
$intervalo_str = $_POST['intervalo'] ?? '';
if ($documento_id === 0 || empty($intervalo_str)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']); exit();
}

$usuario_id = $_SESSION['user_id'];
$pdo->beginTransaction();

try {
    $stmt_orig = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
    $stmt_orig->execute([$documento_id]);
    $doc_original = $stmt_orig->fetch(PDO::FETCH_ASSOC);
    if (!$doc_original) { throw new Exception("Documento original não encontrado."); }

    $caminho_arquivo_original = PROJECT_ROOT . '/public/' . $doc_original['caminho_arquivo'];
    if (!file_exists($caminho_arquivo_original)) { throw new Exception("Arquivo físico original não encontrado no servidor."); }
    
    $paginas_a_processar = parse_page_range($intervalo_str);
    if (empty($paginas_a_processar)) { throw new Exception("Nenhum intervalo de página válido foi fornecido."); }
    
    foreach ($paginas_a_processar as $numero_pagina) {
        $identificador_unico = strtoupper(uniqid('SEP-'));
        $novo_titulo = $identificador_unico;
        $nova_descricao = "Documento criado a partir da página {$numero_pagina} do documento original '{$doc_original['titulo']}' (ID: {$doc_original['id']}).";
        
        $info_arquivo = pathinfo($doc_original['caminho_arquivo']);
        $novo_nome_arquivo = strtolower($identificador_unico) . '_' . time() . '.' . ($info_arquivo['extension'] ?? 'pdf');
        
        $caminho_novo_db = 'storage/uploads/' . $novo_nome_arquivo;
        $caminho_novo_servidor = PROJECT_ROOT . '/public/' . $caminho_novo_db;

        $comando = sprintf('%s -sDEVICE=pdfwrite -o %s -dFirstPage=%d -dLastPage=%d %s', $ghostscript_command, escapeshellarg($caminho_novo_servidor), $numero_pagina, $numero_pagina, escapeshellarg($caminho_arquivo_original));
        exec($comando . ' 2>&1', $output, $return_var);

        if ($return_var !== 0) {
            $error_output = implode("\n", $output);
            throw new Exception("Falha ao extrair a página {$numero_pagina}. Detalhes: {$error_output}");
        }
        
        // ✅ INSERT SIMPLIFICADO EM UMA ÚNICA TABELA
        $stmt_insert = $pdo->prepare(
            "INSERT INTO documentos 
                (titulo, descricao, caminho_arquivo, tipo_documento_id, pasta_id, usuario_id, data_upload) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt_insert->execute([
            $novo_titulo, $nova_descricao, $caminho_novo_db,
            $doc_original['tipo_documento_id'], $doc_original['pasta_id'], $usuario_id
        ]);
    }
    
    // ✅ O DOCUMENTO ORIGINAL NÃO É MAIS APAGADO
    
    registrar_log($pdo, $usuario_id, "Separou as páginas '{$intervalo_str}' do documento '{$doc_original['titulo']}'.");

    $pdo->commit();
    
    echo json_encode(['sucesso' => true, 'pasta_id' => $doc_original['pasta_id']]);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    exit();
}
?>