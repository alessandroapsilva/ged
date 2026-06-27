<?php
// public/funcoes_apagar.php
require_once '../core/init.php';
session_start();
if (!isset($_SESSION['user_id'])) { exit('Acesso negado.'); }

// Apenas processa se um ID for passado
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    try {
        // Graças ao 'ON DELETE CASCADE' no banco, apagar a função também apaga
        // as ligações na tabela 'funcao_permissao'.
        $stmt = $pdo->prepare("DELETE FROM funcoes WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: funcoes_listar.php?sucesso=apagado');
        exit();
    } catch (PDOException $e) {
        die('Erro ao apagar a função: ' . $e->getMessage());
    }
} else {
    header('Location: funcoes_listar.php');
    exit();
}
?>