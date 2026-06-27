<?php
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
if (file_exists(PROJECT_ROOT . '/helpers/csrf_helper.php')) { require_once PROJECT_ROOT . '/helpers/csrf_helper.php'; }

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit();
}
if (function_exists('require_csrf_or_abort') && !csrf_validate()) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'CSRF inválido']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$titulo = trim($_POST['titulo'] ?? '');
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : null;
$tipo_id = isset($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : 0;
$arquivar = isset($_POST['arquivar_original']) && $_POST['arquivar_original'] === 'sim';

if ($id <= 0 || $titulo === '' || $tipo_id <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Campos obrigatórios ausentes']);
    exit();
}

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT caminho_arquivo FROM documentos WHERE id = ? AND apagado_em IS NULL');
    $st->execute([$id]);
    $doc = $st->fetch(PDO::FETCH_ASSOC);
    if (!$doc) { throw new Exception('Documento não encontrado'); }

    $sqlParts = ['titulo = ?', 'descricao = ?', 'tipo_documento_id = ?'];
    $params = [$titulo, $descricao, $tipo_id];

    // Upload opcional
    if (!empty($_FILES['arquivo']) && is_array($_FILES['arquivo']) && (int)$_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf','jpg','jpeg','png','gif'];
        $name = $_FILES['arquivo']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) { throw new Exception('Extensão não suportada'); }
        $dir = PROJECT_ROOT . '/public/storage/uploads';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $newBase = uniqid('doc_', true) . '.' . $ext;
        $target = $dir . '/' . $newBase;
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $target)) { throw new Exception('Falha no upload'); }
        $relPath = 'storage/uploads/' . $newBase;
        $hash = @hash_file('sha256', $target) ?: null;
        $pages = null;
        if ($ext === 'pdf') {
            try {
                if (!class_exists('Smalot\\PdfParser\\Parser')) { @require_once PROJECT_ROOT . '/vendor/autoload.php'; }
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($target);
                $pages = count($pdf->getPages());
            } catch (Throwable $e) { $pages = null; }
        }
        $sqlParts[] = 'caminho_arquivo = ?';
        $params[] = $relPath;
        $sqlParts[] = 'hash_arquivo = ?';
        $params[] = $hash;
        $sqlParts[] = 'quantidade_paginas = ?';
        $params[] = $pages;
        // Invalida assinaturas no replace de arquivo
        $sqlParts[] = 'assinado = 0';
        $sqlParts[] = 'data_assinatura = NULL';
        $sqlParts[] = 'assinado_por = NULL';

        // Arquivar ou remover original
        $old = $doc['caminho_arquivo'] ?? null;
        if ($old) {
            $oldAbs = PROJECT_ROOT . '/public/' . $old;
            if (file_exists($oldAbs)) {
                if ($arquivar) {
                    $destDir = PROJECT_ROOT . '/public/storage/arquivados';
                    if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                    @rename($oldAbs, $destDir . '/orig_' . date('Ymd_His') . '_' . basename($oldAbs));
                } else {
                    @unlink($oldAbs);
                }
            }
        }
    }

    $sql = 'UPDATE documentos SET ' . implode(', ', $sqlParts) . ', atualizado_em = NOW() WHERE id = ?';
    $params[] = $id;
    $upd = $pdo->prepare($sql);
    $upd->execute($params);

    // Metadados (opcional)
    if (!empty($_POST['meta']) && is_array($_POST['meta'])) {
        try {
            foreach ($_POST['meta'] as $campoId => $valor) {
                $campoId = (int)$campoId;
                $valor = (string)$valor;
                $stc = $pdo->prepare('SELECT id FROM documento_metadados WHERE documento_id = ? AND campo_id = ?');
                $stc->execute([$id, $campoId]);
                $exist = $stc->fetch(PDO::FETCH_ASSOC);
                if ($exist) {
                    $pdo->prepare('UPDATE documento_metadados SET valor = ? WHERE id = ?')->execute([$valor, (int)$exist['id']]);
                } else {
                    $pdo->prepare('INSERT INTO documento_metadados (documento_id, campo_id, valor) VALUES (?,?,?)')->execute([$id, $campoId, $valor]);
                }
            }
        } catch (Throwable $e) {
            // não falha por metadados
            error_log('Falha meta inline doc '.$id.': '.$e->getMessage());
        }
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
exit();
