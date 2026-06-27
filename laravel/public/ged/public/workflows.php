<?php
require_once '../core/init.php';
require_once '../core/workflow.php';

// Verifica se usuário está logado e tem permissão
if (!isset($_SESSION['usuario']) || !tem_permissao('gerenciar_workflows')) {
    header('Location: acesso_negado.php');
    exit;
}

$workflow = new Workflow($pdo, $_SESSION['usuario']['id']);
$workflows = $workflow->listarWorkflows();

include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gerenciar Workflows</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovoWorkflow">
                            <i class="fas fa-plus"></i> Novo Workflow
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Criado por</th>
                                <th>Data Criação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workflows as $w): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($w['nome']); ?></td>
                                <td><?php echo htmlspecialchars($w['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($w['criado_por_nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($w['data_criacao'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $w['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                        <?php echo $w['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="editarWorkflow(<?php echo $w['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="excluirWorkflow(<?php echo $w['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Workflow -->
<div class="modal fade" id="modalNovoWorkflow" tabindex="-1" role="dialog" aria-labelledby="modalNovoWorkflowLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovoWorkflowLabel">Novo Workflow</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formWorkflow" action="workflows_salvar.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nome">Nome do Workflow</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Etapas do Workflow</label>
                        <div id="etapas">
                            <!-- As etapas serão adicionadas aqui dinamicamente -->
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" onclick="adicionarEtapa()">
                            <i class="fas fa-plus"></i> Adicionar Etapa
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let contadorEtapas = 0;

function adicionarEtapa() {
    const etapasDiv = document.getElementById('etapas');
    const etapaHtml = `
        <div class="card mb-3 etapa" data-etapa="${contadorEtapas}">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    Etapa ${contadorEtapas + 1}
                    <button type="button" class="btn btn-sm btn-danger float-right" onclick="removerEtapa(${contadorEtapas})">
                        <i class="fas fa-times"></i>
                    </button>
                </h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Nome da Etapa</label>
                    <input type="text" class="form-control" name="etapas[${contadorEtapas}][nome]" required>
                </div>
                <div class="form-group">
                    <label>Descrição da Etapa</label>
                    <textarea class="form-control" name="etapas[${contadorEtapas}][descricao]" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tipo de Aprovação</label>
                        <select class="form-control" name="etapas[${contadorEtapas}][tipo_aprovacao]" onchange="togglePercentual(this)">
                            <option value="individual">Individual</option>
                            <option value="todos">Todos devem aprovar</option>
                            <option value="percentual">Percentual de aprovação</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6 campo-percentual" style="display:none;">
                        <label>Percentual Necessário (%)</label>
                        <input type="number" class="form-control" name="etapas[${contadorEtapas}][percentual]" min="1" max="100" value="100">
                    </div>
                </div>
                <div class="form-group">
                    <label>Prazo em Dias (opcional)</label>
                    <input type="number" class="form-control" name="etapas[${contadorEtapas}][prazo]" min="1">
                </div>
                <div class="form-group">
                    <label>Aprovadores</label>
                    <select class="form-control select2" name="etapas[${contadorEtapas}][aprovadores][]" multiple required>
                        <!-- Adicionar lista de usuários aqui -->
                    </select>
                </div>
            </div>
        </div>
    `;
    
    etapasDiv.insertAdjacentHTML('beforeend', etapaHtml);
    contadorEtapas++;
    
    // Inicializa o select2 para o novo select
    $(`select[name="etapas[${contadorEtapas-1}][aprovadores][]"]`).select2({
        ajax: {
            url: 'ajax_buscar_usuarios.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
}

function removerEtapa(index) {
    const etapa = document.querySelector(`.etapa[data-etapa="${index}"]`);
    etapa.remove();
}

function togglePercentual(select) {
    const etapa = select.closest('.etapa');
    const campoPercentual = etapa.querySelector('.campo-percentual');
    campoPercentual.style.display = select.value === 'percentual' ? 'block' : 'none';
}

function editarWorkflow(id) {
    // Carregar dados do workflow via AJAX e abrir modal
    $.get('workflows_get.php', { id: id }, function(workflow) {
        $('#modalNovoWorkflow').modal('show');
        // Preencher formulário com dados do workflow
        $('#nome').val(workflow.nome);
        $('#descricao').val(workflow.descricao);
        // Adicionar etapas existentes
        workflow.etapas.forEach(etapa => {
            adicionarEtapa();
            // Preencher dados da etapa
        });
    });
}

function excluirWorkflow(id) {
    if (confirm('Tem certeza que deseja excluir este workflow?')) {
        $.post('workflows_excluir.php', { id: id }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao excluir workflow');
            }
        });
    }
}

// Inicializar primeira etapa ao abrir modal
$('#modalNovoWorkflow').on('shown.bs.modal', function () {
    if (contadorEtapas === 0) {
        adicionarEtapa();
    }
});
</script>

<?php include '../templates/footer.php'; ?>