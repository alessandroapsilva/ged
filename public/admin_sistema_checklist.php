<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();

$messages = [];
$errors = [];
function is_writable_dir($path) { return is_dir($path) && is_writable($path); }

// Ações de correção rápida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_dirs') {
    $dirs = [
      PROJECT_ROOT.'/public/uploads',
      PROJECT_ROOT.'/public/storage/uploads',
      PROJECT_ROOT.'/storage',
      PROJECT_ROOT.'/storage/arquivados'
    ];
        foreach ($dirs as $d) { if (!is_dir($d)) { @mkdir($d, 0775, true); } }
        $messages[] = 'Diretórios criados/verificados.';
    } elseif ($action === 'write_htaccess') {
        $ht = "Deny from all\n";
        @file_put_contents(PROJECT_ROOT.'/storage/.htaccess', $ht);
        @file_put_contents(PROJECT_ROOT.'/storage/arquivados/.htaccess', $ht);
        $messages[] = '.htaccess escrito em storage/.';
  } elseif ($action === 'test_upload') {
    // Teste rápido de upload para public/storage/uploads
    $targetDir = PROJECT_ROOT.'/public/storage/uploads';
    if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }
    if (!isset($_FILES['arquivo']) || !is_array($_FILES['arquivo'])) {
      $errors[] = 'Nenhum arquivo recebido no teste.';
    } else {
      $err = (int)($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE);
      if ($err !== UPLOAD_ERR_OK) {
        $errors[] = 'Falha no upload de teste (código '.$err.').';
      } else {
        $dest = $targetDir.'/_teste_'.uniqid().'.bin';
        if (@move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
          @unlink($dest);
          $messages[] = 'Upload de teste realizado com sucesso em public/storage/uploads.';
        } else {
          $errors[] = 'Falha ao mover o arquivo de teste. Verifique permissões em public/storage/uploads.';
        }
      }
    }
    }
}

// Checks
$env = getenv('APP_ENV') ?: getenv('GED_ENV') ?: 'development';
$inProd = in_array(strtolower($env), ['prod','production']);
$displayErrors = ini_get('display_errors');
$iniPath = function_exists('php_ini_loaded_file') ? php_ini_loaded_file() : '';

$dirsOk = [
  'public/uploads' => is_writable_dir(PROJECT_ROOT.'/public/uploads'),
  'public/storage/uploads' => is_writable_dir(PROJECT_ROOT.'/public/storage/uploads'),
  'storage' => is_writable_dir(PROJECT_ROOT.'/storage'),
  'storage/arquivados' => is_writable_dir(PROJECT_ROOT.'/storage/arquivados'),
];

$hasMailer = class_exists('PHPMailer\\PHPMailer\\PHPMailer') || file_exists(PROJECT_ROOT.'/vendor/autoload.php');

// DB tables
$tables = [];
try { $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN, 0); } catch (Throwable $e) {}
$hasEmail = in_array('email_templates', $tables, true);
$hasIngest = in_array('ingest_arquivos', $tables, true) && in_array('ingest_eventos', $tables, true);

// App settings
require_once PROJECT_ROOT . '/core/email.php';
$hdr = app_setting_get($pdo, 'email_header_html', '');
$ftr = app_setting_get($pdo, 'email_footer_html', '');
$ingestPasta = app_setting_get($pdo, 'INGEST_PASTA_MONITORADA', '');

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Checklist de Produção</h1></div></section>
  <section class="content"><div class="container-fluid">
    <?php foreach ($messages as $m): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
    <?php foreach ($errors as $m): ?><div class="alert alert-danger"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>

    <div class="card card-dark card-outline">
      <div class="card-body">
        <h5>Ambiente</h5>
        <ul>
          <li>APP_ENV: <strong><?= htmlspecialchars($env) ?></strong> <?= $inProd ? '<span class="badge badge-success">produção</span>' : '<span class="badge badge-warning">desenvolvimento</span>' ?></li>
          <li>display_errors: <strong><?= $displayErrors ?></strong> <?= ($inProd && $displayErrors=='0') ? '<span class="badge badge-success">OK</span>' : ($inProd ? '<span class="badge badge-danger">DESLIGUE EM PRODUÇÃO</span>' : '') ?></li>
          <li>php.ini carregado: <code><?= htmlspecialchars($iniPath ?: 'desconhecido') ?></code></li>
        </ul>
        <h5>Diretórios</h5>
        <ul>
          <?php foreach ($dirsOk as $name => $ok): ?>
            <li><?= $name ?>: <?= $ok ? '<span class="text-success">gravável</span>' : '<span class="text-danger">faltando/permissões</span>' ?></li>
          <?php endforeach; ?>
        </ul>
        <form method="post" class="mb-3">
          <input type="hidden" name="action" value="create_dirs">
          <button class="btn btn-sm btn-secondary"><i class="fas fa-folder-open mr-1"></i>Criar/Verificar diretórios</button>
        </form>
        <h5>Proteção de Storage</h5>
        <p>.htaccess presente: <?= file_exists(PROJECT_ROOT.'/storage/.htaccess') ? '<span class="text-success">sim</span>' : '<span class="text-danger">não</span>' ?></p>
        <form method="post" class="mb-3">
          <input type="hidden" name="action" value="write_htaccess">
          <button class="btn btn-sm btn-secondary"><i class="fas fa-shield-alt mr-1"></i>Escrever .htaccess</button>
        </form>
        <h5>Dependências</h5>
        <ul><li>PHPMailer/Composer: <?= $hasMailer ? '<span class="text-success">OK</span>' : '<span class="text-danger">vendor ausente</span>' ?></li></ul>
        <h5>Limites de upload (PHP)</h5>
        <ul>
          <li>upload_max_filesize: <code><?= htmlspecialchars(ini_get('upload_max_filesize')) ?></code></li>
          <li>post_max_size: <code><?= htmlspecialchars(ini_get('post_max_size')) ?></code></li>
          <li>file_uploads: <code><?= htmlspecialchars(ini_get('file_uploads')) ?></code></li>
          <li>upload_tmp_dir: <code><?= htmlspecialchars(ini_get('upload_tmp_dir') ?: '(padrão do sistema)') ?></code></li>
          <li>tmp dir existe?: <code><?= ($d = ini_get('upload_tmp_dir')) && is_dir($d) ? 'sim' : 'não/indefinido' ?></code></li>
        </ul>
        <div class="border rounded p-3 mb-3">
          <h6>Como ajustar os limites no php.ini (Windows/XAMPP)</h6>
          <ol>
            <li>Feche uploads em andamento. Abra o arquivo php.ini: <code><?= htmlspecialchars($iniPath ?: 'C:\\xampp\\php\\php.ini') ?></code></li>
            <li>Procure e ajuste estes valores (recomendado para produção moderada):
              <pre style="white-space: pre-wrap; background:#f8f9fa; padding:10px; border:1px solid #eee;">
upload_max_filesize = 100M
post_max_size = 128M
max_file_uploads = 50
memory_limit = 256M
; Se necessário, defina uma pasta temporária dedicada (garanta que exista e seja gravável)
; upload_tmp_dir = "C:\\xampp\\tmp"
              </pre>
            </li>
            <li>Salve o arquivo php.ini e reinicie o Apache no XAMPP Control Panel.</li>
            <li>Retorne aqui e use “Testar upload” para confirmar.</li>
          </ol>
        </div>
        <div class="border rounded p-3 mb-3">
          <h6>Diagnóstico de Upload</h6>
          <p class="mb-2">Faça um teste rápido para checar se o servidor consegue gravar em <code>public/storage/uploads</code>.</p>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="test_upload">
            <div class="form-group">
              <input type="file" name="arquivo" class="form-control-file" required>
            </div>
            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-upload mr-1"></i>Testar upload</button>
          </form>
        </div>
        <h5>Banco de Dados</h5>
        <ul>
          <li>email_templates: <?= $hasEmail ? '<span class="text-success">OK</span>' : '<span class="text-danger">faltando</span>' ?></li>
          <li>ingest_arquivos/eventos: <?= $hasIngest ? '<span class="text-success">OK</span>' : '<span class="text-danger">faltando</span>' ?></li>
        </ul>
        <h5>Configurações de E-mail</h5>
        <ul>
          <li>Cabeçalho padrão: <?= $hdr !== '' ? '<span class="text-success">definido</span>' : '<span class="text-warning">vazio</span>' ?></li>
          <li>Rodapé padrão: <?= $ftr !== '' ? '<span class="text-success">definido</span>' : '<span class="text-warning">vazio</span>' ?></li>
          <li><a href="admin_email_layout.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-columns mr-1"></i>Editar layout de e-mail</a></li>
        </ul>
        <h5>Ingest</h5>
        <ul>
          <li>Pasta monitorada (app_settings INGEST_PASTA_MONITORADA): <code><?= htmlspecialchars($ingestPasta) ?></code></li>
          <li><a href="ingest.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-upload mr-1"></i>Abrir Ingest</a></li>
        </ul>
      </div>
    </div>
  </div></section>
</div>
<?php require_once '../templates/footer.php'; ?>
