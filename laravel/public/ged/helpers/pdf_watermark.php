<?php
// helpers/pdf_watermark.php - Aplica marca d'água em PDFs usando FPDI/FPDF

if (!defined('PROJECT_ROOT')) { define('PROJECT_ROOT', dirname(__DIR__)); }

// Carrega FPDF/FPDI da pasta libraries (compatível com o projeto)
require_once PROJECT_ROOT . '/libraries/fpdf/fpdf.php';
require_once PROJECT_ROOT . '/libraries/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

/**
 * Aplica marca d'água texto em todas as páginas do PDF de entrada e grava no output.
 * Retorna true em sucesso.
 */
function aplicar_marcadagua_pdf(string $inputPdf, string $outputPdf, string $texto, float $opacity = 0.15, int $fontSize = 22): bool {
    if (!file_exists($inputPdf)) return false;
    try {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($inputPdf);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Define blend/alpha (FPDF não tem alpha nativo; FPDI+FPDF simples usa workaround leve)
            // Para simplicidade e compatibilidade, usamos cinza claro sem alpha real
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetFont('Arial', 'B', $fontSize);

            // Posiciona texto em diagonal ao centro
            $text = $texto;
            $pdf->SetXY(0, 0);

            // Calcula eixo central
            $centerX = $size['width'] / 2;
            $centerY = $size['height'] / 2;

            // Rotaciona e escreve (usa auxiliar rotate)
            _fpdf_rotate($pdf, 45, $centerX, $centerY);

            // Centraliza aproximadamente
            $pdf->SetXY($centerX - (strlen($text) * $fontSize * 0.12), $centerY);
            $pdf->Cell(0, 10, $text);

            // Remove rotação
            _fpdf_rotate($pdf, 0);
        }

        // Salva
        $pdf->Output($outputPdf, 'F');
        return file_exists($outputPdf) && filesize($outputPdf) > 0;
    } catch (Throwable $e) {
        error_log('Watermark PDF erro: ' . $e->getMessage());
        return false;
    }
}

// Funções auxiliares de rotação para FPDF
function _fpdf_rotate(FPDF $pdf, $angle, $x = -1, $y = -1) {
    if ($x == -1) $x = $pdf->x;
    if ($y == -1) $y = $pdf->y;
    if ($pdf instanceof FPDF) {
        static $rotate = 0;
        if ($rotate != 0)
            $pdf->_out('Q');
        $rotate = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $pdf->k;
            $cy = ($pdf->h - $y) * $pdf->k;
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }
}
