<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';
require_auth();

$policyUrl = lgpd_policy_url($pdo);
$requireConsent = lgpd_is_consent_required($pdo);
$logIps = lgpd_log_ips($pdo);
$retention = (string)lgpd_get_setting($pdo, 'LGPD_LOG_RETENTION_DAYS', '0');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lgpd_set_setting($pdo, 'LGPD_POLICY_URL', trim($_POST['LGPD_POLICY_URL'] ?? ''));
    lgpd_set_setting($pdo, 'LGPD_REQUIRE_CONSENT', isset($_POST['LGPD_REQUIRE_CONSENT']) ? '1' : '0');
    lgpd_set_setting($pdo, 'LGPD_LOG_IPS', isset($_POST['LGPD_LOG_IPS']) ? '1' : '0');
    lgpd_set_setting($pdo, 'LGPD_LOG_RETENTION_DAYS', (string)max(0, (int)($_POST['LGPD_LOG_RETENTION_DAYS'] ?? 0)));
    header('Location: admin_lgpd.php?salvo=1');
    exit();
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>LGPD & Privacidade</h1></div></div></div></section>
  <section class="content"><div class="container-fluid">
    <?php if (!empty($_GET['salvo'])): ?><div class="alert alert-success">Configurações salvas.</div><?php endif; ?>
    <form method="post">
      <div class="card card-dark card-outline"><div class="card-body">
        <div class="form-group">
          <label>URL da Política de Privacidade</label>
          <input type="url" name="LGPD_POLICY_URL" value="<?= htmlspecialchars($policyUrl) ?>" class="form-control" placeholder="https://example.com/politica" />
          <small class="text-muted">Será exibida como link nas telas que requerem consentimento.</small>
        </div>
        <div class="form-group form-check">
          <input type="checkbox" id="LGPD_REQUIRE_CONSENT" name="LGPD_REQUIRE_CONSENT" value="1" class="form-check-input" <?= $requireConsent ? 'checked' : '' ?> />
          <label for="LGPD_REQUIRE_CONSENT" class="form-check-label">Exigir consentimento expresso antes de operações sensíveis (ex.: assinatura)</label>
        </div>
        <div class="form-group form-check">
          <input type="checkbox" id="LGPD_LOG_IPS" name="LGPD_LOG_IPS" value="1" class="form-check-input" <?= $logIps ? 'checked' : '' ?> />
          <label for="LGPD_LOG_IPS" class="form-check-label">Registrar IPs nos logs (recomendado somente se necessário)</label>
        </div>
        <div class="form-group">
          <label>Retenção de logs (dias)</label>
          <input type="number" min="0" name="LGPD_LOG_RETENTION_DAYS" value="<?= htmlspecialchars($retention) ?>" class="form-control" />
          <small class="text-muted">0 para ilimitado. Dica: crie uma tarefa agendada para limpeza periódica.</small>
        </div>
      </div><div class="card-footer text-right">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
      </div></div>
    </form>

    <div class="card card-outline card-secondary mt-3"><div class="card-body">
      <h5>Limpeza manual (logs)</h5>
      <p>Se desejar, posso adicionar um script de limpeza para executar via Agendador de Tarefas do Windows. Diga-me se quer que eu crie agora.</p>
    </div></div>
  </div></section>
</div>
<?php require_once '../templates/footer.php'; ?>
