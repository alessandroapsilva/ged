<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

require_auth();
require_permission('admin.access');

// Fetch all active users for the approvers dropdown
try {
    $stmt_users = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome ASC");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar usuários: " . $e->getMessage());
}

$workflow = ['id' => null, 'nome' => '', 'descricao' => '', 'status' => 'ativo'];
$etapas = [];
$page_title = "Criar Novo Workflow";
$is_edit_mode = false;

if (isset($_GET['id'])) {
    $is_edit_mode = true;
    $workflow_id = (int)$_GET['id'];
    $page_title = "Editar Workflow";

    $stmt_wf = $pdo->prepare("SELECT * FROM workflows WHERE id = ?");
    $stmt_wf->execute([$workflow_id]);
    $workflow = $stmt_wf->fetch(PDO::FETCH_ASSOC);

    if (!$workflow) {
        die("Workflow não encontrado.");
    }

    $stmt_etapas = $pdo->prepare("
        SELECT e.*, GROUP_CONCAT(a.usuario_id) as aprovadores
        FROM workflow_etapas e
        LEFT JOIN workflow_aprovadores a ON e.id = a.etapa_id
        WHERE e.workflow_id = ?
        GROUP BY e.id
        ORDER BY e.ordem ASC
    ");
    $stmt_etapas->execute([$workflow_id]);
    $etapas = $stmt_etapas->fetchAll(PDO::FETCH_ASSOC);
}

// --- Helper function to render a step ---
function render_etapa($index, $etapa_data, $all_users) {
    $nome = htmlspecialchars($etapa_data['nome'] ?? '');
    $descricao = htmlspecialchars($etapa_data['descricao'] ?? '');
    $tipo_aprovacao = $etapa_data['tipo_aprovacao'] ?? 'individual';
    $prazo_dias = $etapa_data['prazo_dias'] ?? '';
    $aprovadores = isset($etapa_data['aprovadores']) ? explode(',', $etapa_data['aprovadores']) : [];
?>
<div class="etapa card card-secondary" data-index="<?= $index ?>">
    <div class="card-header">
        <h3 class="card-title">Etapa</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool remove-etapa" title="Remover Etapa"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="card-body">
        <input type="hidden" name="etapas[<?= $index ?>][id]" value="<?= $etapa_data['id'] ?? '' ?>">
        <div class="form-group">
            <label for="etapa_nome_<?= $index ?>">Nome da Etapa</label>
            <input type="text" id="etapa_nome_<?= $index ?>" name="etapas[<?= $index ?>][nome]" class="form-control" value="<?= $nome ?>" required>
        </div>
        <div class="form-group">
            <label for="etapa_descricao_<?= $index ?>">Descrição da Etapa</label>
            <textarea id="etapa_descricao_<?= $index ?>" name="etapas[<?= $index ?>][descricao]" class="form-control" rows="2"><?= $descricao ?></textarea>
        </div>
        <div class="form-group">
            <label for="etapa_aprovadores_<?= $index ?>">Aprovadores</label>
            <select id="etapa_aprovadores_<?= $index ?>" name="etapas[<?= $index ?>][aprovadores][]" class="form-control" multiple required>
                <?php foreach ($all_users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= in_array($user['id'], $aprovadores) ? 'selected' : '' ?>><?= htmlspecialchars($user['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="etapa_tipo_aprovacao_<?= $index ?>">Tipo de Aprovação</label>
                    <select id="etapa_tipo_aprovacao_<?= $index ?>" name="etapas[<?= $index ?>][tipo_aprovacao]" class="form-control tipo-aprovacao">
                        <option value="individual" <?= $tipo_aprovacao == 'individual' ? 'selected' : '' ?>>Individual (um aprova)</option>
                        <option value="todos" <?= $tipo_aprovacao == 'todos' ? 'selected' : '' ?>>Todos (unânime)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="etapa_prazo_dias_<?= $index ?>">Prazo (dias)</label>
                    <input type="number" id="etapa_prazo_dias_<?= $index ?>" name="etapas[<?= $index ?>][prazo_dias]" class="form-control" value="<?= $prazo_dias ?>" min="0">
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
// --- End of helper function ---

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1><?= $page_title ?></h1></div>
                <div class="col-sm-6"><a href="admin_workflows.php" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left mr-1"></i> Voltar</a></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="admin_workflow_save.php" method="post">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Dados do Workflow</h3></div>
                    <div class="card-body">
                        <input type="hidden" name="workflow_id" value="<?= $workflow['id'] ?>">
                        <div class="form-group">
                            <label for="nome">Nome do Workflow</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($workflow['nome']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3"><?= htmlspecialchars($workflow['descricao']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="ativo" <?= $workflow['status'] == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= $workflow['status'] == 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card card-info card-outline">
                    <div class="card-header"><h3 class="card-title">Etapas do Workflow</h3></div>
                    <div class="card-body">
                        <div id="etapas-container">
                            <?php
                            if (!empty($etapas)) {
                                foreach ($etapas as $index => $etapa_data) {
                                    render_etapa($index, $etapa_data, $users);
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="add-etapa" class="btn btn-success mt-2"><i class="fas fa-plus mr-1"></i> Adicionar Etapa</button>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar Workflow</button>
                </div>
            </form>
        </div>
    </section>
</div>

<div id="etapa-template" style="display: none;">
    <?php render_etapa('__INDEX__', [], $users); ?>
</div>

<?php require_once '../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('etapas-container');
    const addButton = document.getElementById('add-etapa');
    const template = document.getElementById('etapa-template');
    let etapaIndex = <?= !empty($etapas) ? count($etapas) : 0 ?>;

    addButton.addEventListener('click', function () {
        const newEtapa = template.firstElementChild.cloneNode(true);
        newEtapa.innerHTML = newEtapa.innerHTML.replace(/__INDEX__/g, etapaIndex);
        container.appendChild(newEtapa);
        etapaIndex++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-etapa')) {
            e.preventDefault();
            const etapaDiv = e.target.closest('.etapa');
            etapaDiv.remove();
        }
    });
});
</script>