<?php
// public/documentos_combinar.php (Com a correção do FPDF)
require_once '../core/init.php';

// ##### LINHA ADICIONADA PARA CORRIGIR O ERRO FATAL #####
// Carrega a classe FPDF base, que é uma dependência do FPDI.
require_once PROJECT_ROOT . '/libraries/fpdf/fpdf.php';

// Carrega o FPDI que você já tem
require_once PROJECT_ROOT . '/libraries/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

// Validação inicial (sem alterações)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['doc_ids']) || !is_array($_POST['doc_ids'])) {
    die("Requisição inválida ou nenhum documento selecionado.");
}

try {
    $ids = array_map('intval', $_POST['doc_ids']);
    if (count($ids) < 2) {
        die("Selecione pelo menos dois documentos para combinar.");
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT caminho_arquivo FROM documentos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($files)) {
        die("Nenhum arquivo válido encontrado para os documentos selecionados.");
    }

    $pdf = new Fpdi();

    foreach ($files as $file) {
        $filePath = PROJECT_ROOT . '/uploads/' . $file;
        if (file_exists($filePath)) {
            $pageCount = $pdf->setSourceFile($filePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }
    }

    $nome_arquivo = 'documento_combinado_' . date('Y-m-d_H-i') . '.pdf';
    $pdf->Output('D', $nome_arquivo);
    exit;

} catch (Exception $e) {
    die('Erro ao combinar os documentos: ' . $e->getMessage());
}