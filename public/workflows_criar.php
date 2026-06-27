<?php
// public/workflows_criar.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Buscar usuários para aprovadores
$usuarios = [];
try {
    $stmt = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    
    <section class="content-header">
        <div class="container-fluid">
            <h1>Criar Workflow</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="workflows_salvar.php" method="post" id="form-workflow">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Informações do Workflow</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nome do Workflow *</label>
                                    <input type="text" name="nome" class="form-control" placeholder="Ex: Aprovação de Contratos" required>
                                </div>
                                <div class="form-group">
                                    <label>Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva o fluxo de aprovação..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="ativo">Ativo</option>
                                        <option value="inativo">Inativo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Etapas do Workflow</h3>
                                <button type="button" class="btn btn-sm btn-success float-right" id="btn-add-etapa">
                                    <i class="fas fa-plus"></i> Adicionar Etapa
                                </button>
                            </div>
                            <div class="card-body" id="etapas-container">
                                <p class="text-muted">Clique em "Adicionar Etapa" para criar as etapas do fluxo.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Ajuda</h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Como funciona?</strong></p>
                                <ol>
                                    <li>Crie as etapas do workflow na ordem</li>
                                    <li>Adicione aprovadores para cada etapa</li>
                                    <li>Defina o tipo de aprovação</li>
                                    <li>Configure prazos (opcional)</li>
                                </ol>
                                <hr>
                                <p><strong>Tipos de Aprovação:</strong></p>
                                <ul>
                                    <li><strong>Individual:</strong> Qualquer aprovador pode aprovar</li>
                                    <li><strong>Todos:</strong> Todos devem aprovar</li>
                                    <li><strong>Percentual:</strong> % mínimo de aprovadores</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Workflow
                        </button>
                        <a href="workflows_listar.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<script>
let etapaCount = 0;
const usuarios = <?= json_encode($usuarios) ?>;

$('#btn-add-etapa').on('click', function() {
    etapaCount++;
    const html = `
        <div class="card mb-3 etapa-card" data-etapa="${etapaCount}">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    Etapa ${etapaCount}
                    <button type="button" class="btn btn-sm btn-danger float-right btn-remove-etapa">
                        <i class="fas fa-trash"></i>
                    </button>
                </h5>
            </div>
            <div class="card-body">
                <input type="hidden" name="etapas[${etapaCount}][ordem]" value="${etapaCount}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome da Etapa *</label>
                            <input type="text" name="etapas[${etapaCount}][nome]" class="form-control" placeholder="Ex: Análise Jurídica" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Prazo (dias)</label>
                            <input type="number" name="etapas[${etapaCount}][prazo_dias]" class="form-control" placeholder="Ex: 5">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="etapas[${etapaCount}][descricao]" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Aprovação *</label>
                            <select name="etapas[${etapaCount}][tipo_aprovacao]" class="form-control tipo-aprovacao">
                                <option value="individual">Individual (qualquer um)</option>
                                <option value="todos">Todos devem aprovar</option>
                                <option value="percentual">Percentual mínimo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group percentual-group" style="display:none;">
                            <label>Percentual Mínimo (%)</label>
                            <input type="number" name="etapas[${etapaCount}][percentual_aprovacao]" class="form-control" value="50" min="1" max="100">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Aprovadores *</label>
                    <select name="etapas[${etapaCount}][aprovadores][]" class="form-control select2" multiple required>
                        ${usuarios.map(u => `<option value="${u.id}">${u.nome} (${u.email})</option>`).join('')}
                    </select>
                    <small class="text-muted">Selecione um ou mais aprovadores</small>
                </div>
            </div>
        </div>
    `;
    
    if (etapaCount === 1) {
        $('#etapas-container').html(html);
    } else {
        $('#etapas-container').append(html);
    }
    
    // Inicializa select2 para o novo select
    $(`select[name="etapas[${etapaCount}][aprovadores][]"]`).select2({
        placeholder: 'Selecione os aprovadores',
        allowClear: true
    });
});

$(document).on('change', '.tipo-aprovacao', function() {
    const card = $(this).closest('.etapa-card');
    const percentualGroup = card.find('.percentual-group');
    if ($(this).val() === 'percentual') {
        percentualGroup.show();
    } else {
        percentualGroup.hide();
    }
});

$(document).on('click', '.btn-remove-etapa', function() {
    $(this).closest('.etapa-card').remove();
    // Renumera as etapas
    $('.etapa-card').each(function(index) {
        $(this).find('.card-title').html(`
            Etapa ${index + 1}
            <button type="button" class="btn btn-sm btn-danger float-right btn-remove-etapa">
                <i class="fas fa-trash"></i>
            </button>
        `);
    });
});

$('#form-workflow').on('submit', function(e) {
    if ($('.etapa-card').length === 0) {
        e.preventDefault();
        Swal.fire('Atenção', 'Adicione pelo menos uma etapa ao workflow.', 'warning');
    }
});
</script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
