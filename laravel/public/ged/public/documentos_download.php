<?php
// public/documentos_download.php
require_once '../core/init.php';

$versao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($versao_id === 0) {
    die('ID inválido.');
}

$stmt = $pdo->prepare("SELECT caminho_arquivo, nome_arquivo_original FROM documento_versoes WHERE id = ?");
$stmt->execute([$versao_id]);
$versao_documento = $stmt->fetch();

if (!$versao_documento) {
    die('Documento não encontrado.');
}

$caminho_fisico = __DIR__ . '/' . $versao_documento['caminho_arquivo'];

if (!file_exists($caminho_fisico)) {
    die('Arquivo físico não encontrado.');
}

$nome_arquivo = $versao_documento['nome_arquivo_original'];
$content_type = 'application/octet-stream'; // Tipo genérico para forçar download

header('Content-Type: ' . $content_type);
// A MÁGICA ESTÁ AQUI: 'attachment' força o download
header('Content-Disposition: attachment; filename="' . basename($nome_arquivo) . '"');
header('Content-Length: ' . filesize($caminho_fisico));

ob_clean();
flush();

readfile($caminho_fisico);
exit();
?>