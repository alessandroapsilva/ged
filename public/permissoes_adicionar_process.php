<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    exit('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $chave = trim($_POST['chave']);
    $descricao = trim($_POST['descricao']);

    if (empty($nome) || empty($chave)) {
        die('Erro: Nome e Chave são obrigatórios.');
    }
    
    // Valida o formato da chave
    if (!preg_match('/^[a-z0-9_.]+$/', $chave)) {
        die('Erro: A "Chave" só pode conter letras minúsculas, números, ponto (.) e underscore (_).');
    }

    try {
        $sql = "INSERT INTO permissoes (nome, chave, descricao) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $chave, $descricao]);
        
        header('Location: funcoes_listar.php?sucesso=permissao_add');
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die('Erro: Já existe uma permissão com esta "Chave". Ela deve ser única.');
        } else {
            die('Erro ao salvar no banco de dados: ' . $e->getMessage());
        }
    }
}
?>