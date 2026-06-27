<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// ##### LINHA 6 CORRIGIDA #####
// Removemos a condição 'WHERE apagado_em IS NULL' que estava causando o erro.
// Carrega tipos de documento com tolerância a falhas de banco
$tipos_documento = [];
try {
    $stmtTipos = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC");
    if ($stmtTipos) {
        $tipos_documento = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $tipos_documento = [];
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid"><h1>Central de Digitalização</h1></div>
    </section>

    <section class="content">
        <div class="container-fluid"><div class="row">
            <div class="col-lg-4">
                <form id="form-digitalizacao">
                    <div class="card card-primary card-outline">
                        <div class="card-header"><h3 class="card-title">1. Controles do Scanner</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="select-scanner">Selecione um Scanner:</label>
                                <select id="select-scanner" class="form-control"></select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="dpi">Resolução (DPI)</label>
                                    <input type="number" id="dpi" class="form-control" value="200" min="72" max="600" step="1">
                                </div>
                                <div class="form-group col-6">
                                    <label for="pixel-type">Modo de Cor</label>
                                    <select id="pixel-type" class="form-control">
                                        <option value="rgb" selected>Colorido (RGB)</option>
                                        <option value="gray">Tons de Cinza</option>
                                        <option value="bw">Preto e Branco</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="chk-adf">
                                        <label class="custom-control-label" for="chk-adf">Usar ADF</label>
                                    </div>
                                </div>
                                <div class="form-group col-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="chk-duplex">
                                        <label class="custom-control-label" for="chk-duplex">Duplex</label>
                                    </div>
                                </div>
                                <div class="form-group col-4">
                                    <label for="max-paginas">Limite de Páginas</label>
                                    <input type="number" id="max-paginas" class="form-control" placeholder="Ilimitado" min="1">
                                </div>
                            </div>
                            <div id="div-status" class="text-muted small mt-2">Status: Inicializando...</div>
                            <div id="install-help" class="alert alert-warning mt-3" style="display:none">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                                    <div>
                                        <strong>Serviço de Digitalização não encontrado ou não acessível.</strong>
                                        <div class="mt-1">Se você já instalou o serviço, siga os passos abaixo e depois clique em "Tentar Novamente". Abra o Console do Desenvolvedor (F12) para ver mensagens de depuração.</div>
                                        <ul class="mb-2 mt-2">
                                            <li><strong>Passo 1 (Firewall):</strong> Verifique se o Firewall do Windows ou seu antivírus não está bloqueando a comunicação. Libere as portas 18622 (HTTP) e 18623 (HTTPS) se necessário.</li>
                                            <li><strong>Passo 2 (HTTPS):</strong> Se esta página usa HTTPS, o navegador pode bloquear a conexão. Para resolver, abra uma nova aba e acesse <a href="https://localhost:18623" target="_blank">https://localhost:18623</a> ou <a href="https://127.0.0.1:18623" target="_blank">https://127.0.0.1:18623</a>. Você verá um alerta de segurança. Clique em "Avançado" e depois em "Continuar para..." para aceitar o certificado. Após fazer isso, volte para esta página.</li>
                                            <li><strong>Passo 3 (Reinstalação):</strong> Se nada funcionar, tente reinstalar o serviço: <a href="https://download.dynamsoft.com/web-twain/setup/DynamsoftServiceSetup.msi" target="_blank" rel="noopener">Baixar instalador (MSI)</a>.</li>
                                        <li><strong>Testar serviço local:</strong> <a href="http://127.0.0.1:18622" target="_blank" rel="noopener">http://127.0.0.1:18622</a></li></ul>
                                        <button type="button" id="btn-retry-service" class="btn btn-sm btn-primary">Tentar Novamente</button>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <button type="button" id="btn-scan" class="btn btn-primary btn-block" disabled><i class="fas fa-camera-retro mr-2"></i>Digitalizar Página</button>
                        </div>
                    </div>

                    <div class="card card-primary card-outline">
                        <div class="card-header"><h3 class="card-title">2. Detalhes do Documento</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="titulo">Título do Documento</label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Contrato de Serviço 123">
                            </div>
                            <div class="form-group">
                                <label for="select-tipo-documento">Tipo de Documento</label>
                                <select name="tipo_documento_id" id="select-tipo-documento" class="form-control">
                                    <option value="">-- Selecione para carregar metadados --</option>
                                    <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="chk-ocr">
                                    <label class="custom-control-label" for="chk-ocr">Executar OCR (Extrair Texto)</label>
                                </div>
                            </div>
                            <div id="metadados-dinamicos" class="mt-3"></div>
                        </div>
                    </div>
                    <div class="card card-warning card-outline" id="card-upload-manual" style="display:none">
                        <div class="card-header"><h3 class="card-title">Alternativa: Upload Manual</h3></div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Se o serviço não estiver disponível, exporte seu documento como PDF no seu software de digitalização (ex.: NAPS2, HP, Epson) e anexe abaixo.</p>
                            <div class="form-group">
                                <label for="arquivo-manual">Arquivo</label>
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
                            <button type="button" id="btn-download" class="btn btn-secondary btn-block" disabled title="Digitalize ou carregue um documento para habilitar"><i class="fas fa-download mr-2"></i>Baixar no PC</button>
                            <button type="button" id="btn-save" class="btn btn-success btn-block mt-2" disabled title="Digitalize ou carregue um documento para habilitar"><i class="fas fa-save mr-2"></i>Salvar no GED</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Pré-visualização</h3>
                        <div class="card-tools">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev-page" title="Página Anterior" disabled><i class="fas fa-arrow-left"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-next-page" title="Próxima Página" disabled><i class="fas fa-arrow-right"></i></button>
                            </div>
                            <span id="page-info" class="ml-2 text-muted">Página 0 de 0</span>
                            <button type="button" class="btn btn-sm btn-outline-danger ml-3" id="btn-remove-page" title="Apagar Página Atual" disabled><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="min-height: 80vh; background-color: #2f3640;">
                        <div class="px-2 py-1 border-bottom" style="background:#1f252c;">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group btn-group-sm mr-2" role="group">
                                    <button type="button" class="btn btn-outline-light" id="btn-zoom-out" title="Zoom -"><i class="fas fa-search-minus"></i></button>
                                    <button type="button" class="btn btn-outline-light" id="btn-zoom-in" title="Zoom +"><i class="fas fa-search-plus"></i></button>
                                </div>
                                <div class="btn-group btn-group-sm mr-2" role="group">
                                    <button type="button" class="btn btn-outline-light" id="btn-rotate-left" title="Girar Esquerda"><i class="fas fa-undo"></i></button>
                                    <button type="button" class="btn btn-outline-light" id="btn-rotate-right" title="Girar Direita"><i class="fas fa-redo"></i></button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-light" id="btn-fit-width" title="Ajustar Largura"><i class="fas fa-arrows-alt-h"></i></button>
                                    <button type="button" class="btn btn-outline-light" id="btn-fit-page" title="Ajustar Página"><i class="fas fa-expand"></i></button>
                                </div>
                            </div>
                        </div>
                        <div id="dwtcontrolContainer" style="height: calc(80vh - 34px);"></div>
                    </div>
                </div>
            </div>
        </div></div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<!-- Carregamento direto da biblioteca Dynamsoft -->
<script src="https://cdn.jsdelivr.net/npm/dwt@19.2.0/dist/dynamsoft.webtwain.min.js"></script>

<script>
(function(start){
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
else start();
})(function () {
    console.log('DWT: Inicializando script de digitalização (método simplificado).');
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
    let loadAttempt = 0;

    const BASE = '<?php echo BASE_URL; ?>';

    function bootstrapDWT() {
        console.log('DWT: Inicializando Dynamsoft Web TWAIN...');
        statusEl.innerText = 'Status: Configurando biblioteca...';

        // A chave de licença de teste pública é usada aqui. Para produção, obtenha uma chave de licença completa da Dynamsoft.
        Dynamsoft.DWT.ProductKey = 't01908AUAACv0PI01Ibf0OTvec661vrA/tK+fsiKgZdDmiaiQ09mFc3xprFmdcNoAgIwui73GYbIlcVACBOOyJJ7xSErX0WYtrycnOzi1vVOlvRMdnKxyirjYnfbbNjEuBNADcwT0eA4HgBTY13IB3sM2+2Q4ASwBWgC0tAaUgNtd5IcfhvmZVl1oGLKDU9s70wRp40QHJ6uca4LYScyy7nbZEwTpyzkBLAF6C+D/kWUJIleAJUAzwBKkU/sDxJszTw==';
        // O caminho dos recursos (ResourcesPath) deve apontar para a mesma versão da biblioteca.
        Dynamsoft.DWT.ResourcesPath = "https://cdn.jsdelivr.net/npm/dwt@19.2.0/dist/";
        
        console.log('DWT: Caminho dos Recursos (ResourcesPath):', Dynamsoft.DWT.ResourcesPath);
        Dynamsoft.DWT.UseLocalService = true;
        
        Dynamsoft.DWT.Containers = [{ ContainerId: 'dwtcontrolContainer', Width: '100%', Height: '100%' }];
        Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', Dynamsoft_OnReady);
        Dynamsoft.DWT.RegisterEvent('OnBufferChanged', updateControls);
        Dynamsoft.DWT.RegisterEvent('OnWebTwainInitMessage', (msg) => console.warn('DWT Mensagem de Inicialização:', msg));
        Dynamsoft.DWT.RegisterEvent('OnPostAllTransfers', () => console.log('DWT: Todas as imagens foram transferidas.'));

        statusEl.innerText = 'Status: Carregando serviço...';
        loadWebTwain();
    }

    function configureConnection(attempt = 0) {
        const isHttps = window.location.protocol === 'https:';
        const combos = [
            { proto: isHttps ? 'https' : 'http', host: '127.0.0.1' },
            { proto: isHttps ? 'https' : 'http', host: 'localhost' },
            { proto: isHttps ? 'http'  : 'https', host: '127.0.0.1' },
            { proto: isHttps ? 'http'  : 'https', host: 'localhost' }
        ];
        const idx = Math.max(0, Math.min(attempt, combos.length - 1));
        const c = combos[idx];
        Dynamsoft.DWT.Protocol = c.proto;
        Dynamsoft.DWT.Port = c.proto === 'https' ? 18623 : 18622;
        Dynamsoft.DWT.Host = c.host;
        const statusText = `Status: Conectando ao serviço em ${c.proto}://${c.host}:${Dynamsoft.DWT.Port} ... (Tentativa ${attempt + 1}/4)`;
        console.log(`DWT: ${statusText}`);
        try { statusEl.innerText = statusText; } catch(_) {}
    }

    function showInstallHelp() {
        console.error('DWT: Não foi possível conectar ao serviço. Exibindo ajuda.');
        installHelp.style.display = '';
        cardUploadManual.style.display = '';
        document.getElementById('btn-scan').disabled = true;
        statusEl.innerText = 'Status: Serviço não encontrado. Veja instruções acima ou use o Upload Manual.';
    }

    function hideInstallHelp() {
        installHelp.style.display = 'none';
    }

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
        console.log(`DWT: Buffer atualizado. Total: ${total}, Página Atual: ${current}`);
    }

    function Dynamsoft_OnReady() {
        console.log('DWT: Evento OnWebTwainReady disparado. A biblioteca está pronta.');
        webtwainReady = true;
        hideInstallHelp();
        statusEl.innerText = 'Status: Biblioteca pronta.';
        DWObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
        if (!DWObject) {
            console.error('DWT: Falha ao obter o objeto WebTwain do container dwtcontrolContainer.');
            statusEl.innerText = 'Status: Erro ao iniciar componente de digitalização.';
            return;
        }

        try { DWObject.Viewer.ifShowPageNumber = false; } catch(_) {}
        try { DWObject.Viewer.ifShowNavigators = false; } catch(_) {}
        try { if (DWObject.Viewer.setViewMode) DWObject.Viewer.setViewMode(1,1); } catch(_) {}
        updateControls(); 

        statusEl.innerText = 'Status: Procurando scanners...';
        console.log('DWT: Procurando scanners disponíveis...');
        
        const getSources = () => {
            return new Promise((resolve) => {
                try {
                    if (DWObject.GetSourceNamesAsync) {
                        let TWAIN = null;
                        try {
                            const Enum = Dynamsoft.DWT.EnumDWT_DriverType || {};
                            TWAIN = (Enum.TWAIN !== undefined) ? Enum.TWAIN : (Enum.DRIVER_TWAIN !== undefined ? Enum.DRIVER_TWAIN : null);
                        } catch(_) {}
                        const tryTwain = () => {
                            if (TWAIN === null) return DWObject.GetSourceNamesAsync().then(resolve).catch(() => resolve([]));
                            return DWObject.GetSourceNamesAsync(TWAIN)
                                .then(names => { if (names && names.length) resolve(names); else resolve([]); })
                                .catch(() => DWObject.GetSourceNamesAsync().then(resolve).catch(() => resolve([])));
                        };
                        return tryTwain();
                    } else {
                        var count = DWObject.SourceCount || 0;
                        var items = DWObject.GetSourceNameItems ? DWObject.GetSourceNameItems(0, count) || [] : [];
                        resolve(items);
                    }
                } catch(e){ resolve([]); }
            });
        };

        getSources().then(sources => {
            console.log('DWT: Scanners encontrados:', sources);
            statusEl.innerText = `Status: ${sources.length} scanner(s) encontrado(s).`;
            let selectScanner = document.getElementById('select-scanner');
            selectScanner.innerHTML = '';
            sources.forEach((source, index) => selectScanner.add(new Option(source, index)));
            document.getElementById('btn-scan').disabled = sources.length === 0;
            if (sources.length === 0) {
                selectScanner.add(new Option('Nenhum scanner encontrado', -1));
                statusEl.innerText = 'Status: Nenhum scanner encontrado. Verifique se o driver TWAIN do seu scanner está instalado.';
            }
        }).catch(err => {
            console.error('DWT: Erro ao obter a lista de scanners.', err);
            statusEl.innerText = 'Status: Erro ao listar scanners.';
        });
    }

    function loadWebTwain() {
        configureConnection(loadAttempt);
        try {
            console.log('DWT: Chamando Dynamsoft.DWT.Load() para carregar o serviço.');
            Dynamsoft.DWT.Load();
        } catch (e) {
            console.error('DWT: Erro ao iniciar o serviço', e);
        }
        
        setTimeout(() => {
            if (!webtwainReady) {
                console.warn(`DWT: Timeout de 10s atingido, o serviço não respondeu a tempo. webtwainReady=${webtwainReady}`);
                if (loadAttempt < 3) {
                    loadAttempt += 1;
                    console.log(`DWT: Tentando conexão alternativa #${loadAttempt + 1}`);
                    loadWebTwain();
                } else {
                    showInstallHelp();
                }
            }
        }, 10000);
    }

    // --- INÍCIO DA EXECUÇÃO ---
    if (typeof Dynamsoft === 'undefined' || typeof Dynamsoft.DWT === 'undefined') {
        console.error('DWT: A biblioteca Dynamsoft não foi carregada. Verifique o link do script ou a conexão com a internet.');
        statusEl.innerText = 'Status: Erro crítico! A biblioteca de digitalização não pôde ser carregada.';
        installHelp.style.display = '';
        cardUploadManual.style.display = '';
        return;
    }
    
    bootstrapDWT();

    if (btnRetryService) {
        btnRetryService.addEventListener('click', () => {
            console.log('DWT: Botão "Tentar Novamente" clicado.');
            webtwainReady = false;
            loadAttempt = 0;
            hideInstallHelp();
            statusEl.innerText = 'Status: Recarregando...';
            loadWebTwain();
        });
    }

    // Digitalizar
    document.getElementById('btn-scan').onclick = () => {
        if (!DWObject) return;
        const sel = document.getElementById('select-scanner');
        const selectedIndex = sel.selectedIndex;
        const selectedValue = sel.options[selectedIndex] ? sel.options[selectedIndex].value : -1;
        const scannerNome = sel.options[selectedIndex]?.text || '';

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

        const afterAcquire = () => {
            updateControls();
            const total = DWObject.HowManyImagesInBuffer;
            console.log(`DWT: Digitalização concluída. Total de imagens no buffer: ${total}`);

            if (total === 0) {
                alert('Digitalização concluída, mas nenhuma imagem foi recebida do scanner. Verifique se há papel no alimentador ou se o scanner está funcionando corretamente.');
            }

            if (maxPages > 0 && total > maxPages) {
                for (let i = total - 1; i >= maxPages; i--) {
                    try { DWObject.RemoveImage(i); } catch(e) { console.warn('Falha ao remover página extra', e); }
                }
                updateControls();
                alert('Foram mantidas apenas ' + maxPages + ' páginas conforme limite definido.');
            }
            
            try { if (total > 0) DWObject.GoToImage(total - 1); } catch(_) {}
            try { if (DWObject.Viewer && DWObject.Viewer.setViewMode) DWObject.Viewer.setViewMode(1,1); } catch(_) {}
            document.getElementById('form-digitalizacao').dataset.scannerNome = scannerNome;
        };

        const acquireOptions = {
            IfDisableSourceAfterAcquire: true,
            IfShowUI: false,
            PixelType: pixelType,
            Resolution: dpi,
            IfFeederEnabled: useAdf,
            IfDuplexEnabled: useDuplex,
            OnTaskDetected: (task) => console.log('DWT: Tarefa de Digitalização Detectada:', task),
            OnTaskAborted: (task) => console.warn('DWT: Tarefa de Digitalização Abortada:', task),
            OnTaskError: (task, error) => console.error('DWT: Erro na Tarefa de Digitalização:', task, error)
        };

        console.log('DWT: Iniciando digitalização com as opções:', acquireOptions);
        
        if (DWObject.SelectSourceByIndexAsync && DWObject.AcquireImageAsync) {
            const idx = selectedValue !== undefined ? Number(selectedValue) : -1;
            DWObject.SelectSourceByIndexAsync(idx).then(() => {
                return DWObject.AcquireImageAsync(acquireOptions).then(afterAcquire);
            }).catch(exp => {
                console.error('DWT: Falha na digitalização (async).', exp.message);
                alert('Falha na digitalização: ' + exp.message);
            });
        } else {
            try {
                if (selectedValue == -1) DWObject.SelectSource();
                else DWObject.SelectSourceByIndex(Number(selectedValue));
                DWObject.IfShowUI = false;
                DWObject.Resolution = dpi;
                DWObject.PixelType = pixelType;
                DWObject.IfFeederEnabled = useAdf;
                DWObject.IfDuplexEnabled = useDuplex;
                if (DWObject.OpenSource) DWObject.OpenSource();
                DWObject.AcquireImage();
                afterAcquire();
            } catch (e) {
                console.error('DWT: Falha na digitalização (sync).', e);
                alert('Falha na digitalização: ' + e.message);
            }
        }
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

    // Upload manual
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
                        let redirectUrl = 'documentos.php?t=' + new Date().getTime();
                        if (data.pasta_id) {
                            redirectUrl += '&pasta_id=' + data.pasta_id;
                        }
                        window.location.href = redirectUrl;
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
        if (!DWObject || DWObject.HowManyImagesInBuffer === 0) {
            return alert('Nenhuma imagem para salvar.');
        }
        if (!document.getElementById('titulo').value.trim()) {
            alert('Por favor, preencha o Título do Documento.');
            return;
        }
        if (!document.getElementById('select-tipo-documento').value) {
            alert('Por favor, selecione o Tipo de Documento.');
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
                    let redirectUrl = 'documentos.php?t=' + new Date().getTime();
                    if (data.pasta_id) {
                        redirectUrl += '&pasta_id=' + data.pasta_id;
                    }
                    window.location.href = redirectUrl;
                } else { throw new Error(data.mensagem || 'Erro desconhecido.'); }
            }).catch(error => {
                alert('Erro no upload: ' + error.message);
                this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Salvar no GED';
            });
        };
        
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
            DWObject.ConvertToBlob(indices, Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF, (blob) => uploadFile(blob, ""));
        }
    };
});
</script>

<script>
    // Extras: eventos e botões do visualizador
    try {
        Dynamsoft.DWT.RegisterEvent('OnPostAllTransfers', function(){ try { if (DWObject && DWObject.HowManyImagesInBuffer>0) DWObject.GoToImage(DWObject.HowManyImagesInBuffer-1); } catch(e){} updateControls(); });
        Dynamsoft.DWT.RegisterEvent('OnTopImageInTheViewChanged', function(){ updateControls(); });
    } catch(e) {}

    function viewerZoom(factor){
        try {
            if (DWObject && DWObject.Viewer && typeof DWObject.Viewer.zoom === 'number') {
                DWObject.Viewer.zoom = Math.max(0.1, Math.min(8, (DWObject.Viewer.zoom || 1) * factor));
            } else if (DWObject && DWObject.ZoomIn && factor>1) {
                DWObject.ZoomIn();
            } else if (DWObject && DWObject.ZoomOut && factor<1) {
                DWObject.ZoomOut();
            }
        } catch(e) {}
    }
    function viewerFit(mode){
        try {
            if (DWObject && DWObject.Viewer) {
                if (DWObject.Viewer.fitMode !== undefined && Dynamsoft.DWT && Dynamsoft.DWT.EnumDWT_ViewerFitMode) {
                    if (mode==='width') DWObject.Viewer.fitMode = Dynamsoft.DWT.EnumDWT_ViewerFitMode.FIT_WIDTH;
                    else DWObject.Viewer.fitMode = Dynamsoft.DWT.EnumDWT_ViewerFitMode.FIT_WINDOW;
                } else if (DWObject.Viewer.setViewMode) {
                    DWObject.Viewer.setViewMode(1,1);
                }
            }
        } catch(e) {}
    }
    function viewerRotate(dir){
        try {
            if (!DWObject) return;
            var idx = DWObject.CurrentImageIndexInBuffer;
            if (dir==='left' && DWObject.RotateLeft) DWObject.RotateLeft(idx);
            else if (dir==='right' && DWObject.RotateRight) DWObject.RotateRight(idx);
        } catch(e) {}
    }
    var btnZoomIn = document.getElementById('btn-zoom-in');
    var btnZoomOut = document.getElementById('btn-zoom-out');
    var btnRotL = document.getElementById('btn-rotate-left');
    var btnRotR = document.getElementById('btn-rotate-right');
    var btnFitW = document.getElementById('btn-fit-width');
    var btnFitP = document.getElementById('btn-fit-page');
    if (btnZoomIn) btnZoomIn.onclick = function(){ viewerZoom(1.25); };
    if (btnZoomOut) btnZoomOut.onclick = function(){ viewerZoom(0.8); };
    if (btnRotL) btnRotL.onclick = function(){ viewerRotate('left'); };
    if (btnRotR) btnRotR.onclick = function(){ viewerRotate('right'); };
    if (btnFitW) btnFitW.onclick = function(){ viewerFit('width'); };
    if (btnFitP) btnFitP.onclick = function(){ viewerFit('page'); };
</script>
