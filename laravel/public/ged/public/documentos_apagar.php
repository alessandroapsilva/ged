<?php
// public/documentos_apagar.php
// Script para mover um documento para a lixeira.

require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE documentos SET apagado_em = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Em ambiente de produção, logar o erro e mostrar uma mensagem amigável.
        error_log('Erro ao mover documento para a lixeira: ' . $e->getMessage());
        // Poderia redirecionar para uma página de erro ou mostrar uma notificação.
    }
}

// Redireciona de volta para a página de onde o usuário veio, ou para a home.
$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'documentos.php';
header("Location: " . $redirect_url);
exit();
?>
