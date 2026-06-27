<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Carrega tipos de documento
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Breadcrumb simples
$BREADCRUMB = [
    'title' => 'Upload Manual de Documentos',
    'items' => [
        ['label' => 'Início', 'url' => 'painel_produtividade'],
        ['label' => 'Documentos', 'url' => 'documentos.php'],
        ['label' => 'Upload Manual', 'active' => true]
    ]
];

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<style>
    .upload-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 2rem 1.5rem 3rem;
    }

    .upload-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .upload-title {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900, #111827);
    }

    .upload-title i {
        color: var(--primary, #2563eb);
    }

    .upload-card {
        background: var(--white, #ffffff);
        border-radius: 14px;
        border: 1px solid var(--gray-200, #e5e7eb);
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        padding: 1.75rem 1.75rem 1.5rem;
    }

    .upload-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        gap: 1.75rem;
    }

    .upload-form-section + .upload-form-section {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px dashed var(--gray-200, #e5e7eb);
    }

    .section-label {
        font-size: .85rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--gray-500, #6b7280);
        margin-bottom: .75rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-size: .9rem;
        font-weight: 600;
        color: var(--gray-700, #374151);
        margin-bottom: .35rem;
    }

    .form-group small {
        display: block;
        margin-top: .25rem;
        font-size: .78rem;
        color: var(--gray-500, #6b7280);
    }

    .form-control {
        display: block;
        width: 100%;
        padding: .55rem .7rem;
        font-size: .95rem;
        color: var(--gray-900, #111827);
        background-color: var(--white, #ffffff);
        background-clip: padding-box;
        border: 1px solid var(--gray-300, #d1d5db);
        border-radius: .45rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .form-control:focus {
        outline: 0;
        border-color: var(--primary, #2563eb);
        box-shadow: 0 0 0 1px rgba(37, 99, 235, .35);
    }

    .btn-primary-gradient {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        width: 100%;
        border-radius: .55rem;
        border: none;
        padding: .7rem 1rem;
        font-weight: 600;
        font-size: .98rem;
        color: #ffffff;
        cursor: pointer;
        background-image: linear-gradient(135deg, #2563eb, #4f46e5);
        box-shadow: 0 10px 20px rgba(37, 99, 235, .35);
        transition: transform .08s ease-out, box-shadow .08s ease-out, filter .08s ease-out;
    }

    .btn-primary-gradient:hover {
        filter: brightness(1.03);
        transform: translateY(-1px);
        box-shadow: 0 14px 30px rgba(37, 99, 235, .38);
    }

    .btn-primary-gradient:active {
        transform: translateY(0);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .45);
    }

    .btn-primary-gradient[disabled] {
        opacity: .7;
        cursor: not-allowed;
        box-shadow: none;
    }

    .status-log {
        background: radial-gradient(circle at top left, #eff6ff, #ffffff);
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, .4);
        padding: 1rem 1rem .6rem;
        max-height: 260px;
        overflow-y: auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: .78rem;
    }

    .status-log-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .35rem;
        font-size: .8rem;
        color: var(--gray-600, #4b5563);
    }

    .status-pill {
        padding: .1rem .55rem;
        border-radius: 999px;
        background: rgba(59, 130, 246, .08);
        color: #1d4ed8;
        font-weight: 600;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .log-item {
        margin-bottom: .15rem;
        white-space: pre-wrap;
    }

    @media (max-width: 960px) {
        .upload-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 600px) {
        .upload-container {
            padding: 1.5rem 1rem 2.25rem;
        }

        .upload-card {
            padding: 1.35rem 1.25rem 1.1rem;
        }
    }
</style>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>

    <div class="upload-container">
        <div class="upload-header">
            <h1 class="upload-title">
                <i class="fas fa-cloud-upload-alt"></i>
                Upload Manual de Documentos
            </h1>
            <a href="documentos.php" class="btn-action btn-secondary" style="width:auto;padding:.55rem 1.1rem;">
                <i class="fas fa-arrow-left"></i>
                Voltar para Documentos
            </a>
        </div>

        <div class="upload-card">
            <div class="upload-grid">
                <div>
                    <form id="form-upload-manual" enctype="multipart/form-data">
                        <div class="upload-form-section">
                            <div class="section-label">Dados principais</div>

                            <div class="form-group">
                                <label for="titulo">Título do Documento *</label>
                                <input type="text" id="titulo" name="titulo" class="form-control" placeholder="Ex: Matrícula 123 / Contrato 2026" required>
                                <small>Use um título que facilite a localização futura.</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_documento_id">Tipo de Documento</label>
                                    <select id="tipo_documento_id" name="tipo_documento_id" class="form-control">
                                        <option value="">-- Selecione --</option>
                                        <?php foreach ($tipos_documento as $tipo): ?>
                                            <option value="<?= (int)$tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Controla vencimento e metadados automáticos, quando configurados.</small>
                                </div>
                                <div class="form-group">
                                    <label for="arquivo">Arquivo PDF *</label>
                                    <input type="file" id="arquivo" class="form-control" accept="application/pdf" required>
                                    <small>Somente PDF · tamanho máximo 50 MB.</small>
                                </div>
                            </div>
                        </div>

                        <div class="upload-form-section">
                            <div class="section-label">Ações</div>
                            <button type="submit" id="btn-enviar" class="btn-primary-gradient">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Salvar no GED</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div>
                    <div class="status-log" id="log">
                        <div class="status-log-header">
                            <span>Console da operação</span>
                            <span class="status-pill">tempo real</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const logBox = document.getElementById('log');
    const form = document.getElementById('form-upload-manual');
    const btnEnviar = document.getElementById('btn-enviar');
    const inputArquivo = document.getElementById('arquivo');

    function addLog(message, type = 'info') {
        if (!logBox) return;
        const div = document.createElement('div');
        div.className = 'log-item';
        const icons = { info: 'ℹ️', success: '✅', error: '❌', warning: '⚠️' };
        const ts = new Date().toLocaleTimeString();
        div.textContent = `[${ts}] ${icons[type] || '•'} ${message}`;
        logBox.appendChild(div);
        logBox.scrollTop = logBox.scrollHeight;
    }

    // Garante que o botão inicie habilitado e com cursor de clique
    if (btnEnviar) {
        btnEnviar.disabled = false;
        btnEnviar.style.cursor = 'pointer';
    }

    addLog('Página de upload manual carregada.', 'info');

    if (inputArquivo) {
        inputArquivo.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                addLog('Nenhum arquivo selecionado.', 'warning');
                return;
            }
            const maxSize = 50 * 1024 * 1024;
            addLog(`Arquivo selecionado: ${file.name} (${file.size} bytes).`, 'success');
            if (file.type !== 'application/pdf') {
                addLog('Tipo de arquivo inválido. Apenas PDF é aceito.', 'error');
                alert('Apenas PDF é aceito.');
                this.value = '';
                return;
            }
            if (file.size > maxSize) {
                addLog('Arquivo maior que 50 MB.', 'error');
                alert('Arquivo maior que 50 MB.');
                this.value = '';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const titulo = document.getElementById('titulo').value.trim();
            const tipoId = document.getElementById('tipo_documento_id').value;
            const arquivo = inputArquivo.files[0];

            if (!titulo) {
                addLog('Título obrigatório não preenchido.', 'error');
                alert('Preencha o título do documento.');
                return;
            }

            if (!arquivo) {
                addLog('Nenhum arquivo selecionado.', 'error');
                alert('Selecione um arquivo PDF.');
                return;
            }

            const maxSize = 50 * 1024 * 1024;
            if (arquivo.size > maxSize) {
                addLog('Arquivo maior que 50 MB (upload bloqueado).', 'error');
                alert('Arquivo maior que 50 MB.');
                return;
            }

            const originalHTML = btnEnviar.innerHTML;
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Enviando...</span>';

            addLog('Preparando dados para envio...', 'info');

            const formData = new FormData();
            formData.append('titulo', titulo);
            formData.append('tipo_documento_id', tipoId || '');
            formData.append('documento_digitalizado', arquivo);
            formData.append('conteudo_ocr', '');
            formData.append('captura_modo', 'upload_manual');
            formData.append('scanner_nome', '');

            try {
                addLog('Enviando requisição para digitalizar_processar.php ...', 'info');

                const response = await fetch('digitalizar_processar.php', {
                    method: 'POST',
                    body: formData
                });

                addLog(`Resposta recebida. Status HTTP ${response.status}.`, 'info');
                const text = await response.text();
                addLog(`Corpo da resposta (primeiros 300 caracteres): ${text.substring(0, 300)}`, 'info');

                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    addLog('Falha ao interpretar JSON de resposta.', 'error');
                    console.error(parseError);
                    alert('Erro ao processar resposta do servidor. Consulte os logs.');
                    return;
                }

                if (data.sucesso) {
                    addLog('Documento salvo com sucesso. Redirecionando...', 'success');
                    alert('Documento salvo com sucesso!');
                    window.location.href = 'documentos.php';
                } else {
                    const msg = data.mensagem || 'Erro desconhecido ao salvar documento.';
                    addLog(`Erro de negócio: ${msg}`, 'error');
                    alert(msg);
                }
            } catch (err) {
                console.error(err);
                addLog(`Exceção durante o envio: ${err.message}`, 'error');
                alert('Erro ao enviar o arquivo: ' + err.message);
            } finally {
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = originalHTML;
            }
        });
    }
})();
</script>

<?php require_once '../templates/footer.php'; ?>
