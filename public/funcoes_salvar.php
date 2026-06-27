<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../helpers/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Coleta dos dados do formulário
    $nome_funcao = trim($_POST['nome_funcao']);
    $chave = trim($_POST['chave']);
    $descricao = trim($_POST['descricao']);
    $nivel = (int)$_POST['nivel'];
    // Permissões vêm como um array de IDs
    $permissoes = isset($_POST['permissoes']) ? $_POST['permissoes'] : [];

    if (empty($nome_funcao) || empty($chave)) {
        header('Location: funcoes_adicionar.php?erro=campos_vazios');
        exit();
    }

    try {
        // Inicia a transação "tudo ou nada"
        $pdo->beginTransaction();

        // Passo 1: Insere a nova função na tabela 'funcoes'
        $sql_funcao = "INSERT INTO funcoes (nome_funcao, chave, descricao, nivel) VALUES (?, ?, ?, ?)";
        $stmt_funcao = $pdo->prepare($sql_funcao);
        $stmt_funcao->execute([$nome_funcao, $chave, $descricao, $nivel]);

        // Passo 2: Pega o ID da função que acabamos de criar
        $nova_funcao_id = $pdo->lastInsertId();

        // Passo 3: Se houver permissões selecionadas, insere na tabela de conexão
        if (!empty($permissoes)) {
            $sql_perm = "INSERT INTO funcao_permissao (funcao_id, permissao_id) VALUES (?, ?)";
            $stmt_perm = $pdo->prepare($sql_perm);
            foreach ($permissoes as $permissao_id) {
                $stmt_perm->execute([$nova_funcao_id, $permissao_id]);
            }
        }

        // Se tudo deu certo até aqui, confirma a transação
        $pdo->commit();

        // Registra a ação no log
        registrar_log($pdo, $_SESSION['user_id'], "Criou a função '{$nome_funcao}' (ID: {$nova_funcao_id}).");

        header('Location: funcoes_listar.php?sucesso=funcao_criada');
        exit();

    } catch (PDOException $e) {
        // Se qualquer erro ocorrer, desfaz todas as operações
        $pdo->rollBack();
        // Para depuração: die($e->getMessage());
        header('Location: funcoes_adicionar.php?erro=db_error');
        exit();
    }
} else {
    header('Location: funcoes_listar.php');
    exit();
}
?>