<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403);
    die("Acesso negado.");
}
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

// --- CONFIGURAÇÕES ---
$pasta_de_uploads = '../uploads/'; // Caminho para a pasta que criamos

// Verifica se o arquivo foi enviado pelo Dynamsoft
if (!isset($_FILES['RemoteFile'])) {
    http_response_code(400);
    die("Nenhum arquivo recebido.");
}

<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403);
    die("Acesso negado.");
}
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

// --- CONFIGURAÇÕES ---
// O caminho é relativo a ESTE arquivo (upload_scanner.php), então ../ leva para a raiz do projeto.
$pasta_de_uploads = '../uploads/'; 

// Verifica se o arquivo foi enviado pelo Dynamsoft
if (!isset($_FILES['RemoteFile'])) {
    http_response_code(400);
    die("Nenhum arquivo recebido.");
}

$arquivo_temporario = $_FILES['RemoteFile']['tmp_name'];
$nome_original = $_FILES['RemoteFile']['name'];

// Pega os parâmetros da URL
$pasta_id = isset($_GET['pasta_id']) && $_GET['pasta_id'] !== '' ? (int)$_GET['pasta_id'] : null;
$usuario_id = $_SESSION['user_id'];

// Gera um nome de arquivo único e seguro
$extensao = 'pdf'; // O Dynamsoft está configurado para sempre enviar PDF
$novo_nome_arquivo = 'scan_' . uniqid() . '_' . time() . '.' . $extensao;
$caminho_final_servidor = $pasta_de_uploads . $novo_nome_arquivo;
// Caminho que será salvo no banco (relativo à raiz do projeto)
$caminho_para_db = 'uploads/' . $novo_nome_arquivo;


// Tenta mover o arquivo enviado para nossa pasta de uploads
if (move_uploaded_file($arquivo_temporario, $caminho_final_servidor)) {
    try {
        // Busca o ID do tipo de documento "Digitalizado"
        $stmt_tipo = $pdo->prepare("SELECT id FROM tipos_documento WHERE nome = 'Digitalizado' LIMIT 1");
        $stmt_tipo->execute();
        $tipo_doc = $stmt_tipo->fetch();
        $tipo_doc_id = $tipo_doc ? $tipo_doc['id'] : null;

        // Usa o nome original (sem extensão) como título padrão
        $titulo_padrao = pathinfo($nome_original, PATHINFO_FILENAME);
        if(empty($titulo_padrao)) $titulo_padrao = "Documento Digitalizado " . date('d-m-Y');

        // Cria o registro no banco de dados
        $sql = "INSERT INTO documentos (titulo, caminho_arquivo, tipo_documento_id, pasta_id, usuario_id) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo_padrao, $caminho_para_db, $tipo_doc_id, $pasta_id, $usuario_id]);

        // Registra a ação no log
        registrar_log($pdo, $usuario_id, "Digitalizou e enviou o documento '{$titulo_padrao}'.");

        http_response_code(200);
        echo "Upload bem-sucedido!";

    } catch (PDOException $e) {
        // Se der erro no banco, apaga o arquivo físico que foi salvo para não deixar lixo
        unlink($caminho_final_servidor);
        http_response_code(500);
        die("Erro de banco de dados: " . $e->getMessage());
    }
} else {
    http_response_code(500);
    die("Erro ao salvar o arquivo no servidor.");
}
?>