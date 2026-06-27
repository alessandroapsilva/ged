<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';
require_auth();

// Configurações atuais
$provider = (string)lgpd_get_setting($pdo, 'SIGN_PROVIDER', 'local'); // local|cloud|birdid
$baseUrl = (string)lgpd_get_setting($pdo, 'CLOUD_ICP_BASE_URL', '');
$clientId = (string)lgpd_get_setting($pdo, 'CLOUD_ICP_CLIENT_ID', '');
$clientSecret = (string)lgpd_get_setting($pdo, 'CLOUD_ICP_CLIENT_SECRET', '');
$callbackUrl = (string)lgpd_get_setting($pdo, 'CLOUD_ICP_CALLBACK_URL', '');
$tenant = (string)lgpd_get_setting($pdo, 'CLOUD_ICP_TENANT', '');

// Bird ID settings
$birdBase = (string)lgpd_get_setting($pdo, 'BIRD_BASE_URL', '');
$birdAuth = (string)lgpd_get_setting($pdo, 'BIRD_AUTH_URL', '');
$birdToken = (string)lgpd_get_setting($pdo, 'BIRD_TOKEN_URL', '');
$birdSign = (string)lgpd_get_setting($pdo, 'BIRD_SIGN_URL', '');
$birdClient = (string)lgpd_get_setting($pdo, 'BIRD_CLIENT_ID', '');
$birdSecret = (string)lgpd_get_setting($pdo, 'BIRD_CLIENT_SECRET', '');
$birdCallback = (string)lgpd_get_setting($pdo, 'BIRD_CALLBACK_URL', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  lgpd_set_setting($pdo, 'SIGN_PROVIDER', in_array($_POST['SIGN_PROVIDER'] ?? 'local', ['local','cloud','birdid'], true) ? $_POST['SIGN_PROVIDER'] : 'local');
    lgpd_set_setting($pdo, 'CLOUD_ICP_BASE_URL', trim($_POST['CLOUD_ICP_BASE_URL'] ?? ''));
    lgpd_set_setting($pdo, 'CLOUD_ICP_CLIENT_ID', trim($_POST['CLOUD_ICP_CLIENT_ID'] ?? ''));
    lgpd_set_setting($pdo, 'CLOUD_ICP_CLIENT_SECRET', trim($_POST['CLOUD_ICP_CLIENT_SECRET'] ?? ''));
    lgpd_set_setting($pdo, 'CLOUD_ICP_CALLBACK_URL', trim($_POST['CLOUD_ICP_CALLBACK_URL'] ?? ''));
    lgpd_set_setting($pdo, 'CLOUD_ICP_TENANT', trim($_POST['CLOUD_ICP_TENANT'] ?? ''));
  // Bird ID
  lgpd_set_setting($pdo, 'BIRD_BASE_URL', trim($_POST['BIRD_BASE_URL'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_AUTH_URL', trim($_POST['BIRD_AUTH_URL'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_TOKEN_URL', trim($_POST['BIRD_TOKEN_URL'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_SIGN_URL', trim($_POST['BIRD_SIGN_URL'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_CLIENT_ID', trim($_POST['BIRD_CLIENT_ID'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_CLIENT_SECRET', trim($_POST['BIRD_CLIENT_SECRET'] ?? ''));
  lgpd_set_setting($pdo, 'BIRD_CALLBACK_URL', trim($_POST['BIRD_CALLBACK_URL'] ?? ''));
    header('Location: admin_icp_config.php?salvo=1');
    exit();
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>Assinatura Digital (ICP)</h1></div></div></div></section>
  <section class="content"><div class="container-fluid">
    <?php if (!empty($_GET['salvo'])): ?><div class="alert alert-success">Configurações salvas.</div><?php endif; ?>
    <form method="post">
      <div class="card card-dark card-outline"><div class="card-body">
        <div class="form-group">
          <label>Provedor</label>
          <select name="SIGN_PROVIDER" class="form-control">
            <option value="local" <?= $provider==='local'?'selected':'' ?>>Local (A1 - arquivo .pfx/.p12)</option>
            <option value="cloud" <?= $provider==='cloud'?'selected':'' ?>>ICP em Nuvem (via API do provedor)</option>
            <option value="birdid" <?= $provider==='birdid'?'selected':'' ?>>Bird ID (ICP remoto)</option>
          </select>
        </div>
        <div class="border rounded p-3" style="background:#f9f9fb">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Base URL (Cloud ICP)</label>
              <input type="url" name="CLOUD_ICP_BASE_URL" value="<?= htmlspecialchars($baseUrl) ?>" class="form-control" placeholder="https://api.provedor-icp.com" />
            </div>
            <div class="form-group col-md-6">
              <label>Tenant/Account</label>
              <input type="text" name="CLOUD_ICP_TENANT" value="<?= htmlspecialchars($tenant) ?>" class="form-control" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Client ID</label>
              <input type="text" name="CLOUD_ICP_CLIENT_ID" value="<?= htmlspecialchars($clientId) ?>" class="form-control" />
            </div>
            <div class="form-group col-md-6">
              <label>Client Secret</label>
              <input type="text" name="CLOUD_ICP_CLIENT_SECRET" value="<?= htmlspecialchars($clientSecret) ?>" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label>Callback URL (retorno do provedor)</label>
            <input type="url" name="CLOUD_ICP_CALLBACK_URL" value="<?= htmlspecialchars($callbackUrl) ?>" class="form-control" placeholder="https://seu-dominio/ged/esign/callback" />
          </div>
          <small class="text-muted">Dica: A URL de callback deve estar exposta na internet e configurada no painel do provedor.</small>
        </div>

        <hr>
        <h5>Bird ID (opcional)</h5>
        <div class="border rounded p-3" style="background:#f9f9fb">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Base URL (Bird ID)</label>
              <input type="url" name="BIRD_BASE_URL" value="<?= htmlspecialchars($birdBase) ?>" class="form-control" placeholder="https://api.birdid.com.br">
            </div>
            <div class="form-group col-md-6">
              <label>Callback URL (Bird ID)</label>
              <input type="url" name="BIRD_CALLBACK_URL" value="<?= htmlspecialchars($birdCallback) ?>" class="form-control" placeholder="https://seu-dominio/ged/esign/callback">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Auth URL</label>
              <input type="url" name="BIRD_AUTH_URL" value="<?= htmlspecialchars($birdAuth) ?>" class="form-control" placeholder="https://.../oauth/authorize">
            </div>
            <div class="form-group col-md-4">
              <label>Token URL</label>
              <input type="url" name="BIRD_TOKEN_URL" value="<?= htmlspecialchars($birdToken) ?>" class="form-control" placeholder="https://.../oauth/token">
            </div>
            <div class="form-group col-md-4">
              <label>Sign URL</label>
              <input type="url" name="BIRD_SIGN_URL" value="<?= htmlspecialchars($birdSign) ?>" class="form-control" placeholder="https://.../signature/pades">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Client ID (Bird ID)</label>
              <input type="text" name="BIRD_CLIENT_ID" value="<?= htmlspecialchars($birdClient) ?>" class="form-control">
            </div>
            <div class="form-group col-md-6">
              <label>Client Secret (Bird ID)</label>
              <input type="text" name="BIRD_CLIENT_SECRET" value="<?= htmlspecialchars($birdSecret) ?>" class="form-control">
            </div>
          </div>
          <small class="text-muted">Preencha os endpoints conforme o ambiente do Bird ID. O fluxo OAuth retornará nesta aplicação e seguirá para assinatura PAdES.</small>
        </div>
      </div><div class="card-footer text-right">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
      </div></div>
    </form>
  </div></section>
</div>
<?php require_once '../templates/footer.php'; ?>
