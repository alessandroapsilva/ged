<?php
// helpers/convert_helper.php
// Converte arquivos para PDF quando possível (Windows/Linux), com best-effort.
// Estratégias:
// - Imagens (jpg/jpeg/png/gif/webp): converte para PDF via FPDF (sem dependências externas)
// - Office (doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp): tenta LibreOffice headless (soffice)
// Configs por env:
// - GED_SOFFICE_PATH: caminho para o executável do LibreOffice (ex.: C:\\Program Files\\LibreOffice\\program\\soffice.exe)

if (!function_exists('convert_to_pdf_if_needed')) {
    function convert_to_pdf_if_needed(string $srcPath, string $ext, string $outPath): bool {
        $ext = strtolower($ext);
        if ($ext === 'pdf') { return copy($srcPath, $outPath); }
        // 1) imagens via FPDF
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            try {
                require_once __DIR__ . '/../libraries/fpdf/fpdf.php';
                $pdf = new FPDF('P','mm','A4');
                $pdf->AddPage();
                // Calcula ajuste mantendo proporção
                [$w,$h] = getimagesize($srcPath);
                $maxW = 190; $maxH = 277; // margens 10mm
                $ratio = min($maxW/$w, $maxH/$h);
                $newW = $w * $ratio; $newH = $h * $ratio;
                $x = (210 - $newW)/2; $y = (297 - $newH)/2; // centraliza
                $pdf->Image($srcPath, $x, $y, $newW, $newH);
                return (bool)$pdf->Output('F', $outPath);
            } catch (Throwable $e) {
                return false;
            }
        }
        // 2) Office via LibreOffice headless (se configurado)
        $soffice = getenv('GED_SOFFICE_PATH');
        if (!$soffice || !file_exists($soffice)) {
            // tenta encontrar pelo PATH (Linux) ou defaults do Windows
            $candidates = [];
            if (stripos(PHP_OS, 'WIN') === 0) {
                $candidates = [
                    'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                    'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                ];
            } else {
                $candidates = ['soffice'];
            }
            foreach ($candidates as $cand) {
                if ($cand === 'soffice') { $soffice = 'soffice'; break; }
                if (file_exists($cand)) { $soffice = $cand; break; }
            }
        }
        // Executa conversão se tiver executável
        if ($soffice) {
            $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ged_conv_' . (function_exists('random_bytes')?bin2hex(random_bytes(6)):uniqid());
            if (!@mkdir($tmpDir) && !is_dir($tmpDir)) { $tmpDir = sys_get_temp_dir(); }
            // Comando headless para exportar para PDF na pasta temporária
            $cmd = '"' . $soffice . '" --headless --convert-to pdf --outdir ' . escapeshellarg($tmpDir) . ' ' . escapeshellarg($srcPath);
            // Executa e espera terminar (timeout simples)
            @exec($cmd, $out, $ret);
            // Procura PDF gerado no tmpDir
            $basename = pathinfo($srcPath, PATHINFO_FILENAME);
            $generated = $tmpDir . DIRECTORY_SEPARATOR . $basename . '.pdf';
            if (!file_exists($generated)) {
                // fallback: procura qualquer .pdf criado
                $cands = glob($tmpDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
                if (!empty($cands)) { $generated = $cands[0]; }
            }
            if (file_exists($generated)) {
                $ok = @copy($generated, $outPath);
                // limpeza best-effort
                @unlink($generated);
                @rmdir($tmpDir);
                return $ok;
            }
        }
        return false;
    }
}
