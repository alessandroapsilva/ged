<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/ingest_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (function_exists('require_permission')) { require_permission('ingest.validate'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ingest.php'); exit(); }

$st = $pdo->prepare('SELECT id, nome_original FROM ingest_arquivos WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Item não encontrado.']; header('Location: ingest.php'); exit(); }

$valid = ingest_validar_nome($row['nome_original']);
$novoStatus = $valid['ok'] ? 'corrigido' : 'corrigir';
$falha = $valid['falha'];

$up = $pdo->prepare('UPDATE ingest_arquivos SET status = ?, falha_motivo = ?, corrigido_em = CASE WHEN ? = "corrigido" THEN NOW() ELSE corrigido_em END WHERE id = ?');
$up->execute([$novoStatus, $falha, $novoStatus, $id]);

$_SESSION['flash_message'] = ['type' => $valid['ok'] ? 'sucesso' : 'alerta', 'text' => $valid['ok'] ? 'Item validado e marcado como corrigido.' : ('Ainda precisa corrigir: ' . $falha)];
header('Location: ingest.php');
exit();
?>
