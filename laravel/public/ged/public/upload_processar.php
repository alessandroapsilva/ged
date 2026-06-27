<?php
// public/upload_processar.php (VERSÃO FINAL PARA 2 TABELAS)
require_once '../core/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['arquivo'])) {
    die('Acesso inválido.');
}

// 1. CAPTURA DOS DADOS DO FORMULÁRIO
$titulo_original = trim($_POST['titulo_original']);
$tipo_documento_id = $_POST['tipo_documento_id'];
$pasta_id = ($_POST['pasta_id'] !== '' && is_numeric($_POST['pasta_id'])) ? (int)$_POST['pasta_id'] : null;
$arquivo = $_FILES['arquivo'];
$id_usuario = $_SESSION['user_id'];
$documento_pai_id = null;
$nova_versao = 1;

// 2. LÓGICA DE VERSIONAMENTO PARA 2 TABELAS
// Procura na tabela "mãe" se um documento com este título já existe
$stmt = $pdo->prepare("SELECT * FROM documentos WHERE titulo_original = ?");
$stmt->execute([$titulo_original]);
$documento_pai = $stmt->fetch();

if ($documento_pai) {
    // Se o documento "mãe" JÁ EXISTE, pegamos o ID dele
    $documento_pai_id = $documento_pai['id'];

    // E descobrimos qual será o número da nova versão
    $stmt_versao = $pdo->prepare("SELECT MAX(versao) as max_v FROM documento_versoes WHERE documento_id = ?");
    $stmt_versao->execute([$documento_pai_id]);
    $ultima_versao = $stmt_versao->fetchColumn();
    $nova_versao = $ultima_versao + 1;

} else {
    // Se NÃO EXISTE, criamos o registro "mãe" primeiro
    $stmt_pai = $pdo->prepare("INSERT INTO documentos (titulo_original, id_usuario_criador) VALUES (?, ?)");
    $stmt_pai->execute([$titulo_original, $id_usuario]);
    $documento_pai_id = $pdo->lastInsertId(); // Pegamos o ID do pai que acabamos de criar
}

// 3. PROCESSAMENTO DO ARQUIVO
$nome_arquivo_original = $arquivo['name'];
$extensao = pathinfo($nome_arquivo_original, PATHINFO_EXTENSION);
$nome_arquivo_sistema = uniqid('doc_v'.$nova_versao.'_', true) . '.' . $extensao;
$caminho_destino = '../uploads/' . $nome_arquivo_sistema;

if (!move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
    die('Erro ao mover o arquivo.');
}

// 4. INSERÇÃO NA TABELA DE VERSÕES ('documento_versoes')
$sql = "INSERT INTO documento_versoes 
            (documento_id, versao, titulo, pasta_id, tipo_documento_id, nome_arquivo_original, nome_arquivo_sistema, caminho_arquivo, 
             tamanho_arquivo, status, data_processamento, usuario_id, apagado_em) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
        
$stmt_versao_insert = $pdo->prepare($sql);

try {
    $stmt_versao_insert->execute([
        $documento_pai_id,
        $nova_versao,
        $titulo_original, // O título da versão pode ser o mesmo do pai inicialmente
        $pasta_id,
        $tipo_documento_id,
        $nome_arquivo_original,
        $nome_arquivo_sistema,
        $caminho_destino,
        $arquivo['size'],
        'admitido', // status inicial
        $id_usuario,
        null // apagado_em
    ]);

    $_SESSION['mensagem_sucesso'] = "Documento '{$titulo_original}' (Versão {$nova_versao}) enviado com sucesso!";
    $redirect_url = 'documentos_listar.php' . ($pasta_id !== null ? '?pasta_id=' . $pasta_id : '');
    header('Location: ' . $redirect_url);
    exit();

} catch (PDOException $e) {
    die("Erro ao salvar a versão do documento: " . $e->getMessage());
}
?>