<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();
require_permission('email.templates.manage');

header('Content-Type: text/html; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'ID inválido'; exit; }

$stmt = $pdo->prepare('SELECT id, slug, nome, assunto, corpo, corpo_html, corpo_texto, ativo, updated_at FROM email_templates WHERE id = ?');
$stmt->execute([$id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) { http_response_code(404); echo 'Template não encontrado'; exit; }

// Header/Footer padrão do app (para a opção de aplicar layout)
$headerHtml = '';
$footerHtml = '';
try {
  $st = $pdo->prepare("SELECT chave, valor FROM app_settings WHERE chave IN ('email_header_html','email_footer_html')");
  $st->execute();
  foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if ($r['chave'] === 'email_header_html') $headerHtml = (string)$r['valor'];
    if ($r['chave'] === 'email_footer_html') $footerHtml = (string)$r['valor'];
  }
} catch (Throwable $e) { /* silencioso */ }

// Dados de exemplo para pré-visualização
$sample = [
  'nome' => 'Fulano de Tal',
  'email' => 'fulano@example.com',
  'usuario' => [ 'nome' => 'Fulano Admin', 'email' => 'admin@example.com' ],
  'documento' => [
    'titulo' => 'Contrato de Prestação de Serviços',
    'vencimento' => '31/12/2025',
    'link' => 'https://exemplo.local/documentos/123',
  ],
  'link' => 'https://exemplo.local/acao/XYZ',
  'mensagem' => 'Segue o documento conforme solicitado.',
  'dias' => '7',
  'expira_em' => '24 horas',
];

function resolve_path($data, $path) {
  $parts = explode('.', $path);
  $value = $data;
  foreach ($parts as $p) {
    if (is_array($value) && array_key_exists($p, $value)) {
      $value = $value[$p];
    } else { return null; }
  }
  return $value;
}

function render_placeholders($text, $data) {
  // Sintaxe: {{chave}} ou {{chave|Padrao}}
  return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)(?:\|([^}]+))?\s*\}\}/u', function ($m) use ($data) {
    $key = $m[1];
    $default = isset($m[2]) ? $m[2] : '';
    $val = resolve_path($data, $key);
    if ($val === null || $val === '') { $val = $default; }
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }, $text);
}

$subject = render_placeholders($template['assunto'] ?? '', $sample);
// Suporte a esquemas novos (corpo_html/corpo_texto) e antigos (corpo)
$rawBody = isset($template['corpo_html']) && $template['corpo_html'] !== null && $template['corpo_html'] !== ''
  ? $template['corpo_html']
  : ($template['corpo'] ?? '');
$body = (string)$rawBody;
$bodyRendered = render_placeholders($body, $sample);

// Detecta se o corpo parece HTML
$isHtml = strip_tags($body) !== $body || preg_match('/<\w+[^>]*>/', $body);
if (!$isHtml) {
  $bodyRendered = nl2br($bodyRendered);
}

$brand = defined('BRAND_NAME') ? BRAND_NAME : 'eDok';
$primary = '#00c9a7';
$bg = '#f5f6f8';
$cardBg = '#ffffff';
$muted = '#6c757d';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pré-visualização • <?= htmlspecialchars($template['nome']); ?></title>
  <style>
    body{margin:0;background:<?= $bg ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;}
    .container{max-width:720px;margin:32px auto;padding:0 16px;}
    .card{background:<?= $cardBg ?>;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.08);overflow:hidden;border:1px solid #e6eaef;}
    .card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #eef1f5;background:#fff;}
    .brand{display:flex;align-items:center;gap:10px;font-weight:600;color:#1f2d3d}
    .brand .dot{width:10px;height:10px;background:<?= $primary ?>;border-radius:50%}
    .subject{color:#1f2d3d;font-size:16px;font-weight:600;margin:18px 22px 0}
    .meta{color:<?= $muted ?>;font-size:12px;margin:6px 22px 0}
    .body{padding:22px;color:#1f2d3d;line-height:1.6}
  .toolbar{display:flex;gap:8px;justify-content:space-between;align-items:center;padding:12px 16px}
  .toolbar .left{display:flex;gap:8px;align-items:center}
    .btn{border:1px solid #d8dee6;background:#fff;border-radius:6px;padding:8px 12px;font-size:12px;color:#1f2d3d;text-decoration:none}
    .btn.primary{border-color:<?= $primary ?>;background:<?= $primary ?>;color:#fff}
    code{background:#f1f3f5;border-radius:4px;padding:2px 4px}
  </style>
</head>
<body>
  <div class="container">
    <div class="toolbar">
      <div class="left">
        <a class="btn" href="admin_email_templates.php">Voltar</a>
        <a class="btn primary" href="admin_email_template_edit.php?id=<?= (int)$template['id'] ?>">Editar</a>
      </div>
      <div class="right">
        <label style="margin-right:8px;font-size:12px;display:inline-flex;align-items:center;gap:6px">
          <input type="checkbox" id="apply-layout"> Aplicar layout padrão
        </label>
        <button id="w-mobile" class="btn" type="button">Mobile</button>
        <button id="w-desktop" class="btn" type="button">Desktop</button>
        <button id="btn-copy-subj" class="btn" type="button">Copiar assunto</button>
        <button id="btn-copy-body" class="btn" type="button">Copiar corpo</button>
      </div>
    </div>
    <div class="card" style="margin-bottom:16px;">
      <div class="card-head">
        <div class="brand"><span class="dot"></span><span>Dados da prévia</span></div>
        <div style="color:#6c757d;font-size:12px">Edite o JSON e clique em Atualizar</div>
      </div>
      <div class="body">
        <textarea id="json-data" style="width:100%;min-height:180px;padding:12px;border:1px solid #e6eaef;border-radius:6px;font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
<?= htmlspecialchars(json_encode($sample, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
        <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
          <button id="btn-update" class="btn primary" type="button">Atualizar prévia</button>
          <button id="btn-restore" class="btn" type="button">Restaurar exemplo</button>
        </div>
      </div>
    </div>
  <div class="card" id="viewport" style="margin:0 auto;max-width:700px;">
      <div class="card-head">
        <div class="brand"><span class="dot"></span><span><?= htmlspecialchars($brand) ?></span></div>
        <div style="color:<?= $muted ?>;font-size:12px">Pré-visualização</div>
      </div>
      <div class="subject" id="preview-subject"><?= $subject ?></div>
      <div class="meta">Slug: <code><?= htmlspecialchars($template['slug']) ?></code> • Atualizado: <?= htmlspecialchars($template['updated_at']) ?></div>
      <div class="body" id="preview-body"><?= $bodyRendered ?></div>
    </div>
    <div id="tmpl" data-subject="<?= htmlspecialchars($template['assunto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
      data-body="<?= htmlspecialchars(($template['corpo_html'] ?? ($template['corpo'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
      data-is-html="<?= $isHtml ? '1' : '0' ?>"
      data-hdr="<?= htmlspecialchars($headerHtml ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
      data-ftr="<?= htmlspecialchars($footerHtml ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
      style="display:none"></div>
  </div>
  <script>
    (function(){
      function escapeHtml(s){
        return String(s)
          .replace(/&/g,'&amp;')
          .replace(/</g,'&lt;')
          .replace(/>/g,'&gt;')
          .replace(/"/g,'&quot;')
          .replace(/'/g,'&#39;');
      }
      function resolvePath(obj, path){
        var parts = path.split('.');
        var v = obj;
        for (var i=0;i<parts.length;i++){
          if (v && typeof v === 'object' && (parts[i] in v)) v = v[parts[i]]; else return '';
        }
        return v == null ? '' : v;
      }
      function renderPlaceholders(text, data){
        if (!text) return '';
        return text.replace(/\{\{\s*([a-zA-Z0-9_.]+)(?:\|([^}]+))?\s*\}\}/g, function(_, key, def){
          var val = resolvePath(data, key);
          if (val === '' || val === undefined || val === null) val = def || '';
          return escapeHtml(String(val));
        });
      }
      var jsonEl = document.getElementById('json-data');
      var btnUpdate = document.getElementById('btn-update');
      var btnRestore = document.getElementById('btn-restore');
  var subjectEl = document.getElementById('preview-subject');
  var bodyEl = document.getElementById('preview-body');
  var viewport = document.getElementById('viewport');
  var btnCopySubj = document.getElementById('btn-copy-subj');
  var btnCopyBody = document.getElementById('btn-copy-body');
  var wMobile = document.getElementById('w-mobile');
  var wDesktop = document.getElementById('w-desktop');
      var tmpl = document.getElementById('tmpl');
      var subjectTpl = tmpl.getAttribute('data-subject') || '';
      var bodyTpl = tmpl.getAttribute('data-body') || '';
      var isHtml = tmpl.getAttribute('data-is-html') === '1';
      var hdr = tmpl.getAttribute('data-hdr') || '';
      var ftr = tmpl.getAttribute('data-ftr') || '';
      var applyLayout = document.getElementById('apply-layout');

      var originalJson = jsonEl.value;
      btnRestore.addEventListener('click', function(){ jsonEl.value = originalJson; });
      btnUpdate.addEventListener('click', function(){
        var data;
        try { data = JSON.parse(jsonEl.value); }
        catch(e){ alert('JSON inválido: ' + e.message); return; }
        var subj = renderPlaceholders(subjectTpl, data);
        var body = renderPlaceholders(bodyTpl, data);
        subjectEl.textContent = subj;
        var content = isHtml ? body : body.replace(/\n/g,'<br>');
        if (applyLayout && applyLayout.checked && (hdr || ftr)) {
          content = (hdr || '') + content + (ftr || '');
        }
        bodyEl.innerHTML = content;
      });
      if (applyLayout) applyLayout.addEventListener('change', function(){ btnUpdate.click(); });
      function copyText(text){
        if (!navigator.clipboard) { alert('Copie manualmente: '+ text); return; }
        navigator.clipboard.writeText(text).then(function(){ alert('Copiado!'); }).catch(function(){ alert('Falha ao copiar'); });
      }
      if (btnCopySubj) btnCopySubj.addEventListener('click', function(){ copyText(subjectEl.textContent || ''); });
      if (btnCopyBody) btnCopyBody.addEventListener('click', function(){ copyText(bodyEl.innerText || bodyEl.textContent || ''); });
      if (wMobile) wMobile.addEventListener('click', function(){ viewport.style.maxWidth = '390px'; });
      if (wDesktop) wDesktop.addEventListener('click', function(){ viewport.style.maxWidth = '700px'; });
      // Render inicial respeitando toggle
      if (applyLayout) btnUpdate.click();
    })();
  </script>
</body>
</html>
