<?php
// helpers/pdf_signer.php - Utilitários para assinatura visual em PDF

class PDFSigner {
    public static function signWithImage(string $srcPdf, string $dstPdf, ?string $imagePath = null, int $page = 1, float $posX = 150, float $posY = 750, float $width = 120, ?string $extraText = null, ?string $qrPath = null): bool {
        // Tenta com FPDI (preferencial)
        try {
            if (!class_exists('setasign\\Fpdi\\Fpdi')) {
                // 1) Tenta via Composer
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                    require_once __DIR__ . '/../vendor/autoload.php';
                }
                // 2) Tenta via libraries/ (instalação manual)
                if (!class_exists('setasign\\Fpdi\\Fpdi')) {
                    $libRoot = dirname(__DIR__) . '/libraries';
                    $candidates = [
                        $libRoot . '/fpdf/fpdf.php',                               // FPDF puro
                        $libRoot . '/tcpdf/tcpdf.php',                              // TCPDF (inclui FPDF API)
                        $libRoot . '/fpdi/src/autoload.php',                        // FPDI autoload
                        $libRoot . '/setasign/fpdi/autoload.php',                   // Outra estrutura comum
                        $libRoot . '/setasign/fpdi/src/autoload.php',              // Variante
                    ];
                    foreach ($candidates as $file) {
                        if (file_exists($file)) { @require_once $file; }
                    }
                }
            }
            if (class_exists('setasign\\Fpdi\\Fpdi')) {
                $fpdiClass = 'setasign\\Fpdi\\Fpdi';
                $pdf = new $fpdiClass();
                $pageCount = $pdf->setSourceFile($srcPdf);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl);
                    if ($i === $page) {
                        // Parâmetros padrão do carimbo profissional
                        $boxW = 200; $boxH = 62; $imgW = 36; $qrSize = 24; $header = [0,123,255];
                        self::drawProfessionalStamp($pdf, $posX, $posY, $boxW, $boxH, $imgW, $qrSize, $header, $imagePath, $extraText, $qrPath);
                    }
                }
                return $pdf->Output($dstPdf, 'F') !== false;
            }
        } catch (\Throwable $e) {
            // cai para fallback
        }

        // Fallback: FPDF simples adicionando uma página com o carimbo
        try {
            if (!class_exists('FPDF')) {
                // 1) Tenta via Composer
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                    require_once __DIR__ . '/../vendor/autoload.php';
                }
                // 2) Tenta via libraries/ (instalação manual)
                if (!class_exists('FPDF')) {
                    $libRoot = dirname(__DIR__) . '/libraries';
                    $candidates = [
                        $libRoot . '/fpdf/fpdf.php',
                        $libRoot . '/tcpdf/tcpdf.php',
                    ];
                    foreach ($candidates as $file) {
                        if (file_exists($file)) { @require_once $file; }
                    }
                }
            }
            if (class_exists('FPDF')) {
                // Fallback: adiciona uma página com um carimbo mais profissional
                $pdf = new FPDF();
                $pdf->AddPage();
                $boxX = 15; $boxY = 220; $boxW = 200; $boxH = 62; $imgW = 36; $qrSize = 24; $header = [0,123,255];
                // Moldura
                $pdf->SetDrawColor(80,80,80);
                $pdf->SetLineWidth(0.2);
                $pdf->Rect($boxX, $boxY, $boxW, $boxH, 'D');
                // Cabeçalho
                $pdf->SetFillColor($header[0],$header[1],$header[2]);
                $pdf->Rect($boxX, $boxY, $boxW, 8, 'F');
                $pdf->SetTextColor(255,255,255);
                $pdf->SetFont('Arial','B',9);
                $pdf->SetXY($boxX+3, $boxY+1.5);
                $pdf->Cell(0,5,'Assinado eletronicamente',0,1);
                // Conteúdo
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('Arial','',8);
                $contentX = $boxX + 3; $contentY = $boxY + 10;
                if ($imagePath && file_exists($imagePath)) {
                    $pdf->Image($imagePath, $contentX, $contentY, $imgW);
                    $contentX += ($imgW + 3);
                }
                $pdf->SetXY($contentX, $contentY);
                $pretty = self::formatExtraText($extraText);
                foreach ($pretty as $line) { $pdf->Cell(0,4,$line,0,1); }
                if ($qrPath && file_exists($qrPath)) {
                    $pdf->Image($qrPath, $boxX + $boxW - $qrSize - 3, $boxY + 10, $qrSize);
                }
                return $pdf->Output('F', $dstPdf) !== false;
            }
        } catch (\Throwable $e) {
            // ignora
        }

        return false;
    }

    /**
     * Carimbo profissional com opções de posicionamento/tamanho.
     * opts:
     * - page: 'first'|'last'|int (padrão: 'last')
     * - position: 'br'|'bl'|'tr'|'tl' (padrão: 'br')
     * - size: 'sm'|'md'|'lg' (padrão: 'md')
     * - margin: float (padrão: 18)
     * - headerColor: [r,g,b] (padrão: [40,167,69])
     * - imagePath, qrPath, title, lines[]
     */
    public static function signWithProfessionalStamp(string $srcPdf, string $dstPdf, array $opts = []): bool {
        $pageSel = $opts['page'] ?? 'last';
        $position = $opts['position'] ?? 'br';
        $size = $opts['size'] ?? 'md';
        $margin = isset($opts['margin']) ? floatval($opts['margin']) : 18.0;
        $header = $opts['headerColor'] ?? [0,123,255]; // azul padrão (tema)
        $style = $opts['style'] ?? 'default'; // 'default' | 'usp'
        $imagePath = $opts['imagePath'] ?? null;
        $qrPath = $opts['qrPath'] ?? null;
        $lines = $opts['lines'] ?? [];
        $title = $opts['title'] ?? 'Assinado eletronicamente';

        // Tenta FPDI
        try {
            if (!class_exists('setasign\\Fpdi\\Fpdi')) {
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) { require_once __DIR__ . '/../vendor/autoload.php'; }
                if (!class_exists('setasign\\Fpdi\\Fpdi')) {
                    $libRoot = dirname(__DIR__) . '/libraries';
                    $candidates = [
                        $libRoot . '/fpdf/fpdf.php',
                        $libRoot . '/tcpdf/tcpdf.php',
                        $libRoot . '/fpdi/src/autoload.php',
                        $libRoot . '/setasign/fpdi/autoload.php',
                        $libRoot . '/setasign/fpdi/src/autoload.php',
                    ];
                    foreach ($candidates as $file) { if (file_exists($file)) { @require_once $file; } }
                }
            }
            if (class_exists('setasign\\Fpdi\\Fpdi')) {
                $pdf = new \setasign\Fpdi\Fpdi();
                $pageCount = $pdf->setSourceFile($srcPdf);

                // Dimensões por tamanho
                if ($size === 'sm') { $boxW=160; $boxH=52; $imgW=28; $qrSize=20; }
                elseif ($size === 'lg') { $boxW=240; $boxH=70; $imgW=42; $qrSize=28; }
                else { $boxW=200; $boxH=62; $imgW=36; $qrSize=24; }

                // Monta extraText a partir de lines
                $extraText = implode("\n", array_map('strval', $lines));

                $targetPage = ($pageSel === 'first') ? 1 : (($pageSel === 'last') ? $pageCount : (int)$pageSel);
                if ($targetPage < 1) $targetPage = 1; if ($targetPage > $pageCount) $targetPage = $pageCount;

                $applyAll = ($pageSel === 'all');
                for ($i=1; $i<= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $sizeInfo = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($sizeInfo['orientation'], [$sizeInfo['width'], $sizeInfo['height']]);
                    $pdf->useTemplate($tpl);

                    if ($applyAll || $i === $targetPage) {
                        // Calcula posição
                        $x = $margin; $y = $margin;
                        if ($position === 'br') { $x = $sizeInfo['width'] - $margin - $boxW; $y = $sizeInfo['height'] - $margin - $boxH; }
                        elseif ($position === 'bl') { $x = $margin; $y = $sizeInfo['height'] - $margin - $boxH; }
                        elseif ($position === 'tr') { $x = $sizeInfo['width'] - $margin - $boxW; $y = $margin; }
                        // Desenha (estilo selecionado)
                        if ($style === 'usp') {
                            self::drawUspStamp($pdf, $x, $y, $boxW, $boxH, $imgW, $qrSize, $header, $imagePath, $extraText, $qrPath, $title);
                        } else {
                            self::drawProfessionalStamp($pdf, $x, $y, $boxW, $boxH, $imgW, $qrSize, $header, $imagePath, $extraText, $qrPath, $title);
                        }
                    }
                }
                return $pdf->Output($dstPdf, 'F') !== false;
            }
        } catch (\Throwable $e) { /* fallback */ }

        // Fallback simples com FPDF (adiciona página)
        try {
            if (!class_exists('FPDF')) {
                if (file_exists(__DIR__ . '/../vendor/autoload.php')) { require_once __DIR__ . '/../vendor/autoload.php'; }
                if (!class_exists('FPDF')) {
                    $libRoot = dirname(__DIR__) . '/libraries';
                    foreach ([$libRoot.'/fpdf/fpdf.php',$libRoot.'/tcpdf/tcpdf.php'] as $f) { if (file_exists($f)) { @require_once $f; } }
                }
            }
            if (class_exists('FPDF')) {
                if ($size === 'sm') { $boxW=160; $boxH=52; $imgW=28; $qrSize=20; }
                elseif ($size === 'lg') { $boxW=240; $boxH=70; $imgW=42; $qrSize=28; }
                else { $boxW=200; $boxH=62; $imgW=36; $qrSize=24; }
                $pdf = new FPDF(); $pdf->AddPage();
                $x = 15; $y = 220; // posição padrão página nova
                $pretty = self::formatExtraText(implode("\n", $lines));
                // Moldura
                $pdf->SetDrawColor(80,80,80); $pdf->SetLineWidth(0.2); $pdf->Rect($x,$y,$boxW,$boxH,'D');
                // Cabeçalho
                $pdf->SetFillColor($header[0],$header[1],$header[2]); $pdf->Rect($x,$y,$boxW,8,'F');
                $pdf->SetTextColor(255,255,255); $pdf->SetFont('Arial','B',9); $pdf->SetXY($x+3,$y+1.5); $pdf->Cell(0,5,$title,0,1);
                // Corpo
                $pdf->SetTextColor(0,0,0); $pdf->SetFont('Arial','',8); $cx=$x+3; $cy=$y+10;
                if ($imagePath && file_exists($imagePath)) { $pdf->Image($imagePath,$cx,$cy,$imgW); $cx+=($imgW+3); }
                $pdf->SetXY($cx,$cy); foreach ($pretty as $line) { $pdf->Cell(0,4,$line,0,1);} 
                if ($qrPath && file_exists($qrPath)) { $pdf->Image($qrPath, $x+$boxW-$qrSize-3, $y+10, $qrSize);} 
                return $pdf->Output('F',$dstPdf) !== false;
            }
        } catch (\Throwable $e) { }
        return false;
    }

    private static function drawProfessionalStamp($pdf, float $posX, float $posY, float $boxW, float $boxH, float $imgW, float $qrSize, array $headerColor, ?string $imagePath, ?string $extraText, ?string $qrPath, string $title = 'Assinado eletronicamente'): void {
        $margin = 3;
        // Moldura
        $pdf->SetDrawColor(80,80,80);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($posX, $posY, $boxW, $boxH, 'D');
        // Cabeçalho
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->Rect($posX, $posY, $boxW, 8, 'F');
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFont('Helvetica','B',9);
        $pdf->SetXY($posX + $margin, $posY + 1.5);
        $pdf->Cell(0,5,$title,0,1);

        // Corpo
        $pdf->SetTextColor(20,20,20);
        $pdf->SetFont('Helvetica','',8);
        $contentX = $posX + $margin; $contentY = $posY + 10;
        // Assinatura (imagem)
        if ($imagePath && file_exists($imagePath)) {
            $pdf->Image($imagePath, $contentX, $contentY, $imgW);
            $contentX += ($imgW + 3);
        }

        // Texto formatado
        $pretty = self::formatExtraText($extraText);
        $pdf->SetXY($contentX, $contentY);
        foreach ($pretty as $line) {
            $pdf->Cell(0,4,$line,0,1);
        }

        // QR dentro da caixa, à direita
        if ($qrPath && file_exists($qrPath)) {
            $qrX = $posX + $boxW - $qrSize - $margin;
            $qrY = $posY + 10;
            $pdf->Image($qrPath, $qrX, $qrY, $qrSize);
        }
    }

    private static function formatExtraText(?string $extraText): array {
        if (!$extraText) return [];
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $extraText)))) ;
        $out = [];
        // Heurística: separar informações em campos mais “bonitos”
        // Ex.: "Assinado digitalmente (ICP-Brasil) por ID #123 em 25/10/2025 14:30"
        //      "Verifique: http://..."
        //      "Código: abc..."
        foreach ($lines as $l) {
            // Encurta URLs longas na visualização do carimbo
            if (stripos($l, 'http') !== false && strlen($l) > 70) {
                $p = strpos($l, 'http');
                $prefix = $p > 0 ? substr($l, 0, $p) : '';
                $url = substr($l, $p);
                $urlShort = substr($url, 0, 60) . '...';
                $l = $prefix . $urlShort;
            }
            $out[] = $l;
        }
        return $out;
    }

    // Estilo "USP" inspirado no exemplo: nome grande à esquerda, mensagem "Verifique ..." central e QR grande à direita
    private static function drawUspStamp($pdf, float $posX, float $posY, float $boxW, float $boxH, float $imgW, float $qrSize, array $headerColor, ?string $imagePath, ?string $extraText, ?string $qrPath, string $title = 'Assinado eletronicamente'): void {
        // Cabeçalho fino
        $pdf->SetDrawColor(80,80,80); $pdf->SetLineWidth(0.2); $pdf->Rect($posX, $posY, $boxW, $boxH, 'D');
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->Rect($posX, $posY, $boxW, 6, 'F');
        $pdf->SetTextColor(255,255,255); $pdf->SetFont('Helvetica','B',8);
        $pdf->SetXY($posX+3, $posY+1); $pdf->Cell(0,4,$title,0,1);

        // Layout em três colunas: esquerda (assinante), centro (texto verificação), direita (QR)
        $leftX = $posX + 4; $topY = $posY + 8;
        $rightX = $posX + $boxW - $qrSize - 4;
        $centerX = $leftX + 90; // largura aproximada para a coluna da esquerda

        // Coluna esquerda: imagem de assinatura (opcional) e nome em destaque
        $pdf->SetTextColor(20,20,20); $pdf->SetFont('Helvetica','B',11);
        if ($imagePath && file_exists($imagePath)) {
            $pdf->Image($imagePath, $leftX, $topY, 40);
            $pdf->SetXY($leftX + 44, $topY + 2);
        } else {
            $pdf->SetXY($leftX, $topY + 2);
        }
        // Primeira linha do extraText é tratada como nome, se existir
        $lines = self::formatExtraText($extraText);
        $nome = $lines[0] ?? '';
        $pdf->Cell(0,5, $nome, 0, 1);

        // Coluna central: "Verifique ..." e URL (encurtada pela formatExtraText)
        $pdf->SetFont('Helvetica','',9);
        $pdf->SetXY($centerX, $topY + 6);
        // Busca linha que contém "Verifique" para destacar
        $verif = null; foreach ($lines as $l) { if (stripos($l, 'verifique') !== false) { $verif = $l; break; } }
        if ($verif) {
            $pdf->Cell(0,4, $verif, 0, 1);
        }
        // Exibe código abreviado se houver
        foreach ($lines as $l) {
            if (stripos($l, 'código:') !== false || stripos($l, 'codigo:') !== false) {
                $pdf->SetXY($centerX, $pdf->GetY() + 1);
                $pdf->Cell(0,4, $l, 0, 1);
                break;
            }
        }

        // Coluna direita: QR grande
        if ($qrPath && file_exists($qrPath)) {
            $pdf->Image($qrPath, $rightX, $topY, $qrSize);
        }
    }
}
