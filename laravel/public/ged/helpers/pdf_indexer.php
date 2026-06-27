<?php
// helpers/pdf_indexer.php
// Indexador de PDFs usando Smalot\PdfParser (sem dependência de Tesseract/Imagick)

use Smalot\PdfParser\Parser;

class PDFIndexer {
    private PDO $pdo;
    private Parser $parser;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->parser = new Parser();
    }

    public function indexarDocumento(int $documentoId): array {
        // Busca caminho do arquivo
        $stmt = $this->pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id = ? AND apagado_em IS NULL");
        $stmt->execute([$documentoId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc || empty($doc['caminho_arquivo'])) {
            return ['ok' => false, 'erro' => 'Documento não encontrado ou sem caminho'];
        }
        $caminho = __DIR__ . '/../public/' . ltrim($doc['caminho_arquivo'], '/');
        if (!file_exists($caminho)) {
            return ['ok' => false, 'erro' => 'Arquivo não encontrado no disco'];
        }
        try {
            $pdf = $this->parser->parseFile($caminho);
            $texto = $pdf->getText();
            $texto = $this->normalizarTexto($texto);

            $usouOcr = false;
            // Se o texto parecer vazio/pobre e OCR estiver habilitado, tenta OCR completo
            if ((defined('ENABLE_OCR_INDEXING') && ENABLE_OCR_INDEXING) && $this->textoPobre($texto)) {
                $usouOcr = $this->tentarOCR((int)$documentoId, $caminho);
                if ($usouOcr) {
                    // Recarrega do índice gerado pelo OCR (DocumentoOCR já faz update em documentos_indice)
                    $sel = $this->pdo->prepare("SELECT texto_completo FROM documentos_indice WHERE documento_id = ?");
                    $sel->execute([$documentoId]);
                    $row = $sel->fetch(PDO::FETCH_ASSOC);
                    $texto = $row['texto_completo'] ?? $texto;
                }
            }

            // Upsert no índice
            $up = $this->pdo->prepare("UPDATE documentos_indice SET texto_completo = ?, atualizado_em = NOW(), atualizado_por = ? WHERE documento_id = ?");
            $up->execute([$texto, $_SESSION['user_id'] ?? null, $documentoId]);
            if ($up->rowCount() === 0) {
                $ins = $this->pdo->prepare("INSERT INTO documentos_indice (documento_id, texto_completo, atualizado_em, atualizado_por) VALUES (?, ?, NOW(), ?)");
                $ins->execute([$documentoId, $texto, $_SESSION['user_id'] ?? null]);
            }
            return ['ok' => true, 'chars' => strlen($texto), 'ocr' => $usouOcr];
        } catch (Throwable $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    public function indexarTodosDocumentos(): array {
        $stmt = $this->pdo->query("SELECT id FROM documentos WHERE apagado_em IS NULL");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $total = count($ids);
        $ok = 0; $erros = 0; $falhas = [];
        foreach ($ids as $id) {
            $r = $this->indexarDocumento((int)$id);
            if ($r['ok']) { $ok++; } else { $erros++; $falhas[$id] = $r['erro'] ?? 'erro'; }
        }
        return ['total' => $total, 'sucessos' => $ok, 'erros' => $erros, 'falhas' => $falhas];
    }

    private function normalizarTexto(string $t): string {
        $t = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $t);
        $t = preg_replace('/\s+/', ' ', $t);
        return trim((string)$t);
    }

    private function textoPobre(string $t): bool {
        if ($t === '') return true;
        $len = strlen($t);
        $words = preg_match_all('/\w+/u', $t);
        // Heurística simples: muito curto ou poucas palavras
        return ($len < 50) || ($words !== false && $words < 10);
    }

    private function tentarOCR(int $documentoId, string $arquivoPath): bool {
        try {
            require_once __DIR__ . '/../core/documento_ocr.php';
            $usuarioId = $_SESSION['user_id'] ?? 1;
            $ocr = new \DocumentoOCR($this->pdo, $documentoId, $usuarioId);
            return $ocr->processarOCR($arquivoPath) ? true : false;
        } catch (\Throwable $e) {
            error_log('OCR falhou para doc ' . $documentoId . ': ' . $e->getMessage());
            return false;
        }
    }
}
