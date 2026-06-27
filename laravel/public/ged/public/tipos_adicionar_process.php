<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// Pega todos os dados do formulário de adicionar
$nome = $_POST['nome'] ?? null;
$pasta_destino = $_POST['pasta_destino'] ?? null;
$codigo = $_POST['codigo'] ?? null;
$separador = $_POST['separador'] ?? '-';
$restrito = isset($_POST['restrito']) ? 1 : 0;
$assinado = isset($_POST['assinado']) ? 1 : 0;
$palavras_chave = $_POST['palavras_chave'] ?? null;
$vencimento_prazo = !empty($_POST['vencimento_prazo']) ? (int)$_POST['vencimento_prazo'] : null;
$vencimento_unidade = $_POST['vencimento_unidade'] ?? null;
$destinacao = $_POST['destinacao'] ?? null;


if (empty(trim($nome))) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'O nome do tipo não pode estar vazio.'];
    header('Location: tipos_adicionar.php');
    exit();
}

try {
    // Consulta INSERT atualizada com os novos campos de vencimento
    $sql = "INSERT INTO tipos_documento (nome, pasta_destino, codigo, separador, restrito, assinado, palavras_chave, vencimento_prazo, vencimento_unidade, destinacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nome,
        $pasta_destino,
        $codigo,
        $separador,
        $restrito,
        $assinado,
        $palavras_chave,
        $vencimento_prazo,
        $vencimento_unidade,
        $destinacao
    ]);
    
    $novo_id = $pdo->lastInsertId();

    registrar_log($pdo, $_SESSION['user_id'], "Criou o tipo de documento '{$nome}' (ID: {$novo_id}).");
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Tipo de documento criado com sucesso!'];
    header('Location: tipos_listar.php');
    exit();

} catch (PDOException $e) {
    die("Erro ao salvar o novo Tipo de Documento: " . $e->getMessage());
}
?>