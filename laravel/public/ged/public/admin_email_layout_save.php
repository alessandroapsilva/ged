<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/core/email.php';
require_auth();
require_permission('email.templates.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: admin_email_layout.php');
  exit;
}
if (function_exists('csrf_require')) csrf_require();

$header = $_POST['email_header_html'] ?? '';
$footer = $_POST['email_footer_html'] ?? '';

try {
  $pdo->beginTransaction();
  app_setting_set($pdo, 'email_header_html', $header);
  app_setting_set($pdo, 'email_footer_html', $footer);
  $pdo->commit();
  if (function_exists('registrar_log')) {
    registrar_log($pdo, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null, 'Atualizou layout padrão de e-mails', 'Admin');
  }
  $_SESSION['flash_success'] = 'Layout salvo com sucesso.';
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  $_SESSION['flash_error'] = 'Erro ao salvar layout: ' . $e->getMessage();
}

header('Location: admin_email_layout.php');
