<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('require_permission')) { require_permission('ingest.admit'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ingest.php'); exit(); }

$st = $pdo->prepare('SELECT * FROM ingest_arquivos WHERE id = ?');
$st->execute([$id]);
$arq = $st->fetch(PDO::FETCH_ASSOC);
if (!$arq) { $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Item não encontrado.']; header('Location: ingest.php'); exit(); }

$abs = PROJECT_ROOT . '/public/' . $arq['caminho_relativo'];
if (!file_exists($abs)) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Arquivo físico não encontrado.'];
    header('Location: ingest.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // Preparar dados do documento a partir do arquivo
    $titulo = pathinfo($arq['nome_original'], PATHINFO_FILENAME);
    $descricao = null;
    $pasta_id = null; // Raiz por padrão; pode ser ajustado para uma pasta específica
    $tipo_id = null;  // Sem tipo; pode ser definido depois

    // Mover arquivo para storage/uploads (documentos)
    $nome_unico = uniqid('doc_', true) . '.pdf';
    $destRel = 'storage/uploads/' . $nome_unico;
    $destAbs = PROJECT_ROOT . '/public/' . $destRel;
    $dir = dirname($destAbs);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Falha ao criar diretório de uploads.');
    }
    if (!@copy($abs, $destAbs)) {
        throw new RuntimeException('Falha ao copiar para uploads.');
    }

    // Páginas, hash e extração simples
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($destAbs);
    $paginas = count($pdf->getPages());
    $hash = hash_file('sha256', $destAbs);
    $conteudo_ocr = null;
    try { $conteudo_ocr = trim($pdf->getText()); } catch (\Throwable $e) { $conteudo_ocr = null; }

    $sql = "INSERT INTO documentos (titulo, descricao, caminho_arquivo, usuario_id, pasta_id, tipo_documento_id, conteudo_ocr, hash_arquivo, quantidade_paginas, data_upload) VALUES (?,?,?,?,?,?,?,?,?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titulo, $descricao, $destRel, $_SESSION['user_id'], $pasta_id, $tipo_id, $conteudo_ocr, $hash, $paginas]);
    $docId = (int)$pdo->lastInsertId();

    // Atualiza ingest
    $stUp = $pdo->prepare('UPDATE ingest_arquivos SET status = "admitido", admitido_em = NOW(), documento_id = ? WHERE id = ?');
    $stUp->execute([$docId, $id]);

    $pdo->commit();
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Arquivo admitido como documento #' . $docId . '.'];
} catch (Throwable $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao admitir: ' . $e->getMessage()];
}

header('Location: ingest.php');
exit();
?>
