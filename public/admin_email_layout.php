<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_auth();
require_permission('email.templates.manage');

// Carrega valores atuais
$headerHtml = '';
$footerHtml = '';
try {
  $st = $pdo->prepare('SELECT chave, valor FROM app_settings WHERE chave IN ("email_header_html","email_footer_html")');
  $st->execute();
  foreach ($st->fetchAll() as $r) {
    if ($r['chave'] === 'email_header_html') $headerHtml = (string)$r['valor'];
    if ($r['chave'] === 'email_footer_html') $footerHtml = (string)$r['valor'];
  }
} catch (Throwable $e) {
  // silencioso: página ainda permite cadastrar
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Layout padrão de e-mails</h1></div>
        <div class="col-sm-6 text-right">
          <a href="admin_email_templates.php" class="btn btn-default">Voltar</a>
        </div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="alert alert-secondary">
        Configure aqui o cabeçalho e o rodapé padrão aplicados aos e-mails. Use apenas trechos HTML (sem <html> e <body>), pois serão inseridos ao redor do conteúdo dos templates.
      </div>
      <form method="post" action="admin_email_layout_save.php">
        <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
        <div class="card card-dark card-outline">
          <div class="card-body">
            <div class="form-group">
              <label>Cabeçalho (HTML)</label>
              <textarea name="email_header_html" class="form-control" rows="8" placeholder="<div style='padding:16px;background:#fff;border-bottom:1px solid #eee'>..."><?= htmlspecialchars($headerHtml) ?></textarea>
            </div>
            <div class="form-group">
              <label>Rodapé (HTML)</label>
              <textarea name="email_footer_html" class="form-control" rows="8" placeholder="<div style='padding:12px;border-top:1px solid #eee;color:#6c757d;font-size:12px'>..."><?= htmlspecialchars($footerHtml) ?></textarea>
            </div>
          </div>
          <div class="card-footer text-right">
            <a href="admin_email_templates.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
