<?php
require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    http_response_code(403);
    exit(json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']));
}
require_once '../helpers/auth_helper.php';
require_once '../helpers/log_helper.php';

// Verifica se o usuário tem permissão para apagar funções
if (!usuario_tem_permissao('roles.delete')) {
    http_response_code(403);
    exit(json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para apagar funções.']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        exit(json_encode(['sucesso' => false, 'mensagem' => 'Nenhum ID fornecido.']));
    }

    $ids_para_apagar = [];
    $nomes_apagados = [];
    $erros = [];

    // Prepara a query para verificar se a função está em uso
    $sql_check = "SELECT COUNT(*) FROM usuarios WHERE funcao_id = ?";
    $stmt_check = $pdo->prepare($sql_check);

    // Prepara a query para pegar o nome antes de apagar (para o log)
    $sql_select = "SELECT nome_funcao FROM funcoes WHERE id = ?";
    $stmt_select = $pdo->prepare($sql_select);

    // Verifica cada ID individualmente
    foreach ($ids as $id) {
        $id = (int)$id;
        $stmt_check->execute([$id]);
        $user_count = $stmt_check->fetchColumn();

        if ($user_count > 0) {
            $erros[] = "A função com ID {$id} não pôde ser apagada pois está em uso.";
        } else {
            // Se não está em uso, adiciona à lista para apagar
            $ids_para_apagar[] = $id;
            // Pega o nome para registrar no log
            $stmt_select->execute([$id]);
            $funcao = $stmt_select->fetch();
            if ($funcao) $nomes_apagados[] = $funcao['nome_funcao'];
        }
    }

    try {
        if (!empty($ids_para_apagar)) {
            // Cria os placeholders (?) para a query IN
            $placeholders = implode(',', array_fill(0, count($ids_para_apagar), '?'));
            $sql_delete = "DELETE FROM funcoes WHERE id IN ({$placeholders})";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute($ids_para_apagar);

            // Registra no log
            $log_msg = "Apagou em lote as funções: " . implode(', ', $nomes_apagados);
            registrar_log($pdo, $_SESSION['user_id'], $log_msg);
        }

        // Monta a mensagem de resposta
        $mensagem_final = count($ids_para_apagar) . " função(ões) apagada(s) com sucesso.";
        if (!empty($erros)) {
            $mensagem_final .= " " . count($erros) . " não puderam ser apagadas por estarem em uso.";
        }

        echo json_encode(['sucesso' => true, 'mensagem' => $mensagem_final, 'ids_apagados' => $ids_para_apagar]);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        exit(json_encode(['sucesso' => false, 'mensagem' => 'Erro de banco de dados.']));
    }
}
?>