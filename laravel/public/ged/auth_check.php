
<?php
// Middleware simples de autenticação (compatível com esquema atual)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
