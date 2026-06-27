<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('require_permission')) { require_permission('ingest.delete'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ingest.php'); exit(); }

$st = $pdo->prepare('SELECT caminho_relativo FROM ingest_arquivos WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $pdo->prepare('DELETE FROM ingest_arquivos WHERE id = ?')->execute([$id]);
    $abs = PROJECT_ROOT . '/public/' . $row['caminho_relativo'];
    if (file_exists($abs)) { @unlink($abs); }
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Arquivo removido da fila.'];
} else {
    $_SESSION['flash_message'] = ['type' => 'alerta', 'text' => 'Item já removido ou inexistente.'];
}

header('Location: ingest.php');
exit();
?>
