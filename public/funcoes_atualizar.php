<?php
require_once dirname(__DIR__) . '/core/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !usuario_tem_permissao('role.edit') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado ou requisição inválida.']);
    exit();
}
$funcao_id = filter_input(INPUT_POST, 'funcao_id', FILTER_VALIDATE_INT);
$nome_funcao = trim(filter_input(INPUT_POST, 'nome_funcao'));
$chave = trim(filter_input(INPUT_POST, 'chave'));
$descricao = trim(filter_input(INPUT_POST, 'descricao'));
$nivel = filter_input(INPUT_POST, 'nivel', FILTER_VALIDATE_INT);
$permissoes_selecionadas = $_POST['permissoes'] ?? [];

if (!$funcao_id || empty($nome_funcao) || empty($chave) || $nivel === false) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados essenciais do formulário estão faltando.']);
    exit();
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE funcoes SET nome_funcao = ?, chave = ?, descricao = ?, nivel = ? WHERE id = ?");
    $stmt->execute([$nome_funcao, $chave, $descricao, $nivel, $funcao_id]);
    $stmt = $pdo->prepare("DELETE FROM funcao_permissao WHERE funcao_id = ?");
    $stmt->execute([$funcao_id]);
    if (!empty($permissoes_selecionadas)) {
        $stmt = $pdo->prepare("INSERT INTO funcao_permissao (funcao_id, permissao_id) VALUES (?, ?)");
        foreach ($permissoes_selecionadas as $permissao_id) {
            $stmt->execute([$funcao_id, (int)$permissao_id]);
        }
    }
    $pdo->commit();
    echo json_encode(['sucesso' => true]);
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
    exit();
}