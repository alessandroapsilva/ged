<?php
// public/lixeira_restaurar.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$id = (int)($_GET['id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';
$tabela = ($tipo === 'p') ? 'pastas' : 'documentos';

if ($id > 0) {
    $sql = "UPDATE {$tabela} SET apagado_em = NULL WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

header('Location: lixeira.php');
exit();
?>