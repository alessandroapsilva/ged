<?php
// public/ingest_process.php - Processa upload em lote (PDFs)
require_once '../core/init.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/version_helper.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('require_permission')) { require_permission('document.create'); }
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

$retornarPara = 'documentos.php';
$pasta_id = isset($_GET['pasta_id']) && is_numeric($_GET['pasta_id']) ? (int)$_GET['pasta_id'] : (isset($_POST['pasta_id']) ? (int)$_POST['pasta_id'] : null);
if ($pasta_id) { $retornarPara .= '?pasta_id=' . $pasta_id; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ingest.php'); exit(); }

// Campos comuns
$usar_nome_arquivo = isset($_POST['usar_nome_arquivo']);
$titulo_padrao = trim($_POST['titulo_padrao'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$tipo_id = !empty($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : null;
$metadados = $_POST['meta'] ?? [];
$extrair_texto = isset($_POST['extrair_texto']);

// Valida arquivos
if (!isset($_FILES['arquivos']) || !is_array($_FILES['arquivos']['name']) || count($_FILES['arquivos']['name']) === 0) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Nenhum arquivo enviado.'];
    header('Location: ingest.php');
    exit();
}

$quant_sucesso = 0; $quant_falhas = 0; $erros = [];

$total = count($_FILES['arquivos']['name']);
for ($i = 0; $i < $total; $i++) {
    $nome_original = $_FILES['arquivos']['name'][$i];
    $tmp_name = $_FILES['arquivos']['tmp_name'][$i];
    $erro = $_FILES['arquivos']['error'][$i];
    if ($erro !== UPLOAD_ERR_OK) {
        $quant_falhas++; $erros[] = "$nome_original (erro $erro)"; continue;
    }
    $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') { $quant_falhas++; $erros[] = "$nome_original (não é PDF)"; continue; }

    $titulo = $usar_nome_arquivo ? pathinfo($nome_original, PATHINFO_FILENAME) : ($titulo_padrao ?: pathinfo($nome_original, PATHINFO_FILENAME));

    $nome_unico = uniqid('doc_', true) . '.pdf';
    $rel = 'storage/uploads/' . $nome_unico;
    $abs = PROJECT_ROOT . '/public/' . $rel;
    $dir = dirname($abs);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        $quant_falhas++; $erros[] = "$nome_original (falha ao criar diretório)"; continue;
    }

    if (!move_uploaded_file($tmp_name, $abs)) { $quant_falhas++; $erros[] = "$nome_original (falha ao mover)"; continue; }

    try {
        $pdo->beginTransaction();

        // Páginas e hash
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($abs);
        $paginas = count($pdf->getPages());
        $hash = hash_file('sha256', $abs);

        // Vencimento baseado no tipo
        $data_venc = null;
        if ($tipo_id) {
            $stmt_t = $pdo->prepare('SELECT vencimento_prazo, vencimento_unidade FROM tipos_documento WHERE id = ?');
            $stmt_t->execute([$tipo_id]);
            $ti = $stmt_t->fetch(PDO::FETCH_ASSOC);
            if ($ti && !empty($ti['vencimento_prazo']) && !empty($ti['vencimento_unidade'])) {
                $prazo = (int)$ti['vencimento_prazo'];
                $un = rtrim($ti['vencimento_unidade'], 's');
                $data_venc = date('Y-m-d', strtotime("+{$prazo} {$un}"));
            }
        }

        // Conteúdo extraído (se pedido) – sem OCR, só texto embutido
        $conteudo_ocr = null;
        if ($extrair_texto) {
            try { $conteudo_ocr = trim($pdf->getText()); } catch (\Throwable $e) { $conteudo_ocr = null; }
        }

        $sql = 'INSERT INTO documentos (titulo, descricao, caminho_arquivo, usuario_id, pasta_id, tipo_documento_id, conteudo_ocr, hash_arquivo, quantidade_paginas, data_vencimento, data_upload)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descricao ?: null, $rel, $_SESSION['user_id'], $pasta_id, $tipo_id, $conteudo_ocr, $hash, $paginas, $data_venc]);
        $doc_id = (int)$pdo->lastInsertId();

        if (!empty($metadados)) {
            $stmt_m = $pdo->prepare('INSERT INTO documento_metadados (documento_id, campo_id, valor) VALUES (?, ?, ?)');
            foreach ($metadados as $campo_id => $valor) {
                if ($valor !== null && trim($valor) !== '') {
                    $stmt_m->execute([$doc_id, (int)$campo_id, trim($valor)]);
                }
            }
        }

        if (defined('ENABLE_VERSIONING') && ENABLE_VERSIONING) {
            criar_versao_documento($pdo, $doc_id, (int)$_SESSION['user_id'], 'Versão inicial (ingest)');
        }

        // Indexa para busca
        try {
            require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';
            $indexer = new PDFIndexer($pdo);
            $indexer->indexarDocumento($doc_id);
        } catch (\Throwable $e) { /* loga e segue */ error_log('Indexação falhou no ingest para doc ' . $doc_id . ': ' . $e->getMessage()); }

        $pdo->commit();
        $quant_sucesso++;
        registrar_log($pdo, $_SESSION['user_id'], "Ingest: upload de '{$nome_original}' como '{$titulo}' (ID: {$doc_id}).");
    } catch (\Throwable $e) {
        $pdo->rollBack();
        $quant_falhas++;
        $erros[] = $nome_original . ' (' . $e->getMessage() . ')';
        if (file_exists($abs)) { unlink($abs); }
    }
}

if ($quant_falhas === 0) {
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => "Ingest concluída: {$quant_sucesso} arquivo(s) enviado(s)."];
} else {
    $msg = "Ingest finalizada: {$quant_sucesso} sucesso(s), {$quant_falhas} falha(s).";
    if (!empty($erros)) { $msg .= ' Erros: ' . implode('; ', array_slice($erros, 0, 5)) . (count($erros) > 5 ? '...' : ''); }
    $_SESSION['flash_message'] = ['type' => 'alerta', 'text' => $msg];
}

header('Location: ' . $retornarPara);
exit();
?>
