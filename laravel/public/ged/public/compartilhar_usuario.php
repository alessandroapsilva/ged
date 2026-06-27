<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/share_user_helper.php';
require_once PROJECT_ROOT . '/core/email.php';

require_auth();
require_permission('document.share');

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id <= 0) { header('Location: documentos.php'); exit(); }

// Busca doc
$doc = null;
try {
    $sd = $pdo->prepare("SELECT id, titulo FROM documentos WHERE id = ? AND apagado_em IS NULL");
    $sd->execute([$documento_id]);
    $doc = $sd->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
if (!$doc) { header('Location: documentos.php'); exit(); }

$errors = [];
$values = [
  'users' => '',
  'view_only' => 0,
  'can_download' => 1,
  'expires_at' => '',
  'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) { http_response_code(403); exit('CSRF inválido'); }
    $users_raw = trim($_POST['users'] ?? '');
    $view_only = isset($_POST['view_only']) ? 1 : 0;
    $can_download = isset($_POST['can_download']) ? 1 : 0;
    $expires_in = trim($_POST['expires_at'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $values = [
        'users' => $users_raw,
        'view_only' => $view_only,
        'can_download' => $can_download,
        'expires_at' => $expires_in,
        'message' => $message
    ];

    $user_ids = [];
    if ($users_raw !== '') {
        $parts = preg_split('/[;,\s]+/', $users_raw, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $p) { if (ctype_digit($p)) { $user_ids[] = (int)$p; } }
        $user_ids = array_values(array_unique($user_ids));
        if (count($user_ids) > 50) { $errors[] = 'Limite de 50 usuários por operação.'; $user_ids = array_slice($user_ids, 0, 50); }
    } else {
        $errors[] = 'Informe ao menos um usuário.';
    }

    $expires_at = null;
    if ($expires_in !== '') {
        $exp_norm = str_replace('T', ' ', $expires_in);
        $dt = DateTime::createFromFormat('Y-m-d H:i', $exp_norm) ?: DateTime::createFromFormat('Y-m-d H:i:s', $exp_norm);
        if (!$dt) { $errors[] = 'Data de expiração inválida.'; }
        else if ($dt < new DateTime()) { $errors[] = 'A expiração deve ser no futuro.'; }
        else { $expires_at = $dt->format('Y-m-d H:i:s'); }
    }

    if (empty($errors)) {
        // Buscar e-mails dos usuários
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $st = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id IN ($placeholders)");
        $st->execute($user_ids);
        $users = $st->fetchAll(PDO::FETCH_ASSOC);

        $okCount = 0; $fail = 0; $mailErr = 0;
    foreach ($users as $u) {
            $res = share_user_create($pdo, $documento_id, (int)$u['id'], (int)$_SESSION['user_id'], (bool)$view_only, (bool)$can_download, $expires_at, $message);
            if ($res['ok']) {
                $okCount++;
        // Notificação interna (workflow_notificacoes), se a tabela existir
        try {
          $msgNotif = 'Você recebeu acesso ao documento "' . (string)($doc['titulo'] ?? ('#' . $documento_id)) . '".';
          $tipoNotif = 'compartilhamento';
          $stn = $pdo->prepare("INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) VALUES (NULL, ?, ?, ?)");
          $stn->execute([(int)$u['id'], $tipoNotif, $msgNotif]);
        } catch (Throwable $e) { /* ignora se não existir */ }
                // Email notificação (melhor effort)
                if (!empty($u['email'])) {
                    try {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $url = sprintf('%s://%s/ged/public/documentos_ver.php?id=%d', $scheme, $host, $documento_id);
                        $data = [
                            'nome' => (string)$u['nome'],
                            'titulo' => (string)($doc['titulo'] ?? 'Documento'),
                            'url' => $url,
                            'validade' => $expires_at ? date('d/m/Y H:i', strtotime($expires_at)) : 'Sem expiração',
                            'mensagem' => $message,
                        ];
                        // Usa template se existir, senão texto básico
                        $sent = email_send_template($pdo, $u['email'], 'compartilhar_interno', $data);
                        if (!$sent) { $mailErr++; }
                    } catch (Throwable $e) { $mailErr++; }
                }
            } else { $fail++; }
        }

        $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => sprintf('Compartilhado com %d usuário(s). E-mails com possível falha: %d.', $okCount, $mailErr)];
        header('Location: documentos_propriedades.php?id=' . $documento_id . '#shares-internos');
        exit();
    }
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Compartilhar com Usuários</h1><div class="text-muted">Documento: <strong><?= htmlspecialchars($doc['titulo'] ?? ('#' . $documento_id)); ?></strong></div></div></section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-primary">
        <div class="card-header">Conceder acesso interno</div>
        <form method="post">
          <div class="card-body">
            <?= csrf_input(); ?>
            <?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="form-group">
              <label>Usuários (IDs separados por vírgula, ou use o seletor abaixo)</label>
              <input type="text" name="users" id="users" class="form-control" placeholder="Ex.: 5, 42" value="<?= htmlspecialchars($values['users']); ?>">
              <small class="form-text text-muted">Dica: use o seletor para buscar por nome/e-mail e copie os IDs retornados.</small>
            </div>
            <div class="form-row">
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="view_only" name="view_only" value="1" <?= !empty($values['view_only']) ? 'checked' : '' ?> />
                  <label for="view_only" class="form-check-label">Somente visualização (bloqueia download)</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="can_download" name="can_download" value="1" <?= !empty($values['can_download']) ? 'checked' : '' ?> />
                  <label for="can_download" class="form-check-label">Permitir download</label>
                </div>
              </div>
              <div class="col-md-4">
                <label>Expira em (opcional)</label>
                <input type="datetime-local" class="form-control" name="expires_at" value="<?= htmlspecialchars($values['expires_at']); ?>">
              </div>
            </div>
            <div class="form-group mt-3">
              <label>Mensagem ao destinatário (opcional)</label>
              <textarea name="message" class="form-control" rows="3" placeholder="Mensagem adicional a ser incluída no e-mail"><?= htmlspecialchars($values['message']); ?></textarea>
            </div>
            <hr>
            <div class="form-group">
              <label>Buscar usuários</label>
              <input type="text" id="user-search" class="form-control" placeholder="Comece a digitar para buscar...">
              <small class="form-text text-muted">Digite para buscar; copie o ID exibido para o campo acima.</small>
              <div id="user-results" class="list-group mt-2"></div>
            </div>
          </div>
          <div class="card-footer"><button class="btn btn-primary" type="submit">Conceder acesso</button> <a href="documentos_propriedades.php?id=<?= (int)$documento_id; ?>" class="btn btn-secondary">Cancelar</a></div>
        </form>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
<script>
(function(){
  const input = document.getElementById('user-search');
  const results = document.getElementById('user-results');
  if (!input || !results) return;
  let t = null;
  input.addEventListener('input', function(){
    clearTimeout(t);
    const q = this.value.trim();
    if (q.length < 2) { results.innerHTML = ''; return; }
    t = setTimeout(() => {
      fetch('ajax_buscar_usuarios.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
        .then(r => r.json()).then(rows => {
          results.innerHTML = '';
          (rows||[]).forEach(u => {
            const a = document.createElement('a');
            a.href = '#'; a.className = 'list-group-item list-group-item-action';
            a.textContent = `#${u.id} · ${u.text}`;
            a.addEventListener('click', (e) => { e.preventDefault(); const f = document.getElementById('users'); f.value = (f.value? f.value + ', ' : '') + u.id; });
            results.appendChild(a);
          });
        }).catch(()=>{});
    }, 200);
  });
})();
</script>
