<?php
// public/documentos_adicionar_process.php (Completo com Cálculo de Vencimento)
require_once '../core/init.php';
require_once PROJECT_ROOT . '/vendor/autoload.php'; // Inclui o PDFParser
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/version_helper.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
// Exige permissão e CSRF válido
if (function_exists('require_permission')) { require_permission('document.create'); }
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $titulo = $_POST['titulo'] ?? 'Documento Sem Título';
    $descricao = $_POST['descricao'] ?? null;
    $pasta_id = !empty($_POST['pasta_id']) ? (int)$_POST['pasta_id'] : null;
    $tipo_id = !empty($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : null;
    $metadados = $_POST['meta'] ?? [];
    $query_string = $pasta_id ? '?pasta_id=' . $pasta_id : '';


    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['arquivo'];
        $nome_original = basename($file["name"]);
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

        if ($extensao !== 'pdf') {
             $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Apenas arquivos PDF são permitidos.'];
             header('Location: documentos_adicionar.php' . $query_string);
             exit();
        }

        $nome_unico = uniqid('doc_', true) . '.pdf';
        $caminho_relativo = 'storage/uploads/' . $nome_unico;
        $caminho_servidor = PROJECT_ROOT . '/public/' . $caminho_relativo;
        $dir_uploads = dirname($caminho_servidor);
        if (!is_dir($dir_uploads)) {
            if (!mkdir($dir_uploads, 0777, true) && !is_dir($dir_uploads)) {
                $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao criar diretório de uploads.'];
                header('Location: documentos_adicionar.php' . $query_string);
                exit();
            }
        }

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
                        $unidade_singular = rtrim($unidade, 's');
                        $data_vencimento = date('Y-m-d', strtotime("+{$prazo} {$unidade_singular}"));
                    }
                }
                // ##### FIM DA LÓGICA #####

        $sql = "INSERT INTO documentos (titulo, descricao, caminho_arquivo, usuario_id, pasta_id, tipo_documento_id, hash_arquivo, quantidade_paginas, data_vencimento, data_upload) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $descricao, $caminho_relativo, $_SESSION['user_id'], $pasta_id, $tipo_id, $hash_arquivo, $quantidade_paginas, $data_vencimento]);
                $novo_id = $pdo->lastInsertId();

                // Cria versão inicial apenas se habilitado
                if (defined('ENABLE_VERSIONING') && ENABLE_VERSIONING) {
                    criar_versao_documento($pdo, (int)$novo_id, (int)$_SESSION['user_id'], 'Versão inicial (upload)');
                }

                // Salva os metadados
                if (!empty($metadados) && $novo_id) {
                    $stmt_meta = $pdo->prepare("INSERT INTO documento_metadados (documento_id, campo_id, valor) VALUES (?, ?, ?)");
                    foreach ($metadados as $campo_id => $valor) {
                        if (!empty(trim($valor))) {
                            $stmt_meta->execute([$novo_id, (int)$campo_id, trim($valor)]);
                        }
                    }
                }

                $pdo->commit();
                // Indexa o conteúdo do PDF no índice de busca (melhora a busca tipo eDok)
                try {
                    require_once PROJECT_ROOT . '/vendor/autoload.php';
                    require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';
                    $indexer = new PDFIndexer($pdo);
                    $indexer->indexarDocumento((int)$novo_id);
                } catch (Throwable $e) {
                    error_log('Falha ao indexar documento ' . $novo_id . ': ' . $e->getMessage());
                }

                registrar_log($pdo, $_SESSION['user_id'], "Fez upload do documento '{$titulo}' (ID: {$novo_id}).");
                $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Documento enviado com sucesso!'];
                header('Location: documentos.php' . $query_string);
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                if (file_exists($caminho_servidor)) unlink($caminho_servidor);
                $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao processar o PDF: ' . $e->getMessage()];
                header('Location: documentos_adicionar.php' . $query_string);
                exit();
            }
        } else {
            $err = isset($_FILES['arquivo']['error']) ? (int)$_FILES['arquivo']['error'] : -1;
            $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao enviar arquivo (código ' . $err . '). Verifique permissões de escrita em storage/uploads.'];
            header('Location: documentos_adicionar.php' . $query_string);
            exit();
        }
    }
}
?>