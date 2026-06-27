<?php
// public/documentos_ver.php
require_once '../core/init.php';

// Proteção básica: verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Proibido
    die("Acesso negado. Por favor, faça login.");
}

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($documento_id === 0) {
    http_response_code(400); // Requisição inválida
    die("ID do documento não fornecido.");
}

try {
    // Busca o caminho do arquivo no banco de dados
    $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id = ? AND apagado_em IS NULL");
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        http_response_code(404); // Não encontrado
        die("Documento não encontrado ou está na lixeira.");
    }

    $caminho_relativo = $documento['caminho_arquivo'];
    $caminho_completo = PROJECT_ROOT . '/public/' . $caminho_relativo;

    if (!file_exists($caminho_completo)) {
        http_response_code(404);
        die("Arquivo físico não encontrado no servidor.");
    }
    
    // Cabeçalhos de cache para evitar conteúdo obsoleto após substituição de arquivo
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    $nomeArquivo = basename($caminho_completo);
    // Detecta MIME dinamicamente (fallback por extensão)
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) { $m = finfo_file($f, $caminho_completo); if ($m) $mime = $m; finfo_close($f); }
    } else {
        $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp',
            'txt' => 'text/plain', 'csv' => 'text/csv'
        ];
        if (isset($map[$ext])) $mime = $map[$ext];
    }

    $forceDownload = isset($_GET['download']) && $_GET['download'] == '1';
    $inlineMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'text/plain'];
    $disposition = $forceDownload ? 'attachment' : (in_array($mime, $inlineMimes, true) ? 'inline' : 'attachment');

    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . $nomeArquivo . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . filesize($caminho_completo));

    // Lê e envia o arquivo para o navegador
    readfile($caminho_completo);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    die("Erro de banco de dados: " . $e->getMessage());
}