<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_auth();
require_permission('email.templates.manage');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tpl_defaults = [
  'id' => 0,
  'nome' => '',
  'slug' => '',
  'assunto' => '',
  'corpo_html' => '',
  'corpo_texto' => '',
  'variaveis_json' => '{"exemplo":"valor"}',
  'ativo' => 1,
];
$tpl = $tpl_defaults;

if ($id) {
  $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) {
    $tpl = array_merge($tpl_defaults, $row);
  }
}

// Garantir que os valores que podem ser nulos sejam strings vazias para evitar warnings
$tpl['corpo_html'] = $tpl['corpo_html'] ?? '';
$tpl['corpo_texto'] = $tpl['corpo_texto'] ?? '';
$tpl['variaveis_json'] = $tpl['variaveis_json'] ?? '';

// Dados de exemplo e JSON inicial para a prévia ao vivo
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
$initialJson = '';
if (!empty($tpl['variaveis_json'])) {
  $try = json_decode((string)$tpl['variaveis_json'], true);
  if (is_array($try)) {
    $initialJson = json_encode($try, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  }
}
if ($initialJson === '') {
  $initialJson = json_encode($sample, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

// Header/Footer padrão configuráveis para a prévia
$headerHtml = '';
$footerHtml = '';
try {
  $st = $pdo->prepare("SELECT chave, valor FROM app_settings WHERE chave IN ('email_header_html','email_footer_html')");
  $st->execute();
  foreach ($st->fetchAll() as $r) {
    if ($r['chave'] === 'email_header_html') $headerHtml = (string)$r['valor'];
    if ($r['chave'] === 'email_footer_html') $footerHtml = (string)$r['valor'];
  }
} catch (Throwable $e) { /* silencioso */ }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1><?= $id ? 'Editar Template' : 'Novo Template' ?></h1></div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-7">
          <div class="card card-outline card-info mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Variáveis do sistema</h3>
              <div style="width:240px" class="input-group input-group-sm">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                <input id="vars-search" type="text" class="form-control" placeholder="Filtrar variáveis...">
              </div>
            </div>
            <div class="card-body">
              <div class="text-muted mb-2">Clique para inserir no campo focado (shift+clique pergunta um padrão).</div>
              <div id="vars-container" class="d-flex flex-wrap" style="gap:.5rem"></div>
            </div>
          </div>
          <form method="post" action="admin_email_template_save.php">
            <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
            <input type="hidden" name="id" value="<?= (int)$tpl['id'] ?>">
            <div class="card card-dark card-outline">
              <div class="card-body">
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label>Nome</label>
                    <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($tpl['nome']) ?>">
                  </div>
                  <div class="form-group col-md-6">
                    <label>Slug (único)</label>
                    <input type="text" name="slug" class="form-control" required value="<?= htmlspecialchars($tpl['slug']) ?>" <?= $id ? '' : '' ?> placeholder="ex: compartilhar_link">
                    <small class="form-text text-muted">Use letras minúsculas, números e _</small>
                  </div>
                </div>
                <div class="form-group">
                  <label>Assunto</label>
                  <input type="text" name="assunto" id="tpl-assunto" class="form-control" required value="<?= htmlspecialchars($tpl['assunto']) ?>">
                </div>
                <div class="form-group">
                  <label>Corpo HTML</label>
                  <textarea name="corpo_html" id="tpl-corpo-html" class="form-control" rows="10" placeholder="<p>Olá {{nome}}, ...</p>"><?= htmlspecialchars($tpl['corpo_html'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Corpo Texto (opcional)</label>
                  <textarea name="corpo_texto" id="tpl-corpo-texto" class="form-control" rows="6" placeholder="Olá {{nome}}, ..."><?= htmlspecialchars($tpl['corpo_texto'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-8">
                    <label>Variáveis (JSON, documentativo)</label>
                    <textarea name="variaveis_json" id="tpl-variaveis" class="form-control" rows="4"><?= htmlspecialchars($tpl['variaveis_json'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Somente para referência/ajuda. No envio real, os dados são passados pela aplicação.</small>
                  </div>
                  <div class="form-group col-md-4">
                    <label>Status</label>
                    <select name="ativo" class="form-control">
                      <option value="1" <?= $tpl['ativo'] ? 'selected' : '' ?>>Ativo</option>
                      <option value="0" <?= !$tpl['ativo'] ? 'selected' : '' ?>>Inativo</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="admin_email_templates.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
              </div>
            </div>
          </form>
        </div>
        <div class="col-lg-5">
          <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Pré-visualização ao vivo</h3>
              <div class="d-flex align-items-center" role="group" aria-label="Viewport" style="gap:8px">
                <label class="mb-0" style="font-size:12px;display:inline-flex;align-items:center;gap:6px">
                  <input type="checkbox" id="pv-apply-layout"> Aplicar layout padrão
                </label>
                <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="pv-mobile">Mobile</button>
                <button type="button" class="btn btn-outline-secondary" id="pv-desktop">Desktop</button>
                <a class="btn btn-outline-primary" href="admin_email_template_test.php?id=<?= (int)$tpl['id'] ?>" title="Teste de envio"><i class="fas fa-paper-plane"></i></a>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label>Dados da prévia (JSON)</label>
                <textarea id="pv-json" class="form-control" rows="8"><?= htmlspecialchars($initialJson) ?></textarea>
                <div class="text-right mt-2">
                  <button type="button" class="btn btn-sm btn-primary" id="pv-update">Atualizar prévia</button>
                  <button type="button" class="btn btn-sm btn-default" id="pv-restore">Restaurar exemplo</button>
                </div>
              </div>
              <div class="border rounded p-2" style="background:#f8f9fa">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Assunto:</strong>
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" id="pv-copy-subj">Copiar assunto</button>
                    <button type="button" class="btn btn-outline-secondary" id="pv-copy-body">Copiar corpo</button>
                  </div>
                </div>
                <div id="pv-subject" class="mb-2" style="word-break:break-word"></div>
                <div id="pv-viewport" class="border rounded bg-white p-3" style="max-width:700px;margin:0 auto">
                  <div id="pv-body" style="line-height:1.6;color:#1f2d3d"></div>
                </div>
              </div>
              <small class="text-muted d-block mt-2">Use {{chave}} ou {{chave|Padrão}}. Suporta caminhos: {{usuario.nome}}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
<script>
(function(){
  // Utilitários de renderização (mesma lógica da página de prévia)
  function escapeHtml(s){
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
  function resolvePath(obj, path){
    var parts = path.split('.'); var v = obj;
    for (var i=0;i<parts.length;i++){ if (v && typeof v==='object' && (parts[i] in v)) v=v[parts[i]]; else return ''; }
    return v==null ? '' : v;
  }
  function renderPlaceholders(text, data){
    if (!text) return '';
    return text.replace(/\{\{\s*([a-zA-Z0-9_.]+)(?:\|([^}]+))?\s*\}\}/g, function(_, key, def){
      var val = resolvePath(data, key);
      if (val === '' || val === undefined || val === null) val = def || '';
      return escapeHtml(String(val));
    });
  }

  var elAssunto = document.getElementById('tpl-assunto');
  var elHtml = document.getElementById('tpl-corpo-html');
  var elTxt = document.getElementById('tpl-corpo-texto');
  var elVars = document.getElementById('tpl-variaveis');

  var pvJson = document.getElementById('pv-json');
  var pvUpdate = document.getElementById('pv-update');
  var pvRestore = document.getElementById('pv-restore');
  var pvSubject = document.getElementById('pv-subject');
  var pvBody = document.getElementById('pv-body');
  var pvViewport = document.getElementById('pv-viewport');
  var btnCopySubj = document.getElementById('pv-copy-subj');
  var btnCopyBody = document.getElementById('pv-copy-body');
  var btnMobile = document.getElementById('pv-mobile');
  var btnDesktop = document.getElementById('pv-desktop');
  var varsContainer = document.getElementById('vars-container');
  var varsSearch = document.getElementById('vars-search');
  var pvApplyLayout = document.getElementById('pv-apply-layout');
  var hdr = "<?= htmlspecialchars($headerHtml ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>";
  var ftr = "<?= htmlspecialchars($footerHtml ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>";

  var originalJson = pvJson.value;
  pvRestore.addEventListener('click', function(){ pvJson.value = originalJson; });

  function parseJson(){
    try { return JSON.parse(pvJson.value); }
    catch(e){ console.warn('JSON inválido', e); return {}; }
  }
  function updateVarKeysFromJson(){
    try { varKeys = flattenKeys(JSON.parse(pvJson.value || '{}'), ''); }
    catch(e){ varKeys = []; }
    // merge com placeholders detectados no assunto/corpos
    var detected = extractPlaceholders((elAssunto.value||'') + '\n' + (elHtml.value||'') + '\n' + (elTxt.value||''));
    var all = varKeys.concat(detected);
    varKeys = Array.from(new Set(all)).sort();
  }
  function detectHtml(s){
    if (!s) return false;
    return /<\w+[^>]*>/.test(s) || s.replace(/<[^>]*>/g,'') !== s;
  }
  function getBodyTemplate(){
    var h = elHtml.value.trim();
    if (h) return { tpl: h, isHtml: true };
    var t = elTxt.value || '';
    return { tpl: t, isHtml: detectHtml(t) };
  }
  function extractPlaceholders(text){
    var out = [];
    if (!text) return out;
    var re = /\{\{\s*([a-zA-Z0-9_.]+)(?:\|[^}]+)?\s*\}\}/g; var m;
    while ((m = re.exec(text))){ out.push(m[1]); }
    return Array.from(new Set(out));
  }
  function render(){
    var data = parseJson();
    var subj = renderPlaceholders(elAssunto.value || '', data);
    var bodyInfo = getBodyTemplate();
    var body = renderPlaceholders(bodyInfo.tpl || '', data);
    pvSubject.textContent = subj;
    var content = bodyInfo.isHtml ? body : String(body).replace(/\n/g,'<br>');
    if (pvApplyLayout && pvApplyLayout.checked && (hdr || ftr)) {
      content = (hdr || '') + content + (ftr || '');
    }
    pvBody.innerHTML = content;
  }

  pvUpdate.addEventListener('click', render);
  pvUpdate.addEventListener('click', function(){ updateVarKeysFromJson(); renderVars(varsSearch ? varsSearch.value : ''); });
  [elAssunto, elHtml, elTxt].forEach(function(el){ el.addEventListener('input', render); });
  [elAssunto, elHtml, elTxt].forEach(function(el){ el.addEventListener('input', function(){ updateVarKeysFromJson(); renderVars(varsSearch ? varsSearch.value : ''); }); });

  function copyText(text){
    if (!navigator.clipboard) { alert('Copie manualmente'); return; }
    navigator.clipboard.writeText(text).then(function(){ /* ok */ }).catch(function(){ alert('Falha ao copiar'); });
  }
  if (btnCopySubj) btnCopySubj.addEventListener('click', function(){ copyText(pvSubject.textContent || ''); });
  if (btnCopyBody) btnCopyBody.addEventListener('click', function(){ copyText(pvBody.innerText || pvBody.textContent || ''); });
  if (btnMobile) btnMobile.addEventListener('click', function(){ pvViewport.style.maxWidth = '390px'; });
  if (btnDesktop) btnDesktop.addEventListener('click', function(){ pvViewport.style.maxWidth = '700px'; });
  if (pvApplyLayout) pvApplyLayout.addEventListener('change', render);

  // Inicializa prévia com JSON inicial
  render();

  // ===== Paleta de variáveis: gerar a partir do JSON e inserir no campo focado =====
  function flattenKeys(obj, prefix){
    var keys = [];
    if (obj && typeof obj === 'object' && !Array.isArray(obj)){
      Object.keys(obj).forEach(function(k){
        var full = prefix ? prefix + '.' + k : k;
        var v = obj[k];
        if (v && typeof v === 'object' && !Array.isArray(v)) {
          keys = keys.concat(flattenKeys(v, full));
        } else {
          keys.push(full);
        }
      });
    }
    return keys;
  }
  var varKeys = [];
  updateVarKeysFromJson();
  // Deixar únicos e ordenados
  varKeys = Array.from(new Set(varKeys)).sort();

  function renderVars(filter){
    if (!varsContainer) return;
    varsContainer.innerHTML = '';
    varKeys.forEach(function(k){
      if (filter && k.toLowerCase().indexOf(filter.toLowerCase()) === -1) return;
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm btn-outline-secondary';
      btn.textContent = '{{' + k + '}}';
      btn.title = 'Clique para inserir. Shift+clique para inserir com padrão';
      btn.addEventListener('click', function(ev){
        var placeholder = '{{' + k + '}}';
        if (ev.shiftKey) {
          var def = prompt('Padrão (opcional) para ' + k + ':', '');
          if (def && def.trim()) placeholder = '{{' + k + '|' + def.trim() + '}}';
        }
        insertIntoFocused(placeholder);
      });
      varsContainer.appendChild(btn);
    });
  }

  if (varsSearch) varsSearch.addEventListener('input', function(){ renderVars(varsSearch.value || ''); });

  var lastFocused = elHtml; // padrão
  [elAssunto, elHtml, elTxt].forEach(function(el){
    el.addEventListener('focus', function(){ lastFocused = el; });
  });
  function insertAtCursor(el, text){
    if (!el) return;
    var start = el.selectionStart || 0;
    var end = el.selectionEnd || 0;
    var val = el.value || '';
    el.value = val.substring(0, start) + text + val.substring(end);
    // reposiciona o cursor após o texto inserido
    var pos = start + text.length;
    if (el.setSelectionRange) {
      el.setSelectionRange(pos, pos);
      el.focus();
    }
    // dispara evento de input para atualizar a prévia
    var evt = new Event('input', { bubbles: true });
    el.dispatchEvent(evt);
  }
  function insertIntoFocused(text){
    var target = lastFocused || elHtml;
    insertAtCursor(target, text);
  }

  renderVars('');
  // ============ aviso de saída sem salvar ============
  var form = document.querySelector('form[action="admin_email_template_save.php"]');
  var dirty = false;
  function markDirty(){ dirty = true; }
  [elAssunto, elHtml, elTxt, elVars].forEach(function(el){ el.addEventListener('input', markDirty); });
  if (form) form.addEventListener('submit', function(){ dirty = false; });
  window.addEventListener('beforeunload', function(e){ if (!dirty) return; e.preventDefault(); e.returnValue = ''; });
})();
</script>
