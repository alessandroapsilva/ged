<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<style>
/* Estilos específicos para a página de digitalização */
.digitalizar-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.digitalizar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.digitalizar-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.digitalizar-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 2rem;
}

.scan-controls-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-section {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-100);
}

.card-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-field {
    margin-bottom: 1.25rem;
}

.form-field:last-child {
    margin-bottom: 0;
}

.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.field-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
    background: var(--white);
}

.field-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.fields-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    cursor: pointer;
    user-select: none;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.status-box {
    padding: 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    font-size: 0.875rem;
    color: #0369a1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.status-icon {
    font-size: 1.25rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.btn-scan {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-scan:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-scan:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-action {
    width: 100%;
    padding: 0.75rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-action:hover:not(:disabled) {
    transform: translateY(-1px);
}

.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.preview-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.preview-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--gray-50);
}

.preview-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
}

.preview-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-icon {
    padding: 0.5rem;
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--gray-600);
}

.btn-icon:hover:not(:disabled) {
    background: var(--gray-100);
    border-color: var(--gray-400);
}

.btn-icon:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.btn-danger {
    color: var(--danger);
    border-color: var(--danger);
}

.btn-danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.1);
}

.page-info {
    font-size: 0.875rem;
    color: var(--gray-600);
    white-space: nowrap;
}

.preview-body {
    flex: 1;
    min-height: 600px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.empty-preview {
    text-align: center;
    color: var(--gray-400);
}

.empty-preview i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.alert-box {
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    border-left: 4px solid;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border-color: var(--warning);
    color: #92400e;
}

.alert-info {
    background: rgba(8, 145, 178, 0.1);
    border-color: var(--info);
    color: #164e63;
}

.help-content {
    font-size: 0.875rem;
    line-height: 1.6;
}

.help-content ul {
    margin: 0.5rem 0 0.5rem 1.5rem;
}

.help-content li {
    margin-bottom: 0.5rem;
}

.help-content strong {
    color: var(--gray-800);
}

.help-content code {
    background: rgba(0,0,0,0.05);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-size: 0.8125rem;
}

@media (max-width: 1024px) {
    .digitalizar-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .digitalizar-container {
        padding: 1rem;
    }
    
    .fields-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    
    <div class="digitalizar-container">
        <div class="digitalizar-header">
            <h1 class="digitalizar-title">
                <i class="fas fa-scanner" style="color: var(--primary);"></i>
                Central de Digitalização
            </h1>
            <a href="documentos.php" class="btn-action btn-secondary" style="width: auto; padding: 0.625rem 1.25rem;">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
        </div>

        <div class="digitalizar-grid">
            <!-- Coluna Esquerda: Controles -->
            <div>
                <form id="form-digitalizacao">
                    <!-- Scanner Controls -->
                    <div class="scan-controls-card">
                        <div class="card-section">
                            <h2 class="section-title">
                                <i class="fas fa-print"></i>
                                Configurações do Scanner
                            </h2>
                            
                            <div class="form-field">
                                <label class="field-label">Scanner</label>
                                <select id="select-scanner" class="field-input">
                                    <option value="">Carregando...</option>
                                </select>
                            </div>

                            <div class="fields-row">
                                <div class="form-field">
                                    <label class="field-label">Resolução (DPI)</label>
                                    <input type="number" id="dpi" class="field-input" value="200" min="72" max="600">
                                </div>
                                <div class="form-field">
                                    <label class="field-label">Modo de Cor</label>
                                    <select id="pixel-type" class="field-input">
                                        <option value="rgb">🎨 Colorido</option>
                                        <option value="gray">⚫ Cinza</option>
                                        <option value="bw">⚪ P&B</option>
                                    </select>
                                </div>
                            </div>

                            <div class="fields-row">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="chk-adf">
                                    Usar ADF
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="chk-duplex">
                                    Duplex
                                </label>
                            </div>

                            <div class="form-field">
                                <label class="field-label">Limite de Páginas (opcional)</label>
                                <input type="number" id="max-paginas" class="field-input" placeholder="Sem limite" min="1">
                            </div>

                            <div class="status-box" id="div-status">
                                <span class="status-icon">⏳</span>
                                <span>Inicializando serviço...</span>
                            </div>
                        </div>

                        <div class="card-section">
                            <button type="button" id="btn-scan" class="btn-scan" disabled>
                                <i class="fas fa-camera-retro"></i>
                                Digitalizar
                            </button>
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="scan-controls-card" style="margin-top: 1.5rem;">
                        <div class="card-section">
                            <h2 class="section-title">
                                <i class="fas fa-file-alt"></i>
                                Dados do Documento
                            </h2>
                            
                            <div class="form-field">
                                <label class="field-label">Título *</label>
                                <input type="text" name="titulo" id="titulo" class="field-input" required 
                                       placeholder="Ex: Contrato de Serviço 2025">
                            </div>

                            <div class="form-field">
                                <label class="field-label">Tipo de Documento</label>
                                <select name="tipo_documento_id" id="select-tipo-documento" class="field-input">
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <label class="checkbox-label">
                                <input type="checkbox" id="chk-ocr">
                                Executar OCR (extrair texto)
                            </label>

                            <div id="metadados-dinamicos"></div>
                        </div>

                        <div class="card-section">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <button type="button" id="btn-save" class="btn-action btn-success" disabled>
                                    <i class="fas fa-save"></i>
                                    Salvar no GED
                                </button>
                                <button type="button" id="btn-download" class="btn-action btn-secondary" disabled>
                                    <i class="fas fa-download"></i>
                                    Baixar PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Manual (hidden by default) -->
                    <div class="scan-controls-card alert-box alert-warning" id="card-upload-manual" style="display:none; margin-top: 1.5rem;">
                        <div class="help-content">
                            <strong>📁 Alternativa: Upload Manual</strong>
                            <p style="margin: 0.5rem 0;">Se o scanner não funcionar, você pode fazer upload de um PDF:</p>
                            <div class="form-field" style="margin-top: 1rem;">
                                <input type="file" id="arquivo-manual" class="field-input" accept="application/pdf">
                                <small style="display: block; margin-top: 0.5rem; color: var(--gray-600);">
                                    Máximo: 50 MB
                                </small>
                            </div>
                            <button type="button" id="btn-save-upload" class="btn-action btn-success" disabled style="margin-top: 1rem;">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Enviar Arquivo
                            </button>
                        </div>
                    </div>

                    <!-- Help Section (hidden by default) -->
                    <div class="scan-controls-card alert-box alert-info" id="install-help" style="display:none; margin-top: 1.5rem;">
                        <div class="help-content">
                            <strong>⚠️ Serviço de Digitalização Não Encontrado</strong>
                            <p style="margin: 0.5rem 0 1rem;">Para usar o scanner, instale o serviço Dynamsoft:</p>
                            <ul>
                                <li><a href="https://download.dynamsoft.com/web-twain/setup/DynamsoftServiceSetup.msi" 
                                       target="_blank" style="color: inherit; text-decoration: underline;">
                                    <strong>Baixar instalador (MSI)</strong>
                                </a></li>
                                <li>Após instalar, <strong>reinicie esta página</strong></li>
                                <li>Portas: HTTP <code>18622</code> | HTTPS <code>18623</code></li>
                            </ul>
                            <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                                <button type="button" id="btn-retry-service" class="btn-action btn-secondary" style="flex: 1;">
                                    ↻ Tentar Novamente
                                </button>
                                <button type="button" id="btn-force-http" class="btn-action btn-secondary" style="flex: 1;">
                                    HTTP (Forçar)
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Coluna Direita: Preview -->
            <div class="preview-card">
                <div class="preview-header">
                    <h3 class="preview-title">Pré-visualização</h3>
                    <div class="preview-controls">
                        <button type="button" class="btn-icon" id="btn-prev-page" title="Página Anterior" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="page-info" class="page-info">Página 0 de 0</span>
                        <button type="button" class="btn-icon" id="btn-next-page" title="Próxima Página" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="btn-icon btn-danger" id="btn-remove-page" title="Apagar Página" disabled>
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="preview-body" id="preview-body">
                    <div class="empty-preview">
                        <i class="fas fa-file-pdf"></i>
                        <p>Nenhuma página digitalizada</p>
                    </div>
                    <div id="dwtcontrolContainer" style="width: 100%; height: 100%; display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

<!-- Dynamsoft WebTWAIN Scripts -->
<script src="js/dynamsoft.webtwain.min.js"></script>
<script src="js/Resources/dynamsoft.webtwain.config.js"></script>
<script src="js/dynamsoft.webtwain.init.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 Script DOMContentLoaded iniciado');
    let DWObject = null; // Desabilitado - usando upload manual
    const statusEl = document.getElementById('div-status');
    const installHelp = document.getElementById('install-help');
    const cardUploadManual = document.getElementById('card-upload-manual');
    const previewBody = document.getElementById('preview-body');
    const emptyPreview = document.querySelector('.empty-preview');
    const dwtContainer = document.getElementById('dwtcontrolContainer');
    let webtwainReady = false;
    let loadAttempt = 0;
    
    console.log('✅ Elementos do DOM localizados');
    console.log('- statusEl:', statusEl);
    console.log('- cardUploadManual:', cardUploadManual);
    console.log('- btnSaveUpload:', document.getElementById('btn-save-upload'));

    // Scanner via Dynamsoft REATIVADO para versão 19.2
    // Se falhar o carregamento, o usuário ainda verá o upload manual como opção.
    function showFallback() {
        // Fallback apenas se necessário
        if (installHelp) installHelp.style.display = 'block'; 
    }

    // console.warn('⚠️ Scanner (Dynamsoft) desativado - usando apenas upload manual.');
    // showFallback();

    
    // Configuração para Dynamsoft 19.2
    Dynamsoft.DWT.ProductKey = 't0200EQYAAIqF7ULlzeXyetmQqDjsghHfezbNM7OaVUgjn1UnuM8+Nxogctuj7hdPJZwiB3wZAosIajHHOZyvtawQdgjUnZded04Z4NT9nVHc18kBTnnkBHz8dOm0VRx3DJAdmNNkn+vwBVgCaS0XYDFn750hA6QF6AagW2tgC6juIiRfyuQdv785/e9Aa04Z4NT9nWWB9HFygFMeOUOBTA5qC7vdUoGwvDkZIC1AV4E4L1EpEFwBaQG6CiyXKpYMwOSMs9b7N7tqPpU=';
    Dynamsoft.DWT.ResourcesPath = 'js/Resources';
    Dynamsoft.DWT.AutoLoad = true;
    
    // Função para carregar o WebTwain
    function loadWebTwain() {
        if (typeof Dynamsoft !== 'undefined') {
             Dynamsoft.DWT.Load();
        }
    }
    
    // Inicia carregamento
    loadWebTwain();
    
    if (typeof Dynamsoft !== 'undefined') {
         Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', function() {
              console.log('✅ Dynamsoft Ready!');
              webtwainReady = true;
              if (installHelp) installHelp.style.display = 'none';
              
              DWObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
              if (DWObject) {
                  document.getElementById('dwtcontrolContainer').style.display = 'block';
                  document.querySelector('.empty-preview').style.display = 'none';
                  
                  // Atualiza Status Visual
                  if (statusEl) {
                      statusEl.className = 'status-box status-ok';
                      statusEl.innerHTML = '<span class="status-icon">✅</span><span>Serviço Conectado</span>';
                  }

                  // Popula lista de scanners
                  const selectScanner = document.getElementById('select-scanner');
                  if (selectScanner) {
                      selectScanner.innerHTML = '';
                      const count = DWObject.SourceCount;
                      if (count === 0) {
                          selectScanner.innerHTML = '<option value="">Nenhum scanner detectado</option>';
                      } else {
                          for (let i = 0; i < count; i++) {
                              const opt = document.createElement('option');
                              opt.value = i;
                              opt.text = DWObject.GetSourceNameItems(i);
                              selectScanner.appendChild(opt);
                          }
                      }
                  }
                  
                  // Habilita controles
                  const btnScan = document.getElementById('btn-scan');
                  const btnSave = document.getElementById('btn-save');
                  const btnDownload = document.getElementById('btn-download');

                  // Evento disparado após cada imagem digitalizada
                  DWObject.RegisterEvent('OnPostTransfer', function() {
                      console.log('📸 Imagem capturada!');
                      if (btnSave) btnSave.disabled = false;
                      if (btnDownload) btnDownload.disabled = false;
                  });

                  // Botão Digitalizar
                  if (btnScan) {
                      btnScan.disabled = false;
                      btnScan.onclick = function() {
                          if (DWObject) {
                              if (selectScanner && selectScanner.value !== '') {
                                  DWObject.SelectSourceByIndex(selectScanner.value);
                              } else {
                                  DWObject.SelectSource(); 
                              }
                              
                              DWObject.OpenSource();
                              
                              DWObject.Resolution = parseInt(document.getElementById('dpi').value || 200);
                              DWObject.IfFeederEnabled = document.getElementById('chk-adf').checked;
                              DWObject.IfDuplexEnabled = document.getElementById('chk-duplex').checked;
                              const pixelType = document.getElementById('pixel-type').value;
                              if (pixelType === 'gray') DWObject.PixelType = 1;
                              else if (pixelType === 'bw') DWObject.PixelType = 0;
                              else DWObject.PixelType = 2; // RGB

                              DWObject.AcquireImage();
                          }
                      };
                  }

                  // Botão Salvar (Scanner)
                  if (btnSave) {
                      btnSave.onclick = function() {
                          console.log('💾 Tentando salvar...');
                          
                          // Validação Título
                          const titulo = document.getElementById('titulo');
                          if (!titulo || !titulo.value.trim()) {
                              alert('⚠️ O campo "Título" é obrigatório.');
                              if(titulo) {
                                  titulo.scrollIntoView({behavior: "smooth", block: "center"});
                                  titulo.focus();
                                  titulo.style.borderColor = "red";
                              }
                              return;
                          }

                          if (DWObject.HowManyImagesInBuffer > 0 || true) { // Force attempt implies checking buffer inside
                              if (DWObject.HowManyImagesInBuffer === 0) {
                                  alert('⚠️ Nenhuma imagem digitalizada para salvar. Utilize o scanner primeiro.');
                                  return;
                              }

                              const btn = this;
                              btn.disabled = true;
                              btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando PDF...';

                              const indices = [];
                              for (let i = 0; i < DWObject.HowManyImagesInBuffer; i++) indices.push(i);

                              DWObject.ConvertToBlob(
                                  indices,
                                  Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF,
                                  function(blob) {
                                      console.log('📄 PDF Gerado, tamanho:', blob.size);
                                      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando para servidor...';
                                      
                                      const formData = new FormData(document.getElementById('form-digitalizacao'));
                                      formData.append('documento_digitalizado', blob, 'scan.pdf');
                                      formData.append('captura_modo', 'webtwain');
                                      
                                      fetch('digitalizar_processar.php', { method: 'POST', body: formData })
                                          .then(async response => {
                                              const text = await response.text();
                                              try {
                                                  return JSON.parse(text);
                                              } catch (e) {
                                                  console.error('Resposta não-JSON:', text);
                                                  throw new Error('O servidor retornou uma resposta inválida (provavelmente erro PHP ou sessão expirada). Veja o console.');
                                              }
                                          })
                                          .then(data => {
                                              if (data.sucesso) {
                                                  alert('✅ Documento salvo com sucesso!');
                                                  window.location.href = 'documentos.php';
                                              } else {
                                                  alert('❌ Erro retornado pelo sistema: ' + data.mensagem);
                                                  btn.disabled = false;
                                                  btn.innerHTML = '<i class="fas fa-save"></i> Salvar no GED';
                                              }
                                          })
                                          .catch(err => {
                                              console.error(err);
                                              alert('❌ Erro de comunicação: ' + err.message);
                                              btn.disabled = false;
                                              btn.innerHTML = '<i class="fas fa-save"></i> Salvar no GED';
                                          });
                                  },
                                  function(errorCode, errorString) {
                                      console.error('Dynamsoft Error:', errorCode, errorString);
                                      alert('❌ Erro da Biblioteca de Scanner: ' + errorString);
                                      btn.disabled = false;
                                      btn.innerHTML = '<i class="fas fa-save"></i> Salvar no GED';
                                  }
                              );
                          }
                      };
                  }
              }
         });
    }

    /* 
    Legacy Block Removed/Replaced
    */

    // (todo o restante do código do scanner permanece comentado no bloco acima)

    // Manual upload handlers
    const manualFileInput = document.getElementById('arquivo-manual');
    const btnSaveUpload = document.getElementById('btn-save-upload');
    
    console.log('🔵 MANUAL UPLOAD SECTION');
    console.log('📁 manualFileInput:', !!manualFileInput, manualFileInput?.id);
    console.log('🔘 btnSaveUpload:', !!btnSaveUpload, btnSaveUpload?.id);
    
    if (manualFileInput) {
        console.log('✅ manualFileInput ENCONTRADO - adicionando change listener');
        manualFileInput.addEventListener('change', function() {
            console.log('📁 change event disparado');
            const file = this.files && this.files[0];
            if (!file) {
                console.log('⚫ Nenhum arquivo selecionado');
                btnSaveUpload.disabled = true;
                return;
            }
            console.log('✅ Arquivo:', file.name, file.size, 'bytes');
            const maxSize = 50 * 1024 * 1024;
            if (file.type !== 'application/pdf') {
                alert('Apenas PDF é aceito.');
                this.value = '';
                btnSaveUpload.disabled = true;
                return;
            }
            if (file.size > maxSize) {
                alert('Arquivo maior que 50 MB.');
                this.value = '';
                btnSaveUpload.disabled = true;
                return;
            }
            btnSaveUpload.disabled = false;
            console.log('✅ Botão habilitado');
        });
    } else {
        console.error('❌ manualFileInput NÃO ENCONTRADO!!!');
    }
    
    if (btnSaveUpload) {
        console.log('✅ btnSaveUpload ENCONTRADO - adicionando click listener');
        btnSaveUpload.addEventListener('click', function(e) {
            console.log('🔴 CLIQUE DETECTADO!');
            e.preventDefault();
            
            if (!manualFileInput.files || !manualFileInput.files[0]) {
                alert('Selecione um arquivo.');
                return;
            }
            
            const titulo = document.getElementById('titulo');
            if (!titulo || !titulo.value.trim()) {
                alert('Preencha o título do documento.');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            
            const form = document.getElementById('form-digitalizacao');
            const formData = new FormData(form);
            formData.append('documento_digitalizado', manualFileInput.files[0]);
            formData.append('conteudo_ocr', '');
            formData.append('captura_modo', 'upload_manual');
            formData.append('scanner_nome', '');
            
            console.log('🟢 Enviando POST para digitalizar_processar.php');
            
            fetch('digitalizar_processar.php', { method: 'POST', body: formData })
                .then(response => {
                    console.log('🟢 Status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('🟢 Resposta:', text);
                    const data = JSON.parse(text);
                    if (data.sucesso) {
                        alert('✅ Documento salvo!');
                        window.location.href = 'documentos.php';
                    } else {
                        alert('❌ ' + data.mensagem);
                    }
                })
                .catch(err => {
                    console.error('❌ Erro:', err);
                    alert('Erro: ' + err.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Enviar Arquivo';
                });
        });
    } else {
        console.error('❌ btnSaveUpload NÃO ENCONTRADO!!!');
    }

    // Retry button
    document.getElementById('btn-retry-service')?.addEventListener('click', () => {
        webtwainReady = false;
        loadAttempt = 0;
        hideFallback();
        cardUploadManual.style.display = 'none';
        loadWebTwain();
    });

    // Metadata loader
    document.getElementById('select-tipo-documento').addEventListener('change', function() {
        const tipoId = this.value;
        const container = document.getElementById('metadados-dinamicos');
        container.innerHTML = '';
        if (tipoId) {
            fetch(`ajax_get_metadados_fields.php?tipo_id=${tipoId}`)
                .then(r => r.json())
                .then(campos => {
                    if (campos.length > 0) {
                        let html = '<div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);"><strong style="display: block; margin-bottom: 1rem; color: var(--gray-700);">Metadados Adicionais:</strong>';
                        campos.forEach(campo => {
                            html += `<div class="form-field">
                                <label class="field-label">${campo.rotulo}</label>
                                <input type="text" class="field-input" name="meta[${campo.id}]">
                            </div>`;
                        });
                        html += '</div>';
                        container.innerHTML = html;
                    }
                })
                .catch(err => console.error(err));
        }
    });
});
</script>
