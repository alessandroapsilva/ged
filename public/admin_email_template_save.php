<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_auth();
require_permission('email.templates.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: admin_email_templates.php');
  exit;
}

if (function_exists('csrf_require')) { csrf_require(); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome = trim($_POST['nome'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$assunto = trim($_POST['assunto'] ?? '');
$corpo_html = $_POST['corpo_html'] ?? '';
$corpo_texto = $_POST['corpo_texto'] ?? '';
$variaveis_json = $_POST['variaveis_json'] ?? '';
$ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

if ($nome === '' || $slug === '' || $assunto === '') {
  $_SESSION['flash_error'] = 'Preencha os campos obrigatórios.';
  header('Location: admin_email_template_edit.php' . ($id ? ('?id=' . $id) : ''));
  exit;
}

try {
  if ($id) {
    $pdo->beginTransaction();
    $stmtU = $pdo->prepare('UPDATE email_templates SET nome = ?, slug = ?, assunto = ?, corpo_html = ?, corpo_texto = ?, variaveis_json = ?, ativo = ?, updated_at = NOW() WHERE id = ?');
    $stmtU->execute([$nome, $slug, $assunto, $corpo_html, $corpo_texto, $variaveis_json, $ativo, $id]);
    // versão
    $stmtV = $pdo->prepare('INSERT INTO email_template_versions (template_id, assunto, corpo_html, corpo_texto, criado_por) VALUES (?,?,?,?,?)');
    $stmtV->execute([$id, $assunto, $corpo_html, $corpo_texto, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null]);
    $pdo->commit();
    if (function_exists('registrar_log')) { registrar_log($pdo, (int)$_SESSION['user_id'], "Atualizou template de e-mail: $slug", 'Admin'); }
  } else {
    $pdo->beginTransaction();
    $stmtI = $pdo->prepare('INSERT INTO email_templates (nome, slug, assunto, corpo_html, corpo_texto, variaveis_json, ativo) VALUES (?,?,?,?,?,?,?)');
    $stmtI->execute([$nome, $slug, $assunto, $corpo_html, $corpo_texto, $variaveis_json, $ativo]);
    $newId = (int)$pdo->lastInsertId();
    $stmtV = $pdo->prepare('INSERT INTO email_template_versions (template_id, assunto, corpo_html, corpo_texto, criado_por) VALUES (?,?,?,?,?)');
    $stmtV->execute([$newId, $assunto, $corpo_html, $corpo_texto, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null]);
    $pdo->commit();
    if (function_exists('registrar_log')) { registrar_log($pdo, (int)$_SESSION['user_id'], "Criou template de e-mail: $slug", 'Admin'); }
  }
  header('Location: admin_email_templates.php');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  $_SESSION['flash_error'] = 'Erro ao salvar: ' . $e->getMessage();
  header('Location: admin_email_template_edit.php' . ($id ? ('?id=' . $id) : ''));
}
