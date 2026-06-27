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
if ($id) {
  try {
    $pdo->beginTransaction();
    $slug = null;
    $r = $pdo->prepare('SELECT slug FROM email_templates WHERE id = ?');
    $r->execute([$id]);
    $row = $r->fetch();
    if ($row) { $slug = $row['slug']; }
    $stmt = $pdo->prepare('UPDATE email_templates SET ativo = 0 WHERE id = ?');
    $stmt->execute([$id]);
    $pdo->commit();
    if (function_exists('registrar_log')) { registrar_log($pdo, (int)$_SESSION['user_id'], "Desativou template de e-mail: $slug", 'Admin'); }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
  }
}
header('Location: admin_email_templates.php');
