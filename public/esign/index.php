<?php
// public/esign/index.php (limpo e com modal de carregamento estilo eDok)
require_once '../../core/init.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id === 0) { header('Location: ../documentos.php'); exit(); }

try {
        $stmt = $pdo->prepare('SELECT id, titulo FROM documentos WHERE id = ? AND apagado_em IS NULL');
        $stmt->execute([$documento_id]);
        $documento = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$documento) { die('Documento não encontrado ou já está na lixeira.'); }
} catch (Throwable $e) { die('Erro ao carregar documento: ' . $e->getMessage()); }

include '../../templates/header.php';
?>

<style>
/* Layout estilo eDok: sidebar escura + viewer */
.content-wrapper, .main-footer { margin-left: 0 !important; }
.main-header { display: none !important; }
body { background-color: #1f2327; }
.esign-sidebar { background: #2c3034; color: #e9ecef; min-height: calc(100vh - 80px); padding: 16px; }
.esign-sidebar .form-control, .esign-sidebar .custom-file-label { background: #1f2327; border-color: #3a3f44; color: #e9ecef; }
.esign-sidebar .form-control:focus { background: #1f2327; color: #fff; border-color: #495057; box-shadow: none; }
.esign-toolbar { background: #2c3034; padding: 8px; border-radius: 6px; color: #e9ecef; }
.esign-toolbar .btn { margin-right: 6px; }
.esign-zoom { min-width: 86px; text-align:center; }
.esign-advanced { background:#252a2e; border:1px solid #3a3f44; border-radius:6px; padding:10px; margin-top:10px; }
.badge-soft { background: rgba(255,255,255,.12); color:#e9ecef; font-weight:500; }

/* Overlay de carregamento estilo eDok */
#loadingOverlay { position: fixed; inset: 0; background: rgba(17,17,17,.55); display: none; align-items: center; justify-content: center; z-index: 9999; }
#loadingOverlay .box { background: #fff; color:#111; border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,.22); padding: 22px 26px; min-width: 280px; text-align: center; }
#loadingOverlay .spinner { width: 28px; height: 28px; border: 3px solid #e5e7eb; border-top-color: #6b7280; border-radius: 50%; animation: spin 1s linear infinite; display:inline-block; vertical-align: middle; margin-right: 12px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Modal de desenho de assinatura */
#sigModal { position: fixed; inset:0; background: rgba(0,0,0,.65); display:none; align-items:center; justify-content:center; z-index: 10000; }
#sigModal .card { width: 640px; max-width: 92vw; background:#111827; color:#e5e7eb; border-radius:10px; box-shadow: 0 16px 40px rgba(0,0,0,.45); }
#sigModal .card-header { padding:12px 16px; border-bottom:1px solid #374151; }
#sigModal .card-body { padding:16px; }
#sigModal canvas { background:#fff; border-radius:6px; display:block; width:100%; height:220px; }
#sigModal .card-footer { padding:12px 16px; border-top:1px solid #374151; text-align:right; }
</style>

<div id="loadingOverlay" aria-hidden="true">
    <div class="box">
        <span class="spinner" aria-hidden="true"></span>
        <span>Processando, aguarde...</span>
    </div>
</div>

<div id="sigModal" role="dialog" aria-modal="true" aria-label="Desenhar assinatura">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-signature"></i> Desenhar assinatura</strong>
            <button type="button" class="btn btn-sm btn-outline-light" id="sigClose">Fechar</button>
        </div>
        <div class="card-body">
            <canvas id="sigCanvas" width="600" height="220"></canvas>
            <small class="text-muted">Use o mouse (ou toque) para desenhar. Clique em Limpar para refazer.</small>
        </div>
        <div class="card-footer">
            <button type="button" class="btn btn-sm btn-secondary" id="sigClear">Limpar</button>
            <button type="button" class="btn btn-sm btn-success" id="sigSave">Salvar</button>
        </div>
    </div>
    </div>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-file-signature"></i> Assinar Documento: <?= htmlspecialchars($documento['titulo']) ?></h3>
            <a href="../documentos_propriedades.php?id=<?= (int)$documento_id ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Cancelar e Voltar</a>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3 esign-sidebar">
                    <h5 class="mb-3"><img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : (BASE_URL . '/assets/dist/img/logo_enfasged.svg') ?>" style="height:24px;vertical-align:middle;" alt="<?= htmlspecialchars(defined('BRAND_NAME') ? BRAND_NAME : 'GED') ?>"> <span class="ml-1">Assinar Documento</span></h5>
                    <form id="formSimples" action="assinar_simples_process.php" method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="documento_id" value="<?= (int)$documento_id ?>">
                        <input type="hidden" name="assinatura_data" id="assinatura_data">
                        <input type="hidden" name="stamp_pos" value="br">
                        <input type="hidden" name="stamp_size" value="md">
                        <input type="hidden" name="stamp_page" value="all">
                        <input type="hidden" name="geo_lat" id="geo_lat">
                        <input type="hidden" name="geo_lng" id="geo_lng">

                        <div class="form-group">
                            <label>Nome<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" id="nome" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" placeholder="Digite seu nome completo">
                        </div>
                        <div class="form-group">
                            <label>CPF/CNPJ<span class="text-danger">*</span> <small>(somente números)</small></label>
                            <input type="text" class="form-control" name="cpf_cnpj" id="cpf_cnpj" inputmode="numeric" pattern="\d*" placeholder="Digite seu CPF ou CNPJ">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" placeholder="Digite seu email">
                        </div>
                        <div class="form-group">
                            <label>Qualificador<span class="text-danger">*</span></label>
                            <select name="qualificador" id="qualificador" class="form-control" required>
                                <option value="">Selecione ou digite...</option>
                                <option>Paciente</option>
                                <option>Médico(a)</option>
                                <option>Enfermeiro(a)</option>
                                <option>Responsável</option>
                                <option>Parte</option>
                                <option>Testemunha</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="assinatura_arquivo" name="assinatura_arquivo" accept="image/*">
                                <label class="custom-file-label" id="assinatura_arquivo_label" for="assinatura_arquivo">Enviar imagem da assinatura...</label>
                            </div>
                            <button type="button" class="btn btn-warning btn-block mt-2" id="btnAbrirDesenho"><i class="fas fa-signature"></i> Adicionar Assinatura</button>
                        </div>

                        <div class="form-group">
                            <button type="button" class="btn btn-secondary btn-block" id="btnGeo"><i class="fas fa-map-marker-alt"></i> <span id="geoLabel">Obter Localização</span></button>
                        </div>

                        <div class="esign-advanced">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-soft">Opções de Carimbo</span>
                                <a href="#" class="small" id="toggleAdvanced">exibir/ocultar</a>
                            </div>
                            <div id="advOpts" style="display:none;">
                                <div class="form-row">
                                    <div class="form-group col-4"><label>Posição</label><select class="form-control" onchange="document.querySelector('[name=stamp_pos]').value=this.value"><option value="br" selected>Inferior Dir.</option><option value="bl">Inferior Esq.</option><option value="tr">Superior Dir.</option><option value="tl">Superior Esq.</option></select></div>
                                    <div class="form-group col-4"><label>Tamanho</label><select class="form-control" onchange="document.querySelector('[name=stamp_size]').value=this.value"><option value="sm">Pequeno</option><option value="md" selected>Médio</option><option value="lg">Grande</option></select></div>
                                    <div class="form-group col-4"><label>Página</label><select class="form-control" onchange="document.querySelector('[name=stamp_page]').value=this.value"><option value="last">Última</option><option value="first">Primeira</option><option value="all" selected>Todas</option></select></div>
                                </div>
                            </div>
                        </div>

                        <?= lgpd_render_consent_checkbox($pdo); ?>
                        <button type="submit" class="btn btn-success btn-block mt-2"><i class="fas fa-check"></i> Assinar Documento</button>
                    </form>

                    <!-- ICP-Brasil -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center"><span class="badge badge-soft">ICP-Brasil (Avançado)</span><a href="#" id="toggleICP" class="small">exibir/ocultar</a></div>
                        <div id="icpArea" style="display:none;" class="mt-2">
                            <form id="formICP" action="assinar_process.php" method="post" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="documento_id" value="<?= (int)$documento_id ?>">
                                <div class="form-group">
                                    <label>Certificado Digital (A1)</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="certificado" name="certificado" accept=".pfx,.p12">
                                        <label class="custom-file-label" id="certificado_label" for="certificado">Escolher arquivo .pfx ou .p12...</label>
                                    </div>
                                    <small class="form-text text-muted">Se já vinculou um PFX no perfil, o envio é opcional. Informe apenas a senha.</small>
                                </div>
                                <div class="form-group"><label for="senha_certificado">Senha do Certificado</label><input type="password" class="form-control" id="senha_certificado" name="senha_certificado"></div>
                                <div class="form-group form-check"><input type="checkbox" class="form-check-input js-carimbar-toggle" id="icp_carimbar" name="carimbar" value="1" checked><label class="form-check-label" for="icp_carimbar">Carimbar visualmente (QR + texto)</label></div>
                                <div class="form-row js-stamp-opts-icp">
                                    <div class="form-group col-4"><label>Posição</label><select name="stamp_pos" class="form-control"><option value="br" selected>Inferior Dir.</option><option value="bl">Inferior Esq.</option><option value="tr">Superior Dir.</option><option value="tl">Superior Esq.</option></select></div>
                                    <div class="form-group col-4"><label>Tamanho</label><select name="stamp_size" class="form-control"><option value="sm">Pequeno</option><option value="md" selected>Médio</option><option value="lg">Grande</option></select></div>
                                    <div class="form-group col-4"><label>Página</label><select name="stamp_page" class="form-control"><option value="last">Última</option><option value="first">Primeira</option><option value="all" selected>Todas</option></select></div>
                                </div>
                                <div id="certValidationResult" class="alert d-none" role="alert"></div>
                                <?= lgpd_render_consent_checkbox($pdo); ?>
                                <button type="submit" class="btn btn-info btn-block mt-2"><i class="fas fa-certificate"></i> Assinar com Certificado</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Viewer -->
                <div class="col-lg-9">
                    <div class="esign-toolbar mb-2 d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-light" id="prevPage"><i class="fas fa-arrow-left"></i> Página Anterior</button>
                        <button class="btn btn-sm btn-outline-light" id="nextPage"><i class="fas fa-arrow-right"></i> Próxima Página</button>
                        <div class="ml-3 mr-2">Página</div>
                        <input type="number" id="pageInput" class="form-control form-control-sm" value="1" min="1" style="width:80px;">
                        <div class="ml-1 mr-3">de <span id="pageTotal">1</span></div>
                        <button class="btn btn-sm btn-outline-light" id="zoomIn"><i class="fas fa-search-plus"></i> Aumentar</button>
                        <button class="btn btn-sm btn-outline-light" id="zoomOut"><i class="fas fa-search-minus"></i> Diminuir</button>
                        <input type="text" id="zoomPercent" class="form-control form-control-sm esign-zoom ml-2" value="100 %" readonly>
                        <div class="ml-auto d-flex align-items-center">
                            <span class="badge badge-soft mr-2">Visualizador</span>
                            <a href="verificar.php?id=<?= (int)$documento_id ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Verificação pública"><i class="fas fa-qrcode"></i></a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <iframe id="docFrame" src="../documentos_ver.php?id=<?= (int)$documento_id ?>&v=<?= time() ?>" style="width: 100%; height: 82vh; border: none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
(function(){
    const overlay = document.getElementById('loadingOverlay');
    const showOverlay = () => { overlay.style.display = 'flex'; };
    const hideOverlay = () => { overlay.style.display = 'none'; };

    // Exibir overlay em submits
    ['formSimples','formICP'].forEach(id => {
        const f = document.getElementById(id);
        if (f) f.addEventListener('submit', function(){ showOverlay(); });
    });

    // File labels
    const sigInput = document.getElementById('assinatura_arquivo');
    if (sigInput) sigInput.addEventListener('change', function(){
        const lbl = document.getElementById('assinatura_arquivo_label');
        if (lbl && this.files && this.files.length) lbl.textContent = this.files[0].name;
    });
    const certInput = document.getElementById('certificado');
    if (certInput) certInput.addEventListener('change', function(){
        const lbl = document.getElementById('certificado_label');
        if (lbl && this.files && this.files.length) lbl.textContent = this.files[0].name;
    });

    // Toggle avançado de carimbo
    const advLink = document.getElementById('toggleAdvanced');
    if (advLink) advLink.addEventListener('click', function(e){ e.preventDefault(); const el = document.getElementById('advOpts'); if (el) el.style.display = (el.style.display==='none'||!el.style.display)?'block':'none'; });

    // Toggle ICP form
    const icpLink = document.getElementById('toggleICP');
    if (icpLink) icpLink.addEventListener('click', function(e){ e.preventDefault(); const el = document.getElementById('icpArea'); if (el) el.style.display = (el.style.display==='none'||!el.style.display)?'block':'none'; });

    // Mostrar/ocultar opções de carimbo no ICP
    function toggleStampOpts(){
        const chk = document.getElementById('icp_carimbar');
        const area = document.querySelector('.js-stamp-opts-icp');
        if (chk && area) area.style.display = chk.checked ? 'flex' : 'none';
    }
    const carimbarChk = document.getElementById('icp_carimbar');
    if (carimbarChk) carimbarChk.addEventListener('change', toggleStampOpts);
    toggleStampOpts();

    // Geolocalização
    const btnGeo = document.getElementById('btnGeo');
    if (btnGeo) btnGeo.addEventListener('click', function(){
        const lab = document.getElementById('geoLabel');
        if (!navigator.geolocation) { if(lab) lab.textContent = 'Geolocalização indisponível'; return; }
        lab && (lab.textContent = 'Obtendo localização...');
        navigator.geolocation.getCurrentPosition(function(pos){
            document.getElementById('geo_lat').value = pos.coords.latitude;
            document.getElementById('geo_lng').value = pos.coords.longitude;
            lab && (lab.textContent = 'Localização capturada');
        }, function(){ lab && (lab.textContent = 'Falha ao obter localização'); }, { enableHighAccuracy:true, timeout:10000 });
    });

    // Modal de assinatura: desenho simples
    const sigModal = document.getElementById('sigModal');
    const btnOpen = document.getElementById('btnAbrirDesenho');
    const btnClose = document.getElementById('sigClose');
    const btnClear = document.getElementById('sigClear');
    const btnSave = document.getElementById('sigSave');
    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    let drawing = false, last={x:0,y:0};

    function openSig(){ sigModal.style.display='flex'; }
    function closeSig(){ sigModal.style.display='none'; }
    function clearSig(){ ctx.clearRect(0,0,canvas.width,canvas.height); }

    function pos(evt){ const r = canvas.getBoundingClientRect(); const x = (evt.touches?evt.touches[0].clientX:evt.clientX) - r.left; const y = (evt.touches?evt.touches[0].clientY:evt.clientY) - r.top; return {x:x*(canvas.width/r.width), y:y*(canvas.height/r.height)}; }
    function start(evt){ drawing=true; last=pos(evt); }
    function move(evt){ if(!drawing) return; const p=pos(evt); ctx.strokeStyle='#111'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.beginPath(); ctx.moveTo(last.x,last.y); ctx.lineTo(p.x,p.y); ctx.stroke(); last=p; }
    function end(){ drawing=false; }

    if (btnOpen) btnOpen.addEventListener('click', openSig);
    if (btnClose) btnClose.addEventListener('click', closeSig);
    if (btnClear) btnClear.addEventListener('click', clearSig);
    if (btnSave) btnSave.addEventListener('click', function(){
        const dataUrl = canvas.toDataURL('image/png');
        document.getElementById('assinatura_data').value = dataUrl;
        closeSig();
    });

    ['mousedown','touchstart'].forEach(ev => canvas.addEventListener(ev, start));
    ['mousemove','touchmove'].forEach(ev => canvas.addEventListener(ev, move));
    ['mouseup','mouseleave','touchend','touchcancel'].forEach(ev => canvas.addEventListener(ev, end));

    // Viewer UX: mostrar overlay enquanto o iframe carrega
    const frame = document.getElementById('docFrame');
    if (frame) {
        showOverlay();
        frame.addEventListener('load', hideOverlay);
    }
})();
</script>

<?php include '../../templates/footer.php'; ?>