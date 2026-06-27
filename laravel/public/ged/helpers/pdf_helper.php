<?php
require_once __DIR__ . '/../libraries/tcpdf/tcpdf.php';
require_once __DIR__ . '/../libraries/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

function embedSignatureInPdf($filePath, $signatureImageBase64, $signerName, $signatureDate, $ipAddress) {
    try {
        $pdf = new Fpdi();

        // Get the page count
        $pageCount = $pdf->setSourceFile($filePath);

        // Iterate through all pages and import them
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Add signature to the last page
            if ($pageNo == $pageCount) {
                // Decode base64 image
                $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureImageBase64));
                $img = @imagecreatefromstring($signatureImage);
                if ($img !== false) {
                    // Save image temporarily
                    $tempImgPath = tempnam(sys_get_temp_dir(), 'sig') . '.png';
                    imagepng($img, $tempImgPath);

                    // Position at bottom-right
                    $x = $size['width'] - 60; // 60mm from right
                    $y = $size['height'] - 40; // 40mm from bottom

                    // Place signature image
                    $pdf->Image($tempImgPath, $x, $y, 40, 20, 'PNG');

                    // Set font for text
                    $pdf->SetFont('helvetica', '', 8);
                    $pdf->SetTextColor(100, 100, 100);

                    // Place text
                    $pdf->SetXY($x - 10, $y + 22);
                    $pdf->Cell(0, 0, 'Assinado eletronicamente por:', 0, 1);
                    $pdf->SetXY($x - 10, $y + 25);
                    $pdf->Cell(0, 0, $signerName, 0, 1);
                    $pdf->SetXY($x - 10, $y + 28);
                    $pdf->Cell(0, 0, 'Data: ' . date('d/m/Y H:i:s', strtotime($signatureDate)), 0, 1);
                    $pdf->SetXY($x - 10, $y + 31);
                    $pdf->Cell(0, 0, 'IP: ' . $ipAddress, 0, 1);

                    // Clean up temp image
                    unlink($tempImgPath);
                }
            }
        }

        // Overwrite the original file
        $pdf->Output($filePath, 'F');

        return true;
    } catch (Exception $e) {
        // Log error or handle it
        error_log('PDF Stamping Error: ' . $e->getMessage());
        return false;
    }
}
