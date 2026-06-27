<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/share_helper.php';
require_once PROJECT_ROOT . '/core/email.php';

require_auth();
require_permission('document.share');

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id <= 0) { header('Location: documentos.php'); exit(); }

// Valores do formulário e erros (para repopular em caso de erro)
$form_values = [
  'senha' => '',
  'expira_em' => '',
  'max_downloads' => '',
  'emails' => '',
        'usuarios' => [],
    'mensagem' => '',
    'view_only' => 0,
        'one_time' => 0,
        'force_watermark' => 0
];
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        http_response_code(403);
        exit('CSRF inválido');
    }
    $senha = trim($_POST['senha'] ?? '');
    $expira_em_in = trim($_POST['expira_em'] ?? '');
    $max_downloads_in = trim($_POST['max_downloads'] ?? '');
    $emails_raw = trim($_POST['emails'] ?? '');
    $usuarios_ids = isset($_POST['usuarios']) && is_array($_POST['usuarios']) ? array_filter(array_map('intval', $_POST['usuarios'])) : [];
    $mensagem = trim($_POST['mensagem'] ?? '');
    $view_only = isset($_POST['view_only']) ? 1 : 0;
    $one_time = isset($_POST['one_time']) ? 1 : 0;
    $force_wm = isset($_POST['force_watermark']) ? 1 : 0;

    $form_values = [
      'senha' => $senha,
      'expira_em' => $expira_em_in,
      'max_downloads' => $max_downloads_in,
      'emails' => $emails_raw,
            'usuarios' => $usuarios_ids,
            'mensagem' => $mensagem,
            'view_only' => $view_only,
        'one_time' => $one_time,
        'force_watermark' => $force_wm
    ];

    // Validações
    $expira_em = null;
    if ($expira_em_in !== '') {
        $expira_norm = str_replace('T', ' ', $expira_em_in);
        $dt = DateTime::createFromFormat('Y-m-d H:i', $expira_norm) ?: DateTime::createFromFormat('Y-m-d H:i:s', $expira_norm);
        if (!$dt) {
            $form_errors[] = 'Data de expiração inválida.';
        } else {
            if ($dt < new DateTime()) {
                $form_errors[] = 'A expiração deve ser no futuro.';
            } else {
                $expira_em = $dt->format('Y-m-d H:i:s');
            }
        }
    }

    $max_downloads = null;
    if ($max_downloads_in !== '') {
        if (!ctype_digit($max_downloads_in) || (int)$max_downloads_in <= 0) {
            $form_errors[] = 'Máximo de downloads deve ser um número inteiro positivo.';
        } else {
            $max_downloads = (int)$max_downloads_in;
        }
    }
    if ($one_time && $max_downloads === null) { $max_downloads = 1; }

    $validos = [];
    $invalidos = [];
    if ($emails_raw !== '') {
        $emails = preg_split('/[;\,\s]+/', $emails_raw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = array_values(array_unique(array_map('trim', $emails)));
        if (count($emails) > 50) {
            $form_errors[] = 'Limite de 50 destinatários por envio.';
            $emails = array_slice($emails, 0, 50);
        }
        foreach ($emails as $e) {
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) { $validos[] = $e; } else { $invalidos[] = $e; }
        }
    }

    // Usuários internos: buscar e-mails e nomes
    $usuarios_emails = [];
    $usuarios_nomes = [];
    if (!empty($usuarios_ids)) {
        try {
            $place = implode(',', array_fill(0, count($usuarios_ids), '?'));
            $st = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE status='ativo' AND id IN ($place)");
            $st->execute($usuarios_ids);
            while ($u = $st->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($u['email']) && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
                    $usuarios_emails[] = strtolower(trim($u['email']));
                    $usuarios_nomes[(int)$u['id']] = $u['nome'];
                }
            }
        } catch (Throwable $e) { /* silencioso */ }
    }

    if (empty($form_errors)) {
        // Criar link
    $res = criar_link_compartilhado($pdo, $documento_id, (int)$_SESSION['user_id'], $senha ?: null, $expira_em ?: null, $max_downloads, (bool)$view_only, (bool)$force_wm);
        if ($res['ok']) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = sprintf('%s://%s/ged/public/compartilhar_download.php?code=%s', $scheme, $host, $res['code']);

            // Flags aplicadas na criação quando suportadas pela base de dados

            // Obter título do documento
            $doc = null;
            try {
                $sd = $pdo->prepare("SELECT id, titulo FROM documentos WHERE id = ?");
                $sd->execute([$documento_id]);
                $doc = $sd->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}

            // Merge com e-mails de usuários internos e de-duplicar
            if (!empty($usuarios_emails)) {
                $validos = array_values(array_unique(array_merge($validos, $usuarios_emails)));
            }

            // Enviar e-mails válidos
            $envios_ok = 0; $envios_fail = 0; $erros = [];
            if (!empty($validos)) {
                $data = [
                    'titulo' => (string)($doc['titulo'] ?? 'Documento'),
                    'url' => $url,
                    'validade' => $expira_em ? date('d/m/Y H:i', strtotime($expira_em)) : 'Sem expiração',
                    'mensagem' => $mensagem,
                ];
                foreach ($validos as $dest) {
                    try {
                        $ok = email_send_template($pdo, $dest, 'compartilhar_link', $data);
                        if ($ok) $envios_ok++; else { $envios_fail++; $erros[] = $dest; }
                    } catch (Throwable $ex) {
                        $envios_fail++; $erros[] = $dest;
                    }
                }
            }

            // Notificações internas para usuários selecionados
            if (!empty($usuarios_ids)) {
                try {
                    $sn = $pdo->prepare("INSERT INTO workflow_notificacoes (usuario_id, tipo, mensagem, data_envio, lida) VALUES (?, 'share', ?, NOW(), 0)");
                    $msgNotif = 'Documento compartilhado: ' . (string)($doc['titulo'] ?? 'Documento');
                    foreach ($usuarios_ids as $uid) { $sn->execute([(int)$uid, $msgNotif]); }
                } catch (Throwable $e) { /* silencioso */ }
            }

            // Auditoria
            try {
                $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
                $st->execute([(int)$_SESSION['user_id'], 'SHARE_CREATE', 'document', $documento_id, json_encode(['url'=>$url,'max_downloads'=>$max_downloads,'expira_em'=>$expira_em,'emails_enviados'=>$envios_ok,'emails_falha'=>$envios_fail], JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
            } catch (Throwable $e) {}

            // Mensagem de sucesso
            $msg = 'Link criado: ' . $url;
            if ($emails_raw !== '') {
                $msg .= sprintf(' | E-mails: %d enviado(s), %d falhou(falharam)', $envios_ok, $envios_fail);
                if (!empty($invalidos)) {
                    $msg .= ' | Inválidos: ' . implode(', ', array_map('htmlspecialchars', array_slice($invalidos, 0, 5))) . (count($invalidos) > 5 ? '…' : '');
                }
            }
            if (!empty($usuarios_ids)) {
                $msg .= ' | Usuários notificados: ' . count($usuarios_ids);
            }
            $msg .= ' | <a href="documentos_propriedades.php?id=' . (int)$documento_id . '#links-share">Gerenciar links</a>';
            $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => $msg];

            // Disponibiliza a URL para modal pós-criação nas propriedades
            $_SESSION['link_criado_url'] = $url;

            header('Location: documentos_propriedades.php?id=' . $documento_id);
            exit();
        } else {
            $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao criar link'];
        }
    }
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<!-- Assets Select2 (para usuários do sistema) -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Compartilhar Documento</h1></div></section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-primary">
        <div class="card-header">Criar link compartilhado</div>
        <form method="post">
          <div class="card-body">
            <?= csrf_input(); ?>
            <?php if (!empty($form_errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($form_errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
                        <div class="form-group">
                                <label>Usuários do sistema</label>
                                <select name="usuarios[]" id="usuarios_select" class="form-control" multiple data-placeholder="Selecione usuários...">
                                        <?php if (!empty($form_values['usuarios'])): ?>
                                                <?php
                                                // Repopular selecionados (id -> nome)
                                                try {
                                                        $ids = array_filter(array_map('intval', (array)$form_values['usuarios']));
                                                        if ($ids) {
                                                                $place = implode(',', array_fill(0, count($ids), '?'));
                                                                $stp = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id IN ($place)");
                                                                $stp->execute($ids);
                                                                while ($u = $stp->fetch(PDO::FETCH_ASSOC)) {
                                                                        echo '<option value="'.(int)$u['id'].'" selected>'.htmlspecialchars($u['nome']).'</option>';
                                                                }
                                                        }
                                                } catch (Throwable $e) {}
                                                ?>
                                        <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Busque por nome ou e-mail. Os selecionados também receberão o link por e-mail e notificação.</small>
                        </div>
            <div class="form-group"><label>Senha (opcional)</label><input type="password" name="senha" class="form-control" placeholder="Defina uma senha para abrir" value="<?= htmlspecialchars($form_values['senha']); ?>"></div>
            <div class="form-group"><label>Expira em (opcional)</label><input type="datetime-local" name="expira_em" class="form-control" value="<?= htmlspecialchars($form_values['expira_em']); ?>"></div>
            <div class="form-group"><label>Máximo de downloads (opcional)</label><input type="number" name="max_downloads" class="form-control" min="1" value="<?= htmlspecialchars($form_values['max_downloads']); ?>"></div>
            <hr>
                        <div class="form-group">
                            <label>Enviar por e-mail (opcional)</label>
                            <input type="text" name="emails" id="emails" class="form-control" placeholder="Digite e-mails separados por vírgula, ponto e vírgula ou espaço" value="<?= htmlspecialchars($form_values['emails']); ?>" list="emails_suggestions">
                            <datalist id="emails_suggestions"></datalist>
                            <small class="form-text text-muted">Dica: comece a digitar para ver sugestões recentes.</small>
                        </div>
                        <div class="form-group"><label>Mensagem ao destinatário (opcional)</label><textarea name="mensagem" class="form-control" rows="3" placeholder="Mensagem adicional a ser incluída no e-mail"><?= htmlspecialchars($form_values['mensagem']); ?></textarea></div>
                                                <div class="form-group form-check">
                                                        <input type="checkbox" class="form-check-input" id="aceite" name="aceite" value="1" required>
                                                        <label for="aceite" class="form-check-label">Estou CIENTE e ACEITO a responsabilidade legal ao compartilhar e conceder acesso a este documento aos destinatários selecionados acima.</label>
                                                </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="view_only" name="view_only" value="1" <?= !empty($form_values['view_only']) ? 'checked' : '' ?> />
                                    <label for="view_only" class="form-check-label">Somente visualização (aplica marca d'água e desativa download)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="one_time" name="one_time" value="1" <?= !empty($form_values['one_time']) ? 'checked' : '' ?> />
                                    <label for="one_time" class="form-check-label">Desativar após primeiro acesso</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row mt-2">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="force_watermark" name="force_watermark" value="1" <?= !empty($form_values['force_watermark']) ? 'checked' : '' ?> />
                                    <label for="force_watermark" class="form-check-label">Forçar marca d'água (mesmo sem visualização inline)</label>
                                </div>
                            </div>
                        </div>
          </div>
          <div class="card-footer"><button class="btn btn-primary" type="submit">Gerar Link</button></div>
        </form>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/plugins/select2/js/select2.full.min.js"></script>
<script>
(function(){
    // Select2 para usuários
    var $sel = $('#usuarios_select');
    if ($sel.length) {
        $sel.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: $sel.data('placeholder') || 'Selecione usuários...',
            ajax: {
                url: 'ajax_buscar_usuarios.php',
                dataType: 'json', delay: 200,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    var results = (Array.isArray(data) ? data : []).map(function(it){ return { id: it.id, text: it.text }; });
                    return { results: results };
                }
            },
            minimumInputLength: 1
        });
    }

    const input = document.getElementById('emails');
    const datalist = document.getElementById('emails_suggestions');
    if (!input || !datalist) return;
    let timer = null;
    function fetchSuggestions(q) {
        const url = 'ajax_suggest_emails.php?q=' + encodeURIComponent(q||'');
        fetch(url, { credentials: 'same-origin' }).then(r=>r.json()).then(data => {
            datalist.innerHTML = '';
            (data.suggestions||[]).forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                datalist.appendChild(opt);
            });
        }).catch(()=>{});
    }
    input.addEventListener('focus', () => fetchSuggestions(''));
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const val = input.value || '';
            // Tenta sugerir para o último token
            const parts = val.split(/[;,\s]+/);
            const last = parts[parts.length-1].trim();
            if (last.length >= 2) fetchSuggestions(last);
        }, 200);
    });
})();
</script>
