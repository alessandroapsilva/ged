<?php
// public/digitalizar_alternativo.php
// Uma versão simplificada para quando o Dynamsoft não funciona
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="flex-between mb-4">
            <h1>📁 Upload de Documentos</h1>
            <a href="documentos.php" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
        </div>
    </section>

    <section class="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upload de Arquivos PDF</h3>
            </div>
            <div class="card-body">
                <form id="form-upload" enctype="multipart/form-data">
                    <div class="grid grid-2" style="gap: 2rem;">
                        <div>
                            <div class="form-group">
                                <label for="titulo"><strong>Título do Documento *</strong></label>
                                <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Contrato de Serviço 123">
                            </div>

                            <div class="form-group">
                                <label for="select-tipo-documento"><strong>Tipo de Documento</strong></label>
                                <select name="tipo_documento_id" id="select-tipo-documento" class="form-control">
                                    <option value="">-- Selecione um tipo --</option>
                                    <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><strong>Selecione o Arquivo PDF *</strong></label>
                                <div style="border: 2px dashed var(--primary); border-radius: var(--radius-lg); padding: 2rem; text-align: center; cursor: pointer; transition: all var(--transition-base);" id="drop-zone">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--primary); display: block; margin-bottom: 1rem;"></i>
                                    <p style="margin: 0; font-weight: 500; color: var(--gray-700);">Clique ou arraste o arquivo aqui</p>
                                    <small style="color: var(--gray-500);">Apenas PDF (máximo 50 MB)</small>
                                    <input type="file" id="arquivo" name="arquivo" accept="application/pdf" style="display: none;">
                                </div>
                                <div id="file-info" style="margin-top: 1rem; display: none;">
                                    <div class="alert" style="background: rgba(16, 185, 129, 0.1); border-color: var(--secondary); border-left: 4px solid var(--secondary); color: var(--secondary);">
                                        <strong>✓ Arquivo selecionado:</strong> <span id="file-name"></span> (<span id="file-size"></span>)
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="chk-ocr" style="margin-right: 0.5rem;">
                                    <strong>Executar OCR (Extrair Texto)</strong>
                                </label>
                                <small style="display: block; color: var(--gray-500); margin-top: 0.25rem;">ℹ️ Recomendado para documentos com bastante texto</small>
                            </div>

                            <div id="metadados-dinamicos"></div>

                            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                                <button type="submit" id="btn-upload" class="btn btn-success" style="flex: 1;">
                                    <i class="fas fa-upload mr-2"></i>Upload do Documento
                                </button>
                                <button type="button" id="btn-clear" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Limpar
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Pré-visualização</h3>
                                </div>
                                <div class="card-body" style="min-height: 400px; background: var(--gray-50);">
                                    <div id="preview" style="text-align: center; color: var(--gray-500);">
                                        <i class="fas fa-file-pdf" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <p>Nenhum arquivo selecionado</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-upload');
    const fileInput = document.getElementById('arquivo');
    const dropZone = document.getElementById('drop-zone');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const preview = document.getElementById('preview');
    const tipoSelect = document.getElementById('select-tipo-documento');

    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'var(--secondary)';
            dropZone.style.background = 'rgba(16, 185, 129, 0.05)';
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'var(--primary)';
            dropZone.style.background = 'transparent';
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect();
    });

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        const file = fileInput.files[0];
        
        if (!file) {
            fileInfo.style.display = 'none';
            preview.innerHTML = '<i class="fas fa-file-pdf" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i><p>Nenhum arquivo selecionado</p>';
            return;
        }

        if (file.type !== 'application/pdf') {
            alert('Apenas arquivos PDF são aceitos.');
            fileInput.value = '';
            return;
        }

        const maxSize = 50 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Arquivo maior que 50 MB. Por favor, selecione um arquivo menor.');
            fileInput.value = '';
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        fileInfo.style.display = 'block';

        preview.innerHTML = '<div style="padding: 2rem; text-align: center;"><i class="fas fa-check-circle" style="font-size: 3rem; color: var(--secondary); display: block; margin-bottom: 1rem;"></i><p><strong>Arquivo pronto para envio</strong></p><small>' + file.name + '</small></div>';
    }

    // Carregar metadados
    tipoSelect.addEventListener('change', function() {
        const tipoId = this.value;
        const container = document.getElementById('metadados-dinamicos');
        container.innerHTML = '';

        if (tipoId) {
            fetch(`ajax_get_metadados_fields.php?tipo_id=${tipoId}`)
                .then(r => r.json())
                .then(campos => {
                    if (campos.length > 0) {
                        let html = '<hr><h4 style="margin-top: 1.5rem;">Metadados</h4>';
                        campos.forEach(campo => {
                            html += `<div class="form-group">
                                <label for="meta-${campo.id}">${campo.rotulo}</label>
                                <input type="text" class="form-control" id="meta-${campo.id}" name="meta[${campo.id}]">
                            </div>`;
                        });
                        container.innerHTML = html;
                    }
                })
                .catch(err => console.error(err));
        }
    });

    // Submit form
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!fileInput.files || !fileInput.files[0]) {
            alert('Selecione um arquivo para enviar.');
            return;
        }

        if (!document.getElementById('titulo').value.trim()) {
            alert('Por favor, preencha o título do documento.');
            return;
        }

        const btnSubmit = document.getElementById('btn-upload');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';

        const formData = new FormData(form);
        formData.append('documento_digitalizado', fileInput.files[0], fileInput.files[0].name);
        formData.append('conteudo_ocr', '');
        formData.append('captura_modo', 'upload_manual');
        formData.append('scanner_nome', '');

        try {
            const response = await fetch('digitalizar_processar.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.sucesso) {
                sessionStorage.setItem('flash_message', JSON.stringify({
                    type: 'sucesso',
                    text: 'Documento enviado com sucesso!'
                }));
                window.location.href = 'documentos.php';
            } else {
                throw new Error(data.mensagem || 'Erro desconhecido');
            }
        } catch (error) {
            alert('Erro no upload: ' + error.message);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-upload mr-2"></i>Upload do Documento';
        }
    });

    // Clear button
    document.getElementById('btn-clear').addEventListener('click', () => {
        form.reset();
        fileInput.value = '';
        fileInfo.style.display = 'none';
        preview.innerHTML = '<i class="fas fa-file-pdf" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i><p>Nenhum arquivo selecionado</p>';
        document.getElementById('metadados-dinamicos').innerHTML = '';
    });
});
</script>
