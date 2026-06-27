<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

require_auth();
require_permission('admin.access');

// Fetch workflows from the database
try {
    $stmt = $pdo->query("SELECT id, nome, descricao, status FROM workflows ORDER BY nome ASC");
    $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors
    die("Erro ao buscar workflows: " . $e->getMessage());
}

$page_title = "Gerenciamento de Workflows";
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gerenciamento de Workflows</h1>
                </div>
                <div class="col-sm-6">
                    <a href="admin_workflow_edit.php" class="btn btn-primary float-sm-right"><i class="fas fa-plus mr-1"></i> Criar Novo Workflow</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title">Workflows Existentes</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Nome</th>
                                <th>Descrição</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workflows)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum workflow encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($workflows as $workflow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($workflow['nome']); ?></td>
                                        <td><?= htmlspecialchars($workflow['descricao']); ?></td>
                                        <td>
                                            <?php if ($workflow['status'] == 'ativo'): ?>
                                                <span class="badge badge-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="admin_workflow_edit.php?id=<?= $workflow['id']; ?>" class="btn btn-sm btn-info" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Add activate/deactivate button later -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
require_once '../templates/footer.php';
?>
