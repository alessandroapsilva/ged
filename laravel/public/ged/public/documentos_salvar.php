<?php
require_once '../core/init.php';
require_once '../helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/version_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (function_exists('require_permission')) { require_permission('document.create'); }
    if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }
    
    $titulo = trim($_POST['titulo']);
    $tipo_documento_id = $_POST['tipo_documento_id'];
    $usuario_id = $_SESSION['user_id'];
    
    // LINHA CORRIGIDA AQUI:
    // Se 'pasta_id' não estiver vazio, converte para inteiro. Se estiver vazio, se torna NULL.
    $pasta_id = !empty($_POST['pasta_id']) ? (int)$_POST['pasta_id'] : null;

    if (isset($_FILES['documento_pdf']) && $_FILES['documento_pdf']['error'] === UPLOAD_ERR_OK) {

        $file = $_FILES['documento_pdf'];
        $nome_arquivo_original = $file['name'];

        if ($file['type'] !== 'application/pdf') {
            header("Location: documentos_adicionar.php?erro=tipo_invalido");
            exit();
        }

        $extensao = pathinfo($nome_arquivo_original, PATHINFO_EXTENSION);
        $nome_arquivo_sistema = uniqid('doc_', true) . '.' . $extensao;
    $caminho_relativo = 'storage/uploads/' . $nome_arquivo_sistema;
    $caminho_destino = PROJECT_ROOT . '/public/' . $caminho_relativo;

        if (move_uploaded_file($file['tmp_name'], $caminho_destino)) {
            
            try {
                $hash_arquivo = hash_file('sha256', $caminho_destino);
                $sql = "INSERT INTO documentos (titulo, tipo_documento_id, pasta_id, usuario_id, nome_arquivo_original, nome_arquivo_sistema, caminho_arquivo, hash_arquivo, data_upload) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $titulo,
                    $tipo_documento_id,
                    $pasta_id, // Agora envia NULL corretamente quando estiver na Raiz
                    $usuario_id,
                    $nome_arquivo_original,
                    $nome_arquivo_sistema,
                    $caminho_relativo,
                    $hash_arquivo
                ]);

                $novo_doc_id = $pdo->lastInsertId();
                // Cria versão inicial apenas se habilitado
                if (defined('ENABLE_VERSIONING') && ENABLE_VERSIONING) {
                    criar_versao_documento($pdo, (int)$novo_doc_id, (int)$usuario_id, 'Versão inicial (upload)');
                }
                // Indexa
                try {
                    require_once PROJECT_ROOT . '/vendor/autoload.php';
                    require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';
                    $indexer = new PDFIndexer($pdo);
                    $indexer->indexarDocumento((int)$novo_doc_id);
                } catch (Throwable $e) {
                    error_log('Falha ao indexar documento ' . $novo_doc_id . ': ' . $e->getMessage());
                }
                registrar_log($pdo, $usuario_id, "Fez upload do documento '{$titulo}' (ID: {$novo_doc_id}).");

                // Redireciona para a pasta correta com mensagem de sucesso
                $redirect_url = "documentos_listar.php?sucesso=upload";
                if ($pasta_id !== null) {
                    $redirect_url .= "&pasta_id=" . $pasta_id;
                }
                header("Location: " . $redirect_url);
                exit();

            } catch (PDOException $e) {
                if (file_exists($caminho_destino)) { unlink($caminho_destino); }
                header("Location: documentos_adicionar.php?erro=db");
                exit();
            }
        } else {
            header("Location: documentos_adicionar.php?erro=mover");
            exit();
        }
    } else {
        header("Location: documentos_adicionar.php?erro=arquivo");
        exit();
    }
}
?>