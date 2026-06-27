<?php
/**
 * Digitalização Moderna - GED
 */
require_once '../core/init_professional.php';

// Verifica autenticação
requireAuth();

// Inicializa Layout
$layout = new ProfessionalLayout('Digitalização de Documentos');
$layout->addBreadcrumb('Início', 'index.php');
$layout->addBreadcrumb('Digitalizar', '#');

// Adiciona scripts necessários
$layout->addScript(BASE_URL . '/public/js/dynamsoft.webtwain.min.js');
$layout->addScript(BASE_URL . '/public/js/Resources/dynamsoft.webtwain.config.js');
$layout->addScript(BASE_URL . '/public/js/dynamsoft.webtwain.init.js');

// Obtém tipos de documento
$db = DatabaseManager::getInstance();
try {
    $tipos_documento = $db->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
} catch (Exception $e) {
    $tipos_documento = [];
}

// Inicia buffer para o conteúdo
ob_start();
?>

<style>
    /* Estilos específicos para a página de digitalização */
    .digitalizar-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 1.5rem;
    }

    /* Control Cards */
    .scan-controls-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
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
        margin-top: 0;
    }

    .form-field {
        margin-bottom: 1rem;
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
        background: var(--white);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        cursor: pointer;
    }

    .preview-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        height: 800px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .preview-header {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
    }

    .preview-body {
        flex: 1;
        background: #f3f4f6;
        position: relative;
        overflow: hidden;
    }

    #dwtcontrolContainer {
        width: 100%;
        height: 100%;
    }

    /* Utilitários de botão */
    .btn-w-100 { width: 100%; }
    
    .btn-icon-text {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    @media (max-width: 1024px) {
        .digitalizar-grid {
            grid-template-columns: 1fr;
        }
        .preview-card {
            height: 500px;
        }
    }
</style>

<div class="digitalizar-container">
    <div class="digitalizar-grid">
        <!-- Left Panel: Controls -->
        <div>
            <form id="form-digitalizacao">
                
                <!-- Scanner Settings -->
                <div class="scan-controls-card">
                    <div class="card-section">
                        <h2 class="section-title">
                            <i class="fas fa-print"></i> Configuração do Scanner
                        </h2>
                        
                        <div class="form-field">
                            <label class="field-label">Dispositivo *</label>
                            <select id="source" class="field-input">
                                <option value="">Carregando scanners...</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label class="field-label">Resolução (DPI)</label>
                            <input type="number" id="dpi" class="field-input" value="200" min="72" max="600">
                        </div>

                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" id="chk-show-ui">
                                Mostrar interface do driver
                            </label>
                        </div>

                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" id="chk-adf" checked>
                                Usar alimentador (ADF)
                            </label>
                        </div>

                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" id="chk-duplex">
                                Duplex (Frente e Verso)
                            </label>
                        </div>
                    </div>

                    <div class="card-section">
                        <button type="button" id="btn-scan" class="btn btn-primary btn-w-100 btn-icon-text" disabled>
                            <i class="fas fa-play"></i> Iniciar Digitalização
                        </button>
                    </div>
                </div>

                <!-- Document Data -->
                <div class="scan-controls-card">
                    <div class="card-section">
                        <h2 class="section-title">
                            <i class="fas fa-file-alt"></i> Dados do Documento
                        </h2>

                        <div class="form-field">
                            <label class="field-label">Título do Documento *</label>
                            <input type="text" id="titulo" class="field-input" required placeholder="Ex: Contrato 2024">
                        </div>

                        <div class="form-field">
                            <label class="field-label">Tipo de Documento</label>
                            <select id="tipo_documento_id" class="field-input">
                                <option value="">Selecione...</option>
                                <?php foreach ($tipos_documento as $t): ?>
                                    <option value="<?= htmlspecialchars($t['id']) ?>">
                                        <?= htmlspecialchars($t['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" id="chk-ocr">
                                Executar OCR (Reconhecimento de Texto)
                            </label>
                        </div>
                    </div>

                    <div class="card-section">
                        <button type="button" id="btn-save" class="btn btn-success btn-w-100 btn-icon-text" disabled>
                            <i class="fas fa-check"></i> Salvar no GED
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <!-- Right Panel: Preview -->
        <div>
            <div class="preview-card">
                <div class="preview-header">
                    <h3 class="section-title" style="margin: 0;">
                        <i class="fas fa-image"></i> Visualização
                    </h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-secondary btn-sm" id="btn-rotate-left" disabled title="Girar Esquerda">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" id="btn-rotate-right" disabled title="Girar Direita">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" id="btn-clear" disabled title="Limpar Tudo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="preview-body">
                    <div id="dwtcontrolContainer"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var DWObject;

    // Configuração Dynamsoft
    function initDynamsoft() {
        if (typeof Dynamsoft !== 'undefined' && Dynamsoft.DWT) {
            // ProductKey explícita para evitar problemas de cache/carregamento
            Dynamsoft.DWT.ProductKey = 't0200EQYAAIqF7ULlzeXyetmQqDjsghHfezbNM7OaVUgjn1UnuM8+Nxogctuj7hdPJZwiB3wZAosIajHHOZyvtawQdgjUnZded04Z4NT9nVHc18kBTnnkBHz8dOm0VRx3DJAdmNNkn+vwBVgCaS0XYDFn750hA6QF6AagW2tgC6juIiRfyuQdv785/e9Aa04Z4NT9nWWB9HFygFMeOUOBTA5qC7vdUoGwvDkZIC1AV4E4L1EpEFwBaQG6CiyXKpYMwOSMs9b7N7tqPpU=';
            
            // Ajusta o caminho dos recursos dinamicamente
            Dynamsoft.DWT.ResourcesPath = '<?= BASE_URL ?>/public/js/Resources';
            
            Dynamsoft.DWT.Containers = [
                { ContainerId: 'dwtcontrolContainer', Width: '100%', Height: '100%' }
            ];
            
            // AutoLoad e Eventos
            Dynamsoft.DWT.AutoLoad = true;
            Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', Dynamsoft_OnReady);
            
            // Força o carregamento se necessário (caso o init.js não dispare sozinho)
            if (Dynamsoft.DWT.Load) {
                Dynamsoft.DWT.Load();
            }
        } else {
            console.error('Dynamsoft lib not loaded');
            document.getElementById('source').innerHTML = '<option>Erro: Biblioteca não carregada</option>';
        }
    }

    function Dynamsoft_OnReady() {
        DWObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
        if (DWObject) {
            document.getElementById('btn-scan').disabled = false;
            
            var sourceCount = DWObject.SourceCount;
            var sourceSelect = document.getElementById('source');
            sourceSelect.innerHTML = '';
            
            if (sourceCount === 0) {
                sourceSelect.innerHTML = '<option value="">Nenhum scanner detectado</option>';
            } else {
                for (var i = 0; i < sourceCount; i++) {
                    var option = document.createElement('option');
                    option.value = i;
                    option.textContent = DWObject.GetSourceNameItems(i);
                    sourceSelect.appendChild(option);
                }
            }
        }
    }

    // Botão Digitalizar
    document.getElementById('btn-scan').addEventListener('click', function() {
        if (!DWObject) return;

        var sourceIndex = document.getElementById('source').value;
        if (sourceIndex === '') {
            alert('Selecione um scanner.');
            return;
        }

        DWObject.SelectSourceByIndex(sourceIndex);
        DWObject.OpenSource();
        
        DWObject.IfShowUI = document.getElementById('chk-show-ui').checked;
        DWObject.Resolution = parseInt(document.getElementById('dpi').value);
        DWObject.IfFeederEnabled = document.getElementById('chk-adf').checked;
        DWObject.IfDuplexEnabled = document.getElementById('chk-duplex').checked;

        DWObject.AcquireImage(
            { IfCloseSourceAfterAcquire: true },
            function() {
                document.getElementById('btn-save').disabled = false;
                document.getElementById('btn-rotate-left').disabled = false;
                document.getElementById('btn-rotate-right').disabled = false;
                document.getElementById('btn-clear').disabled = false;
            },
            function(errorCode, errorString) {
                console.error('Scan error:', errorString);
                alert('Erro na digitalização: ' + errorString);
            }
        );
    });

    // Botão Salvar
    document.getElementById('btn-save').addEventListener('click', function() {
        if (!DWObject || DWObject.HowManyImagesInBuffer === 0) {
            alert('Nenhuma imagem para salvar.');
            return;
        }

        var titulo = document.getElementById('titulo').value.trim();
        if (!titulo) {
            alert('Por favor, informe um título para o documento.');
            document.getElementById('titulo').focus();
            return;
        }

        var tipoId = document.getElementById('tipo_documento_id').value;
        var btn = this;

        // Feedback visual
        var originalBtnContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        DWObject.ClearAllHTTPFormField();
        DWObject.SetHTTPFormField('titulo', titulo);
        if (tipoId) DWObject.SetHTTPFormField('tipo_documento_id', tipoId);
        DWObject.SetHTTPFormField('captura_modo', 'scanner');
        if (document.getElementById('chk-ocr').checked) {
            DWObject.SetHTTPFormField('conteudo_ocr', '1');
        }

        var strHTTPServer = location.hostname;
        var CurrentPath = location.pathname.substring(0, location.pathname.lastIndexOf('/') + 1);
        var strActionPage = CurrentPath + 'digitalizar_processar.php';

        DWObject.HTTPUploadAllThroughPostAsPDF(
            strHTTPServer,
            strActionPage,
            'documento_digitalizado.pdf',
            function() {
                alert('Documento salvo com sucesso!');
                window.location.href = 'index.php';
            },
            function(errorCode, errorString, response) {
                alert('Erro ao salvar: ' + errorString);
                console.error(response);
                btn.disabled = false;
                btn.innerHTML = originalBtnContent;
            }
        );
    });

    // Controles de Visualização
    document.getElementById('btn-rotate-left').addEventListener('click', function() {
        if (DWObject && DWObject.HowManyImagesInBuffer > 0)
            DWObject.RotateLeft(DWObject.CurrentImageIndexInBuffer);
    });

    document.getElementById('btn-rotate-right').addEventListener('click', function() {
        if (DWObject && DWObject.HowManyImagesInBuffer > 0)
            DWObject.RotateRight(DWObject.CurrentImageIndexInBuffer);
    });

    document.getElementById('btn-clear').addEventListener('click', function() {
        if (DWObject && confirm('Tem certeza que deseja limpar todas as imagens?')) {
            DWObject.RemoveAllImages();
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-rotate-left').disabled = true;
            document.getElementById('btn-rotate-right').disabled = true;
            document.getElementById('btn-clear').disabled = true;
        }
    });

    // Inicia quando a página carregar
    window.onload = function() {
        initDynamsoft();
    };
</script>

<?php
$content = ob_get_clean();
$layout->setContent($content);
echo $layout->render();
?>
