<?php
// public/digitalizar_processar.php (Completo com Cálculo de Vencimento)
require_once '../core/init.php';
require_once PROJECT_ROOT . '/vendor/autoload.php'; // Inclui o PDFParser

header('Content-Type: application/json');

$response = ['sucesso' => false, 'mensagem' => 'Acesso negado.'];

if (isset($_SESSION['user_id']) && isset($_FILES['documento_digitalizado'])) {
    
    $file = $_FILES['documento_digitalizado'];
    $ocr_text = $_POST['conteudo_ocr'] ?? null;
    $titulo = $_POST['titulo'] ?? ('Documento Digitalizado ' . date('d-m-Y H:i:s'));
    $tipo_id = !empty($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : null;
    $metadados = $_POST['meta'] ?? [];
    $captura_modo = $_POST['captura_modo'] ?? 'desconhecido'; // webtwain | upload_manual
    $scanner_nome = $_POST['scanner_nome'] ?? null;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['mensagem'] = 'Erro no upload do arquivo do scanner.';
        echo json_encode($response);
        exit;
    }

    $nome_unico = uniqid('scan_', true) . '.pdf';
    $caminho_relativo = 'storage/uploads/' . $nome_unico;
    $caminho_servidor = PROJECT_ROOT . '/public/' . $caminho_relativo;

    if (move_uploaded_file($file["tmp_name"], $caminho_servidor)) {
        $pdo->beginTransaction();
        try {
            // Contagem de Páginas
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($caminho_servidor);
            $quantidade_paginas = count($pdf->getPages());
            
            // Chave de Integridade
            $hash_arquivo = hash_file('sha256', $caminho_servidor);

            // ##### LÓGICA DE CÁLCULO DE VENCIMENTO #####
            $data_vencimento = null;
            if ($tipo_id) {
                $stmt_tipo = $pdo->prepare("SELECT vencimento_prazo, vencimento_unidade FROM tipos_documento WHERE id = ?");
                $stmt_tipo->execute([$tipo_id]);
                $tipo_info = $stmt_tipo->fetch();
                if ($tipo_info && !empty($tipo_info['vencimento_prazo']) && !empty($tipo_info['vencimento_unidade'])) {
                    $prazo = (int)$tipo_info['vencimento_prazo'];
                    $unidade = $tipo_info['vencimento_unidade']; // ex: 'Anos', 'Meses', 'Dias'
                    // Remove o 's' do final para o strtotime (ex: Anos -> Ano)
                    $unidade_singular = rtrim($unidade, 's'); 
                    $data_vencimento = date('Y-m-d', strtotime("+{$prazo} {$unidade_singular}"));
                }
            }
            // ##### FIM DA LÓGICA #####

            // SQL atualizado com data_vencimento
        $sql = "INSERT INTO documentos (titulo, caminho_arquivo, usuario_id, tipo_documento_id, conteudo_ocr, hash_arquivo, quantidade_paginas, data_vencimento, data_upload) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titulo, $caminho_relativo, $_SESSION['user_id'], $tipo_id, $ocr_text, $hash_arquivo, $quantidade_paginas, $data_vencimento]);
            $novo_id = $pdo->lastInsertId();

            // Insere os metadados (se houver)
            if (!empty($metadados) && $novo_id) {
                $stmt_meta = $pdo->prepare("INSERT INTO documento_metadados (documento_id, campo_id, valor) VALUES (?, ?, ?)");
                foreach ($metadados as $campo_id => $valor) {
                    if (!empty(trim($valor))) {
                        $stmt_meta->execute([$novo_id, (int)$campo_id, trim($valor)]);
                    }
                }
            }

            $pdo->commit();
            registrar_log($pdo, $_SESSION['user_id'], "Digitalizou e salvou o documento '{$titulo}' (ID: {$novo_id}).");

            // Registro avançado de auditoria (tabela opcional digitalizacoes_log)
            try {
                $stmtLog = $pdo->prepare("INSERT INTO digitalizacoes_log (documento_id, usuario_id, modo, scanner_nome, paginas, tem_ocr, tamanho_bytes, ip_origem, criado_em) VALUES (?,?,?,?,?,?,?,?,NOW())");
                $tamanho_bytes = filesize($caminho_servidor);
                $tem_ocr = !empty($ocr_text) ? 1 : 0;
                $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $stmtLog->execute([$novo_id, $_SESSION['user_id'], $captura_modo, $scanner_nome, $quantidade_paginas, $tem_ocr, $tamanho_bytes, $ip]);
            } catch (Exception $eLog) {
                // Silencia se tabela não existir
                error_log('Aviso: não foi possível registrar digitalizacoes_log: ' . $eLog->getMessage());
            }
            
            $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Documento digitalizado salvo com sucesso!'];
            $response = ['sucesso' => true];

        } catch (Exception $e) { // Captura erros do PDFParser também
            $pdo->rollBack();
            if (file_exists($caminho_servidor)) unlink($caminho_servidor);
            $response['mensagem'] = 'Erro ao processar o PDF: ' . $e->getMessage();
            error_log('Erro em digitalizar_processar.php: ' . $e->getMessage());
        }
    } else {
        $response['mensagem'] = 'Falha ao salvar o arquivo fisicamente no servidor.';
    }
}

echo json_encode($response);
?>