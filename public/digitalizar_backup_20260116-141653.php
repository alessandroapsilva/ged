<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// ##### LINHA 6 CORRIGIDA #####
// Removemos a condição 'WHERE apagado_em IS NULL' que estava causando o erro.
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="flex-between mb-4">
            <h1>📱 Central de Digitalização</h1>
            <a href="documentos.php" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
        </div>
    </section>

    <section class="content">
        <div class="grid grid-2" style="gap: 2rem;">
            <div>
                <form id="form-digitalizacao">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">1. Controles do Scanner</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="select-scanner"><strong>Selecione um Scanner</strong></label>
                                <select id="select-scanner" class="form-control" style="padding: 0.75rem;"></select>
                            </div>
                            <div class="grid grid-2" style="gap: 1rem;">
                                <div class="form-group">
                                    <label for="dpi"><strong>Resolução (DPI)</strong></label>
                                    <input type="number" id="dpi" class="form-control" value="200" min="72" max="600" step="1">
                                </div>
                                <div class="form-group">
                                    <label for="pixel-type"><strong>Modo de Cor</strong></label>
                                    <select id="pixel-type" class="form-control">
                                        <option value="rgb" selected>🎨 Colorido (RGB)</option>
                                        <option value="gray">⚫ Tons de Cinza</option>
                                        <option value="bw">⚪ Preto e Branco</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-2" style="gap: 1rem;">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="chk-adf" style="margin-right: 0.5rem;">
                                        <strong>Usar ADF</strong>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="chk-duplex" style="margin-right: 0.5rem;">
                                        <strong>Duplex</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="max-paginas"><strong>Limite de Páginas</strong> (opcional)</label>
                                <input type="number" id="max-paginas" class="form-control" placeholder="Deixe vazio para sem limite" min="1">
                            </div>
                            <div class="alert" id="div-status" style="background: var(--gray-50); border-color: var(--gray-300); color: var(--gray-700); margin: 1rem 0;">
                                <strong>Status:</strong> <span style="display: inline-block; margin-left: 0.5rem;">Inicializando...</span>
                            </div>
                            <div class="alert alert-warning" id="install-help" style="display:none; margin: 1rem 0;">
                                <div style="display: flex; gap: 1rem;">
                                    <span style="font-size: 1.5rem;">⚠️</span>
                                    <div style="flex:1;">
                                        <strong>Serviço de Digitalização não encontrado.</strong>
                                        <div class="mt-3" id="install-help-content">
                                            <div style="margin-bottom: 1.5rem;">
                                                <strong style="display: block; margin-bottom: 0.5rem;">Se já instalou o serviço, siga os passos:</strong>
                                                <ol style="margin-top: 0.75rem; margin-left: 1.25rem;">
                                                    <li style="margin-bottom: 0.75rem;"><strong>Reinicie o Serviço:</strong>
                                                        <ul style="margin-top: 0.5rem; margin-left: 1.25rem;">
                                                            <li>Abra <code>Task Manager</code> (Ctrl+Shift+Esc)</li>
                                                            <li>Procure por "<strong>Dynamsoft</strong>" ou "<strong>DynamsoftServiceManager</strong>"</li>
                                                            <li>Se encontrar, clique com direito → <strong>Reiniciar</strong></li>
                                                            <li>Caso contrário, inicie de: <code>C:\Program Files\Dynamsoft\DynamsoftServiceManager\DynamsoftServiceManager.exe</code></li>
                                                        </ul>
                                                    </li>
                                                    <li style="margin-bottom: 0.75rem;"><strong>Verificar Firewall:</strong>
                                                        <ul style="margin-top: 0.5rem; margin-left: 1.25rem;">
                                                            <li>Windows → <code>Firewall &amp; network protection</code></li>
                                                            <li>Procure por <strong>Dynamsoft Service</strong></li>
                                                            <li>Marque: <strong>Private</strong> e <strong>Public</strong></li>
                                                        </ul>
                                                    </li>
                                                    <li><strong>Teste de Conectividade:</strong>
                                                        <button type="button" id="btn-diagnose-service" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">🔍 Testar Conectividade</button>
                                                        <div id="diagnose-result" style="display:none; margin-top: 0.75rem;"></div>
                                                    </li>
                                                </ol>
                                            </div>
                                            <div style="border-top: 1px solid currentColor; padding-top: 1rem;">
                                                <strong style="display: block; margin-bottom: 0.5rem;">Não instalou ainda?</strong>
                                                <ul style="margin-top: 0.75rem; margin-left: 1.25rem;">
                                                    <li>Windows 64-bit: <a href="https://download.dynamsoft.com/web-twain/setup/DynamsoftServiceSetup.msi" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;"><i class="fas fa-download mr-2"></i>Baixar instalador (MSI)</a></li>
                                                    <li>Porta HTTP: <strong>18622</strong> | HTTPS: <strong>18623</strong></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="mt-3" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                            <button type="button" id="btn-retry-service" class="btn btn-sm btn-primary">↻ Tentar Novamente</button>
                                            <button type="button" id="btn-force-http" class="btn btn-sm btn-secondary">HTTP (sem HTTPS)</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="btn-scan" class="btn btn-primary btn-block" disabled style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-camera-retro mr-2"></i>Digitalizar Página
                            </button>
                        </div>
                    </div>

                    <div class="card card-primary card-outline">
                        <div class="card-header"><h3 class="card-title">2. Detalhes do Documento</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="titulo"><strong>Título do Documento</strong></label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Contrato de Serviço 123">
                            </div>
                            <div class="form-group">
                                <label for="select-tipo-documento"><strong>Tipo de Documento</strong></label>
                                <select name="tipo_documento_id" id="select-tipo-documento" class="form-control">
                                    <option value="">-- Selecione para carregar metadados --</option>
                                    <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="chk-ocr" style="margin-right: 0.5rem;">
                                    <strong>Executar OCR (Extrair Texto)</strong>
                                </label>
                            </div>
                            <div id="metadados-dinamicos" class="mt-3"></div>
                        </div>
                    </div>
                    <div class="card card-warning card-outline" id="card-upload-manual" style="display:none">
                        <div class="card-header"><h3 class="card-title">📁 Upload Manual</h3></div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Se o serviço não estiver disponível, exporte seu documento como PDF no seu software de digitalização (ex.: NAPS2, HP, Epson) e anexe abaixo.</p>
                            <div class="form-group">
                                <label for="arquivo-manual"><strong>Arquivo</strong></label>
                                <input type="file" id="arquivo-manual" class="form-control" accept="application/pdf">
                                <small class="form-text text-muted">Aceita apenas PDF (tamanho máximo 50 MB).</small>
                            </div>
                            <button type="button" id="btn-save-upload" class="btn btn-success btn-block" disabled>
                                <i class="fas fa-cloud-upload-alt mr-2"></i>Salvar no GED (Upload)
                            </button>
                        </div>
                    </div>
                            <div class="card card-primary card-outline">
                        <div class="card-header"><h3 class="card-title">3. Ações</h3></div>
                        <div class="card-body">
                            <button type="button" id="btn-save" class="btn btn-success btn-block mb-2" disabled><i class="fas fa-save mr-2"></i>Salvar no GED</button>
                            <button type="button" id="btn-download" class="btn btn-secondary btn-block" disabled><i class="fas fa-download mr-2"></i>Baixar no PC</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title">Pré-visualização</h3>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-page" title="Página Anterior" disabled><i class="fas fa-arrow-left"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-next-page" title="Próxima Página" disabled><i class="fas fa-arrow-right"></i></button>
                            </div>
                            <span id="page-info" class="ml-2 text-muted" style="font-size: 0.9rem; white-space: nowrap;">Página 0 de 0</span>
                            <button type="button" class="btn btn-sm btn-outline-danger ml-2" id="btn-remove-page" title="Apagar Página Atual" disabled><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="min-height: 600px; background-color: #f9fafb; display: flex; align-items: center; justify-content: center;">
                        <div id="dwtcontrolContainer" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
            </div>
        </div></div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<!-- Dynamsoft WebTWAIN local (evita dependência de CDN/Internet) -->
<script src="assets/plugins/dynamsoft/dynamsoft.webtwain.min.js"></script>
<script src="assets/plugins/dynamsoft/dynamsoft.webtwain.install.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let DWObject = null;
    const statusEl = document.getElementById('div-status');
    const installHelp = document.getElementById('install-help');
    const cardUploadManual = document.getElementById('card-upload-manual');
    const btnRetryService = document.getElementById('btn-retry-service');
    const manualFileInput = document.getElementById('arquivo-manual');
    const btnSaveUpload = document.getElementById('btn-save-upload');
    const dpiInput = document.getElementById('dpi');
    const pixelTypeSelect = document.getElementById('pixel-type');
    const chkAdf = document.getElementById('chk-adf');
    const chkDuplex = document.getElementById('chk-duplex');
    const maxPaginasInput = document.getElementById('max-paginas');
    let webtwainReady = false;
    // loadAttempt controlará as combinações de conexão tentadas
    // 0: protocolo da página + 127.0.0.1
    // 1: protocolo da página + localhost
    // 2: protocolo alternativo + 127.0.0.1
    // 3: protocolo alternativo + localhost
    let loadAttempt = 0;

    // Se a biblioteca do Dynamsoft não carregar (CDN bloqueado/sem internet), evita tela "morta"
    if (typeof Dynamsoft === 'undefined' || !Dynamsoft.DWT) {
        statusEl.innerText = 'Status: Falha ao carregar a biblioteca do scanner (Dynamsoft WebTWAIN).';
        showInstallHelp();
        return;
    }

    // Configuração da licença e parâmetros base
    Dynamsoft.DWT.ProductKey = 't0134uAEAAKKoU9J9Wr1J17f8MnTI9wQd02g006ziwJ+0BaiEtZr2c0r6xGBZU6p0yhzVK0esuYOmlwtEZYG04BPIT14eNfI0uE+Xpr3eb3mcp7K0/TRePFOvkPVgfvLfHrnygz6xeCk44JCvxp92pp/nH/DlO/zv5+27X17O+yaaUiPCA+b6a5g=';
    // Recursos locais do viewer/addons
    Dynamsoft.DWT.ResourcesPath = 'assets/plugins/dynamsoft/Resources';
    Dynamsoft.DWT.UseLocalService = true;
    // Host será definido dinamicamente por tentativa
    Dynamsoft.DWT.Host = '127.0.0.1';

    function configureConnection(attempt = 0) {
        // Em navegadores recentes (ex.: Chrome), HTTPS no serviço local tende a ser mais confiável.
        // Então tentamos HTTPS primeiro e caímos para HTTP como fallback.
        const combos = [
            { proto: 'https', host: '127.0.0.1' },
            { proto: 'https', host: 'localhost' },
            { proto: 'http', host: '127.0.0.1' },
            { proto: 'http', host: 'localhost' }
        ];
        const idx = Math.max(0, Math.min(attempt, combos.length - 1));
        const c = combos[idx];
        Dynamsoft.DWT.Protocol = c.proto;
        Dynamsoft.DWT.Port = c.proto === 'https' ? 18623 : 18622;
        Dynamsoft.DWT.Host = c.host;
    }

    function currentServiceUrl() {
        const proto = Dynamsoft.DWT.Protocol || (window.location.protocol === 'https:' ? 'https' : 'http');
        const host = Dynamsoft.DWT.Host || '127.0.0.1';
        const port = Dynamsoft.DWT.Port || (proto === 'https' ? 18623 : 18622);
        return `${proto}://${host}:${port}`;
    }

    function showInstallHelp() {
        installHelp.style.display = '';
        cardUploadManual.style.display = '';
        document.getElementById('btn-scan').disabled = true;
        statusEl.innerText = 'Status: Serviço não encontrado. Veja instruções abaixo ou use o Upload Manual.';
    }

    function formatDwtError(e) {
        if (!e) return 'Erro desconhecido.';
        const code = (typeof e.code !== 'undefined') ? e.code : (typeof e.ErrorCode !== 'undefined' ? e.ErrorCode : undefined);
        const msg = e.message || e.Message || e.errorString || e.ErrorString || String(e);
        return (typeof code !== 'undefined') ? `(${code}) ${msg}` : msg;
    }

    // Captura erros gerais do WebTWAIN
    try {
        Dynamsoft.DWT.OnWebTwainError = function (e) {
            const msg = formatDwtError(e);
            statusEl.innerText = `Status: WebTWAIN erro em ${currentServiceUrl()}. ${msg}`;
            console.error('DWT OnWebTwainError:', e);
            showInstallHelp();
        };
        // Fallback usado internamente pelo SDK em alguns cenários
        window.Dynamsoft_OnError = function (e) {
            const msg = formatDwtError(e);
            statusEl.innerText = `Status: WebTWAIN erro em ${currentServiceUrl()}. ${msg}`;
            console.error('window.Dynamsoft_OnError:', e);
            showInstallHelp();
        };
    } catch (e) {
        console.warn('Falha ao registrar handlers de erro do WebTWAIN', e);
    }

    function hideInstallHelp() {
        installHelp.style.display = 'none';
        // O card de upload manual fica como alternativa; não escondemos automaticamente.
    }

    // Função para atualizar os botões de pré-visualização
    function updateControls() {
        if (!DWObject) return;
        const total = DWObject.HowManyImagesInBuffer;
        const current = total > 0 ? DWObject.CurrentImageIndexInBuffer + 1 : 0;
        document.getElementById('page-info').innerText = `Página ${current} de ${total}`;
        const hasImages = total > 0;
        document.getElementById('btn-save').disabled = !hasImages;
        document.getElementById('btn-download').disabled = !hasImages;
        document.getElementById('btn-prev-page').disabled = current <= 1;
        document.getElementById('btn-next-page').disabled = current === total;
        document.getElementById('btn-remove-page').disabled = !hasImages;
    }

    // Função executada quando a biblioteca TWAIN está pronta
    function Dynamsoft_OnReady() {
        webtwainReady = true;
        hideInstallHelp();
        statusEl.innerText = 'Status: Biblioteca pronta.';
        DWObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
        if (!DWObject) { statusEl.innerText = 'Status: Erro ao iniciar componente.'; return; }
        
        // Configura o visualizador
        DWObject.Viewer.ifShowPageNumber = false;
        DWObject.Viewer.ifShowNavigators = false;
        updateControls(); 

        statusEl.innerText = 'Status: Procurando scanners...';
        DWObject.GetSourceNamesAsync().then(sources => {
            statusEl.innerText = `Status: ${sources.length} scanner(s) encontrado(s).`;
            let selectScanner = document.getElementById('select-scanner');
            sources.forEach((source, index) => selectScanner.add(new Option(source, index)));
            document.getElementById('btn-scan').disabled = sources.length === 0;
            if (sources.length === 0) alert("Nenhum scanner TWAIN foi encontrado.");
        }).catch(err => {
            const msg = (err && (err.message || err.Message)) ? (err.message || err.Message) : String(err);
            statusEl.innerText = `Status: Erro ao conectar com o serviço (${currentServiceUrl()}). ${msg}`;
            console.error(err);
            showInstallHelp();
        });
    }
    
    // Configura os containers e eventos
    Dynamsoft.DWT.Containers = [{ ContainerId: 'dwtcontrolContainer', Width: '100%', Height: '100%' }];
    Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', Dynamsoft_OnReady);
    Dynamsoft.DWT.RegisterEvent('OnBufferChanged', updateControls);
    statusEl.innerText = 'Status: Carregando biblioteca...';

    function loadWebTwain() {
        configureConnection(loadAttempt);
        statusEl.innerText = `Status: Conectando ao serviço local (${currentServiceUrl()})...`;
        try { Dynamsoft.DWT.Load(); } catch (e) { console.error(e); }
        // Se após 6s não estiver pronto, tenta alternativa ou mostra instruções
        setTimeout(() => {
            if (!webtwainReady) {
                if (loadAttempt < 3) {
                    loadAttempt += 1;
                    statusEl.innerText = 'Status: Tentando conexão alternativa com o serviço...';
                    loadWebTwain();
                } else {
                    showInstallHelp();
                }
            }
        }, 6000);
    }

    // Define o container e carrega

    // --- AÇÕES DOS BOTÕES ---

    // Configura os containers e eventos e inicia o carregamento
    Dynamsoft.DWT.Containers = [{ ContainerId: 'dwtcontrolContainer', Width: '100%', Height: '100%' }];
    loadWebTwain();

    // Botão de tentar novamente após instalar serviço
    if (btnRetryService) {
        btnRetryService.addEventListener('click', () => {
            webtwainReady = false;
            loadAttempt = 0;
            hideInstallHelp();
            statusEl.innerText = 'Status: Recarregando biblioteca...';
            loadWebTwain();
        });
    }

    // Botão para forçar HTTP (sem HTTPS)
    const btnForceHttp = document.getElementById('btn-force-http');
    if (btnForceHttp) {
        btnForceHttp.addEventListener('click', () => {
            webtwainReady = false;
            loadAttempt = 2; // Força a ir direto para HTTP (pula HTTPS)
            hideInstallHelp();
            statusEl.innerText = 'Status: Tentando apenas HTTP (porta 18622)...';
            loadWebTwain();
        });
    }

    // Botão de diagnóstico de conectividade
    const btnDiagnose = document.getElementById('btn-diagnose-service');
    const diagnoseResult = document.getElementById('diagnose-result');
    if (btnDiagnose) {
        btnDiagnose.addEventListener('click', async () => {
            btnDiagnose.disabled = true;
            btnDiagnose.innerHTML = '⏳ Testando...';
            diagnoseResult.innerHTML = '';
            diagnoseResult.style.display = 'block';

            const tests = [
                { name: 'HTTPS localhost:18623', proto: 'https', host: 'localhost', port: 18623 },
                { name: 'HTTPS 127.0.0.1:18623', proto: 'https', host: '127.0.0.1', port: 18623 },
                { name: 'HTTP localhost:18622', proto: 'http', host: 'localhost', port: 18622 },
                { name: 'HTTP 127.0.0.1:18622', proto: 'http', host: '127.0.0.1', port: 18622 }
            ];

            let anySuccess = false;
            let results = '<strong>Resultado do Diagnóstico:</strong><ul class="mt-2 mb-0">';

            for (const test of tests) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 2000);
                    const response = await fetch(`${test.proto}://${test.host}:${test.port}/api/version`, {
                        signal: controller.signal,
                        mode: 'no-cors',
                        credentials: 'omit'
                    });
                    clearTimeout(timeoutId);
                    results += `<li class="text-success"><strong>✓ ${test.name}</strong> - Respondendo!</li>`;
                    anySuccess = true;
                } catch (e) {
                    results += `<li class="text-danger"><strong>✗ ${test.name}</strong> - Sem resposta</li>`;
                }
            }

            results += '</ul>';
            if (anySuccess) {
                results += '<div class="alert alert-info mt-2 mb-0 small"><strong>Serviço detectado!</strong> Clique em "Tentar Novamente" ou "Forçar HTTP".</div>';
            } else {
                results += '<div class="alert alert-danger mt-2 mb-0 small"><strong>Serviço não respondeu em nenhuma porta.</strong><br>Certifique-se de que:<br>1) Abriu o DynamsoftServiceManager (Task Manager → Dynamsoft)<br>2) Permitiu no Firewall do Windows<br>3) Serviço está rodando (status "running")</div>';
            }

            diagnoseResult.innerHTML = results;
            btnDiagnose.disabled = false;
            btnDiagnose.innerHTML = '🔍 Testar Conectividade';
        });
    }

    // Botão de tentar novamente após instalar serviço

    // Digitalizar
    document.getElementById('btn-scan').onclick = () => {
        if (!DWObject) return;
        const selectedIndex = document.getElementById('select-scanner').selectedIndex;
        const scannerNome = document.getElementById('select-scanner').options[selectedIndex]?.text || '';
        DWObject.SelectSourceByIndexAsync(selectedIndex)
            .then(() => {
                const dpi = parseInt(dpiInput.value, 10) || 200;
                const pixelMap = {
                    'rgb': Dynamsoft.DWT.EnumDWT_PixelType.TWPT_RGB,
                    'gray': Dynamsoft.DWT.EnumDWT_PixelType.TWPT_GRAY,
                    'bw': Dynamsoft.DWT.EnumDWT_PixelType.TWPT_BW
                };
                const pixelType = pixelMap[pixelTypeSelect.value] || Dynamsoft.DWT.EnumDWT_PixelType.TWPT_RGB;
                const useAdf = !!chkAdf.checked;
                const useDuplex = !!chkDuplex.checked;
                const maxPages = parseInt(maxPaginasInput.value, 10) || 0;

                const acquireOptions = {
                    IfDisableSourceAfterAcquire: true,
                    IfShowUI: false,
                    PixelType: pixelType,
                    Resolution: dpi,
                    IfFeederEnabled: useAdf,
                    IfDuplexEnabled: useDuplex
                };

                statusEl.innerText = 'Status: Iniciando digitalização...';
                const openPromise = (typeof DWObject.OpenSourceAsync === 'function')
                    ? DWObject.OpenSourceAsync()
                    : Promise.resolve();

                return openPromise.then(() => DWObject.AcquireImageAsync(acquireOptions)).then(() => {
                    // Enforce max pages by trimming extras
                    const total = DWObject.HowManyImagesInBuffer;
                    if (maxPages > 0 && total > maxPages) {
                        for (let i = total - 1; i >= maxPages; i--) {
                            try { DWObject.RemoveImage(i); } catch(e) { console.warn('Falha ao remover página extra', e); }
                        }
                        updateControls();
                        alert('Foram mantidas apenas ' + maxPages + ' páginas conforme limite definido.');
                    }
                    // Guarda nome do scanner no dataset do form para envio
                    document.getElementById('form-digitalizacao').dataset.scannerNome = scannerNome;
                    statusEl.innerText = 'Status: Digitalização concluída.';
                }).finally(() => {
                    if (typeof DWObject.CloseSourceAsync === 'function') {
                        try { return DWObject.CloseSourceAsync(); } catch (e) { return; }
                    }
                });
            })
            .catch(exp => {
                const msg = formatDwtError(exp);
                statusEl.innerText = `Status: Falha ao digitalizar. ${msg}`;
                console.error('AcquireImageAsync falhou:', exp);
                alert('Falha ao digitalizar: ' + msg);
            });
    };
    
    // Controles do Visualizador
    document.getElementById('btn-prev-page').onclick = () => DWObject && DWObject.GoToImage(DWObject.CurrentImageIndexInBuffer - 1);
    document.getElementById('btn-next-page').onclick = () => DWObject && DWObject.GoToImage(DWObject.CurrentImageIndexInBuffer + 1);
    document.getElementById('btn-remove-page').onclick = () => { if(DWObject && DWObject.HowManyImagesInBuffer > 0) DWObject.RemoveImage(DWObject.CurrentImageIndexInBuffer); };
    
    // Carregar Metadados
    document.getElementById('select-tipo-documento').addEventListener('change', function() {
        const tipoId = this.value;
        const container = document.getElementById('metadados-dinamicos');
        container.innerHTML = '';
        if (tipoId) {
            fetch(`ajax_get_metadados_fields.php?tipo_id=${tipoId}`).then(response => response.json()).then(campos => {
                if (campos.length > 0) {
                    let html = '<hr><h5>Metadados</h5>';
                    campos.forEach(campo => { html += `<div class="form-group"><label for="meta-${campo.id}">${campo.rotulo}</label><input type="text" class="form-control" id="meta-${campo.id}" name="meta[${campo.id}]"></div>`; });
                    container.innerHTML = html;
                }
            }).catch(err => console.error(err));
        }
    });

    // Baixar no PC
    document.getElementById('btn-download').onclick = function() {
        if (!DWObject || DWObject.HowManyImagesInBuffer === 0) return alert('Nenhuma imagem para baixar.');
        DWObject.SelectAllImages();
        const nomeArquivo = document.getElementById('titulo').value.trim() || 'documento_digitalizado';
        DWObject.SaveAllAsPDF(nomeArquivo + '.pdf');
    };

    // Upload manual: habilita botão quando há arquivo
    if (manualFileInput) {
        manualFileInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) { btnSaveUpload.disabled = true; return; }
            const maxSize = 50 * 1024 * 1024; // 50MB
            if (file.type !== 'application/pdf') {
                alert('Apenas PDF é aceito para upload manual.');
                this.value = '';
                btnSaveUpload.disabled = true;
                return;
            }
            if (file.size > maxSize) {
                alert('Arquivo maior que 50 MB. Por favor, reduza o tamanho.');
                this.value = '';
                btnSaveUpload.disabled = true;
                return;
            }
            btnSaveUpload.disabled = false;
        });
    }

    if (btnSaveUpload) {
        btnSaveUpload.addEventListener('click', function() {
            if (!(manualFileInput && manualFileInput.files && manualFileInput.files.length > 0)) {
                return alert('Selecione um arquivo para enviar.');
            }
            if (!document.getElementById('titulo').value.trim()) {
                return alert('Por favor, preencha o Título do Documento.');
            }
            this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            const form = document.getElementById('form-digitalizacao');
            const formData = new FormData(form);
            formData.append('documento_digitalizado', manualFileInput.files[0], manualFileInput.files[0].name);
            formData.append('conteudo_ocr', '');
            formData.append('captura_modo', 'upload_manual');
            formData.append('scanner_nome', '');
            fetch('digitalizar_processar.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) {
                        sessionStorage.setItem('flash_message', JSON.stringify({type: 'sucesso', text: data.mensagem}));
                        window.location.href = 'documentos.php';
                    } else {
                        throw new Error(data.mensagem || 'Erro desconhecido.');
                    }
                }).catch(err => {
                    alert('Erro no upload: ' + err.message);
                    this.disabled = false; this.innerHTML = '<i class="fas fa-cloud-upload-alt mr-2"></i>Salvar no GED (Upload)';
                });
        });
    }

    // Salvar no GED via WebTWAIN
    document.getElementById('btn-save').onclick = function () {
        if (!DWObject || DWObject.HowManyImagesInBuffer === 0) return alert('Nenhuma imagem para salvar.');
        if (!document.getElementById('titulo').value.trim()) {
            alert('Por favor, preencha o Título do Documento.');
            return;
        }
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processando...';
        const doOCR = document.getElementById('chk-ocr').checked;
        DWObject.SelectAllImages();
        const indices = Array.from({length: DWObject.HowManyImagesInBuffer}, (_, i) => i);

        const uploadFile = (blob, ocrText = "") => {
            let form = document.getElementById('form-digitalizacao');
            let formData = new FormData(form);
            formData.append('documento_digitalizado', blob, "digitalizado.pdf");
            formData.append('conteudo_ocr', ocrText);
            formData.append('captura_modo', 'webtwain');
            const scannerNome = form.dataset.scannerNome || '';
            formData.append('scanner_nome', scannerNome);

            fetch('digitalizar_processar.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) { 
                    sessionStorage.setItem('flash_message', JSON.stringify({type: 'sucesso', text: data.mensagem}));
                    window.location.href = 'documentos.php';
                } else { throw new Error(data.mensagem || 'Erro desconhecido.'); }
            }).catch(error => {
                alert('Erro no upload: ' + error.message);
                this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Salvar no GED';
            });
        };
        
        // Lógica de OCR (Simplificada: faz OCR apenas na primeira página por eficiência)
        if (doOCR && DWObject.Addon && DWObject.Addon.OCR && typeof DWObject.Addon.OCR.recognize === 'function') {
            try {
                DWObject.Addon.OCR.recognize(0, (result) => { 
                    let ocrText = '';
                    try { ocrText = result.getText ? result.getText() : ''; } catch(e) {}
                    DWObject.ConvertToBlob(indices, Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF, (blob) => uploadFile(blob, ocrText));
                }, (errCode, errStr) => {
                    alert('OCR indisponível ou erro: ' + errStr + '. Salvando sem o texto.');
                    DWObject.ConvertToBlob(indices, Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF, (blob) => uploadFile(blob, ""));
                });
            } catch(e) {
                alert('OCR não suportado neste navegador/instalação. Salvando sem o texto.');
                DWObject.ConvertToBlob(indices, Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF, (blob) => uploadFile(blob, ""));
            }
        } else {
            // Salva como PDF sem OCR
            DWObject.ConvertToBlob(indices, Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF, (blob) => uploadFile(blob, ""));
        }
    };
});
</script>
