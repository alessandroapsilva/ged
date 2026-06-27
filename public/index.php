<?php
// public/index.php
require_once '../core/init.php';

// Verifica se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit();
}

// Redireciona para o dashboard
header('Location: ' . BASE_URL . '/public/painel_produtividade_moderno.php');
exit();
?>
