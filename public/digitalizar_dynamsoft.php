<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: ../login.php'); 
    exit(); 
}

try {
    $stmt = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC");
    $tipos_documento = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tipos_documento = [];
}

$baseUrl = defined('BASE_URL') ? BASE_URL : '/ged';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digitalização com Scanner - GED</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Dynamsoft WebTWAIN SDK -->
    <script src="<?= $baseUrl ?>/public/js/dynamsoft.webtwain.min.js"></script>
    
    <script>
        // Configuração inline para evitar problemas com pasta Resources
        window.Dynamsoft = window.Dynamsoft || {};
        window.Dynamsoft.DWT = window.Dynamsoft.DWT || {};
        window.Dynamsoft.DWT.ResourcesPath = 'https://unpkg.com/dwt@19.3.0/dist';
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            padding: 2rem;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.75rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .content {
            padding: 2rem;
        }
        .grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
        }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .card-header {
            background: #f3f4f6;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group:last-child { margin-bottom: 0; }
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9375rem;
            font-family: inherit;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        .alert-info {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .alert-success {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .alert-warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        #dwtcontrolContainer {
            width: 100%;
            height: 600px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .button-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .button-row button {
            flex: 1;
            min-width: 100px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-scanner"></i> Digitalização com Scanner</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="content">
            <div id="status-message"></div>

            <div class="grid">
                <!-- Painel Esquerdo: Controles -->
                <div>
                    <!-- Scanner -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-scanner"></i> Scanner
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Selecionar Scanner</label>
                                <select id="source-select"></select>
                            </div>
                            
                            <div class="form-group">
                                <label>Configurações</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="show-ui" checked>
                                    <span>Mostrar interface do scanner</span>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="duplex">
                                    <span>Digitalizar frente e verso</span>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="adf">
                                    <span>Usar alimentador automático (ADF)</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>DPI</label>
                                <select id="dpi-select">
                                    <option value="100">100 DPI</option>
                                    <option value="200" selected>200 DPI</option>
                                    <option value="300">300 DPI</option>
                                    <option value="600">600 DPI</option>
                                </select>
                            </div>

                            <div class="button-row">
                                <button type="button" id="btn-scan" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Digitalizar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Metadados -->
                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header">
                            <i class="fas fa-file-alt"></i> Informações do Documento
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Título *</label>
                                <input type="text" id="titulo" placeholder="Ex: Nota Fiscal 2025">
                            </div>

                            <div class="form-group">
                                <label>Tipo de Documento</label>
                                <select id="tipo-documento">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($tipos_documento as $t): ?>
                                        <option value="<?= htmlspecialchars($t['id']) ?>">
                                            <?= htmlspecialchars($t['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-group">
                                    <input type="checkbox" id="ocr-enabled">
                                    <span>Executar OCR (extrair texto)</span>
                                </label>
                            </div>

                            <button type="button" id="btn-upload" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-cloud-upload-alt"></i> Salvar Documento
                            </button>
                        </div>
                    </div>

                    <!-- Ações Extras -->
                    <div class="card" style="margin-top: 1rem;">
                        <div class="card-header">
                            <i class="fas fa-tools"></i> Ferramentas
                        </div>
                        <div class="card-body">
                            <div class="button-row">
                                <button type="button" id="btn-rotate-left" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button type="button" id="btn-rotate-right" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button type="button" id="btn-delete" class="btn btn-secondary">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel Direito: Visualizador -->
                <div>
                    <div id="dwtcontrolContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        let DWObject = null;

        // Configurar Dynamsoft
        Dynamsoft.DWT.AutoLoad = false;
        Dynamsoft.DWT.ProductKey = 't0200EQYAAIqF7ULlzeXyetmQqDjsghHfezbNM7OaVUgjn1UnuM8+Nxogctuj7hdPJZwiB3wZAosIajHHOZyvtawQdgjUnZded04Z4NT9nVHc18kBTnnkBHz8dOm0VRx3DJAdmNNkn+vwBVgCaS0XYDFn750hA6QF6AagW2tgC6juIiRfyuQdv785/e9Aa04Z4NT9nWWB9HFygFMeOUOBTA5qC7vdUoGwvDkZIC1AV4E4L1EpEFwBaQG6CiyXKpYMwOSMs9b7N7tqPpU=';

        // Inicializar ao carregar
        window.onload = function() {
            initDWT();
        };

        function initDWT() {
            showStatus('Inicializando scanner...', 'info');
            
            Dynamsoft.DWT.Load();
            
            Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', function() {
                DWObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
                
                if (DWObject) {
                    showStatus('Scanner pronto!', 'success');
                    loadSourceList();
                } else {
                    showStatus('Erro ao inicializar. Instale o serviço Dynamsoft.', 'warning');
                }
            });
        }

        function loadSourceList() {
            const sourceSelect = document.getElementById('source-select');
            sourceSelect.innerHTML = '';
            
            const count = DWObject.SourceCount;
            
            if (count === 0) {
                sourceSelect.innerHTML = '<option>Nenhum scanner encontrado</option>';
                showStatus('Nenhum scanner conectado', 'warning');
                return;
            }
            
            for (let i = 0; i < count; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.text = DWObject.GetSourceNameItems(i);
                sourceSelect.appendChild(option);
            }
            
            showStatus(`${count} scanner(s) disponível(is)`, 'success');
        }

        // Botão Digitalizar
        document.getElementById('btn-scan').addEventListener('click', function() {
            if (!DWObject) {
                showStatus('Scanner não inicializado', 'warning');
                return;
            }
            
            const sourceIndex = parseInt(document.getElementById('source-select').value);
            
            if (isNaN(sourceIndex)) {
                showStatus('Selecione um scanner', 'warning');
                return;
            }
            
            DWObject.SelectSourceByIndex(sourceIndex);
            
            const showUI = document.getElementById('show-ui').checked;
            const duplex = document.getElementById('duplex').checked;
            const adf = document.getElementById('adf').checked;
            const dpi = parseInt(document.getElementById('dpi-select').value);
            
            DWObject.IfShowUI = showUI;
            DWObject.IfDuplexEnabled = duplex;
            DWObject.IfFeederEnabled = adf;
            DWObject.Resolution = dpi;
            
            showStatus('Digitalizando...', 'info');
            
            DWObject.AcquireImage({
                IfCloseSourceAfterAcquire: true
            }, function() {
                showStatus(`${DWObject.HowManyImagesInBuffer} página(s) digitalizada(s)`, 'success');
            }, function(error) {
                showStatus('Erro ao digitalizar: ' + error.message, 'warning');
            });
        });

        // Botão Upload
        document.getElementById('btn-upload').addEventListener('click', async function() {
            if (!DWObject || DWObject.HowManyImagesInBuffer === 0) {
                showStatus('Nenhuma imagem para salvar', 'warning');
                return;
            }
            
            const titulo = document.getElementById('titulo').value.trim();
            if (!titulo) {
                showStatus('Preencha o título do documento', 'warning');
                return;
            }
            
            showStatus('Preparando PDF...', 'info');
            
            // Converter para PDF
            const fileName = 'doc_' + Date.now() + '.pdf';
            
            DWObject.IfShowProgressBar = true;
            
            DWObject.ConvertToBlob(
                Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF,
                function(result) {
                    uploadPDF(result, fileName, titulo);
                },
                function(error) {
                    showStatus('Erro ao gerar PDF: ' + error.message, 'warning');
                }
            );
        });

        async function uploadPDF(blob, fileName, titulo) {
            showStatus('Enviando PDF...', 'info');
            
            const formData = new FormData();
            formData.append('documento_digitalizado', blob, fileName);
            formData.append('titulo', titulo);
            formData.append('tipo_documento_id', document.getElementById('tipo-documento').value);
            formData.append('captura_modo', 'scanner_twain');
            formData.append('conteudo_ocr', document.getElementById('ocr-enabled').checked ? '1' : '0');
            
            try {
                const response = await fetch('digitalizar_processar.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    if (data.sucesso) {
                        showStatus('Documento salvo com sucesso!', 'success');
                        setTimeout(() => window.location.href = 'index.php', 1500);
                    } else {
                        showStatus('Erro: ' + data.mensagem, 'warning');
                    }
                } catch (e) {
                    showStatus('Erro ao processar resposta', 'warning');
                    console.error(text);
                }
            } catch (error) {
                showStatus('Erro ao enviar: ' + error.message, 'warning');
            }
        }

        // Botões de ferramentas
        document.getElementById('btn-rotate-left').addEventListener('click', function() {
            if (DWObject && DWObject.HowManyImagesInBuffer > 0) {
                DWObject.RotateLeft(DWObject.CurrentImageIndexInBuffer);
            }
        });

        document.getElementById('btn-rotate-right').addEventListener('click', function() {
            if (DWObject && DWObject.HowManyImagesInBuffer > 0) {
                DWObject.RotateRight(DWObject.CurrentImageIndexInBuffer);
            }
        });

        document.getElementById('btn-delete').addEventListener('click', function() {
            if (DWObject && DWObject.HowManyImagesInBuffer > 0) {
                DWObject.RemoveImage(DWObject.CurrentImageIndexInBuffer);
                showStatus(`${DWObject.HowManyImagesInBuffer} página(s) restante(s)`, 'info');
            }
        });

        function showStatus(message, type) {
            const statusDiv = document.getElementById('status-message');
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'warning' ? 'alert-warning' : 'alert-info';
            
            statusDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            
            if (type === 'success') {
                setTimeout(() => statusDiv.innerHTML = '', 3000);
            }
        }

        console.log('✅ Dynamsoft WebTWAIN 19.3 carregado');
    </script>
</body>
</html>
