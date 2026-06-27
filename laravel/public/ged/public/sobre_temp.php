<?php
// public/sobre.php - Página Sobre o Sistema
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
?>