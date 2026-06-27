<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/core/email.php';
require_auth();
require_permission('email.templates.manage');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tpl = null;
if ($id) {
  $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE id = ?');
  $stmt->execute([$id]);
  $tpl = $stmt->fetch();
}
if (!$tpl) { header('Location: admin_email_templates.php'); exit; }

$resultado = null; $erro = null; $ultimo_log = null;
// Dados de exemplo padrão
$defaultData = [
  'nome' => 'Fulano de Tal',
  'email' => 'fulano@example.com',
  'usuario' => [ 'nome' => 'Fulano Admin', 'email' => 'admin@example.com' ],
  'documento' => [ 'titulo' => 'Contrato de Prestação de Serviços', 'vencimento' => '31/12/2025', 'link' => 'https://exemplo.local/documentos/123' ],
  'link' => 'https://exemplo.local/acao/XYZ',
  'mensagem' => 'Segue o documento conforme solicitado.',
  'dias' => '7',
  'expira_em' => '24 horas'
];
// Se existir e-mail do usuário logado, usamos como sugestão
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('csrf_require')) { csrf_require(); }
  $destinatario = trim($_POST['destinatario'] ?? '');
  $json = trim($_POST['dados'] ?? '');
  $smtpDebug = isset($_POST['smtp_debug']) ? (int)$_POST['smtp_debug'] : 0;
  $dados = [];
  if ($json !== '') {
    $tmp = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $dados = $tmp; }
    else { $erro = 'JSON inválido nas variáveis.'; }
  }
  if (empty($dados)) { $dados = $defaultData; }
  if (!$erro && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    try {
      $ok = email_send_template($pdo, $destinatario, $tpl['slug'], $dados, ['smtp_debug' => $smtpDebug]);
      $resultado = $ok ? 'Enviado com sucesso para ' . htmlspecialchars($destinatario) : 'Falha ao enviar.';
      // Busca último log deste envio
      try {
        $l = $pdo->prepare("SELECT id, template_slug, assunto, destinatario, remetente, status, erro, payload_json, created_at FROM emails_log WHERE template_slug = ? AND destinatario = ? ORDER BY id DESC LIMIT 1");
        $l->execute([$tpl['slug'], $destinatario]);
        $ultimo_log = $l->fetch(PDO::FETCH_ASSOC);
      } catch (Throwable $e2) { /* silencioso */ }
    } catch (Throwable $e) {
      $erro = 'Erro: ' . $e->getMessage();
    }
  } else if (!$erro) {
    $erro = 'Informe um e-mail válido.';
  }
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Testar Template: <?= htmlspecialchars($tpl['nome']) ?> <small class="text-muted">(<?= htmlspecialchars($tpl['slug']) ?>)</small></h1></div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <?php if ($resultado): ?><div class="alert alert-success"><?= $resultado ?></div><?php endif; ?>
      <?php if ($erro): ?><div class="alert alert-danger"><?= $erro ?></div><?php endif; ?>
      <div class="card card-dark card-outline">
        <div class="card-body">
          <form method="post">
            <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Destinatário</label>
                <input type="email" class="form-control" name="destinatario" placeholder="email@dominio.com" value="<?= htmlspecialchars($userEmail) ?>" required>
                <small class="form-text text-muted">Dica: deixe seu e-mail aqui para enviar um teste rápido.</small>
              </div>
              <div class="form-group col-md-6">
                <label>Variáveis (JSON)</label>
                <textarea class="form-control" name="dados" rows="8" placeholder='<?= json_encode($defaultData, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?>'><?= isset($_POST['dados']) && $_POST['dados'] !== '' ? htmlspecialchars($_POST['dados']) : json_encode($defaultData, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?></textarea>
                <small class="form-text text-muted">Use {{chave}} ou {{chave|Padrão}}; suporta caminhos, ex.: {{documento.titulo}}.</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Debug SMTP</label>
                <select name="smtp_debug" class="form-control">
                  <option value="0" <?= (isset($_POST['smtp_debug']) && $_POST['smtp_debug']=='0') ? 'selected' : '' ?>>Desligado</option>
                  <option value="1" <?= (isset($_POST['smtp_debug']) && $_POST['smtp_debug']=='1') ? 'selected' : '' ?>>Básico</option>
                  <option value="2" <?= (isset($_POST['smtp_debug']) && $_POST['smtp_debug']=='2') ? 'selected' : '' ?>>Detalhado</option>
                </select>
              </div>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane mr-1"></i>Enviar teste</button>
            <?php if (!empty($userEmail)): ?>
              <button class="btn btn-outline-secondary" type="button" onclick="(function(){var f=document.forms[0]; f.destinatario.value='<?= htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>'; f.submit();})();">Enviar para mim</button>
            <?php endif; ?>
            <a href="admin_email_templates.php" class="btn btn-secondary">Voltar</a>
          </form>
        </div>
      </div>
      <div class="card card-outline">
        <div class="card-header"><strong>Pré-visualização</strong></div>
        <div class="card-body">
          <?php
            try {
              $previewData = isset($dados) && is_array($dados) && !empty($dados) ? $dados : $defaultData;
              $preview = email_preview_template($pdo, $tpl['slug'], $previewData);
              $html = $preview['html'];
              if (strip_tags($html) === $html) { $html = nl2br($html); }
              echo '<p><strong>Assunto:</strong> ' . htmlspecialchars($preview['assunto']) . '</p>';
              echo '<div class="border p-3" style="background:#fff">' . $html . '</div>';
            } catch (Throwable $e) {
              echo '<div class="text-danger">Erro ao renderizar: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
          ?>
        </div>
      </div>
      <?php if ($ultimo_log): ?>
      <div class="card card-outline">
        <div class="card-header"><strong>Detalhes do envio</strong></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div><strong>Status:</strong> <?= htmlspecialchars($ultimo_log['status']) ?></div>
              <div><strong>Assunto:</strong> <?= htmlspecialchars($ultimo_log['assunto']) ?></div>
              <div><strong>Para:</strong> <?= htmlspecialchars($ultimo_log['destinatario']) ?></div>
              <div><strong>De:</strong> <?= htmlspecialchars($ultimo_log['remetente']) ?></div>
              <?php if (!empty($ultimo_log['created_at'])): ?><div><strong>Quando:</strong> <?= htmlspecialchars($ultimo_log['created_at']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <?php if (!empty($ultimo_log['erro'])): ?>
                <div class="alert alert-warning"><strong>Erro SMTP:</strong> <?= htmlspecialchars($ultimo_log['erro']) ?></div>
              <?php endif; ?>
              <?php if (!empty($ultimo_log['payload_json'])): ?>
                <div>
                  <strong>Payload</strong>
                  <pre class="border p-2" style="background:#fff;white-space:pre-wrap;word-wrap:break-word;"><?= htmlspecialchars($ultimo_log['payload_json']) ?></pre>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
