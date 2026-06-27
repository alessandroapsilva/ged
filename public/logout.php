<?php
// public/logout.php
require_once '../core/init.php';

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("Location: login?status=logout");
exit();
?>