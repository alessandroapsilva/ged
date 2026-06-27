<?php
require_once '../core/init.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Pega os dados do formulário
    $nome_pasta = $_POST['nome'] ?? '';
    $pasta_pai_id = $_POST['pasta_pai_id'] ?? null;

    // Validação simples para garantir que o nome não está vazio
    if (empty(trim($nome_pasta))) {
        // Se o nome estiver vazio, redireciona de volta com um erro (opcional)
        header('Location: documentos.php' . ($pasta_pai_id ? '?pasta_id=' . $pasta_pai_id : ''));
        exit();
    }

    // Converte '0' ou string vazia para NULL para o banco de dados
    if (empty($pasta_pai_id)) {
        $pasta_pai_id = null;
    }

    try {
        // Prepara e executa a inserção no banco de dados
        $stmt = $pdo->prepare("INSERT INTO pastas (nome, pasta_pai_id) VALUES (?, ?)");
        $stmt->execute([$nome_pasta, $pasta_pai_id]);

        // Redireciona de volta para a pasta onde o usuário estava
        header('Location: documentos.php' . ($pasta_pai_id ? '?pasta_id=' . $pasta_pai_id : ''));
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, exibe uma mensagem (em um ambiente de produção, seria melhor registrar o erro)
        die("Erro ao criar a pasta: " . $e->getMessage());
    }
} else {
    // Se alguém tentar acessar o arquivo diretamente, redireciona para a página principal
    header('Location: documentos.php');
    exit();
}