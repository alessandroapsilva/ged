<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

require_auth();

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id <= 0) { http_response_code(400); exit('ID inválido'); }

try {
    $stmt = $pdo->prepare("SELECT d.id, d.titulo, d.caminho_arquivo, d.hash_arquivo, d.data_upload, u.nome AS proprietario
                            FROM documentos d
                            LEFT JOIN usuarios u ON u.id = d.usuario_id
                            WHERE d.id = ? AND d.apagado_em IS NULL");
    $stmt->execute([$documento_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) { http_response_code(404); exit('Documento não encontrado'); }

    $path = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];
    $hashAtual = file_exists($path) ? hash_file('sha256', $path) : '';
    $ok = ($doc['hash_arquivo'] && $hashAtual && hash_equals($doc['hash_arquivo'], $hashAtual));

    $lines = [];
    $lines[] = 'Comprovante de Integridade - ENFAS GED';
    $lines[] = 'Documento ID: ' . $doc['id'];
    $lines[] = 'Título: ' . ($doc['titulo'] ?? '');
    $lines[] = 'Proprietário: ' . ($doc['proprietario'] ?? '');
    $lines[] = 'Arquivo: ' . $doc['caminho_arquivo'];
    $lines[] = 'SHA-256 arquivado: ' . ($doc['hash_arquivo'] ?? '');
    $lines[] = 'SHA-256 atual: ' . $hashAtual;
    $lines[] = 'Integridade: ' . ($ok ? 'VÁLIDA' : 'INVÁLIDA');
    $lines[] = 'Gerado em: ' . date('Y-m-d H:i:s');

    $txt = implode("\r\n", $lines) . "\r\n";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="comprovante_integridade_' . $doc['id'] . '.txt"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $txt;
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro: ' . $e->getMessage();
}
