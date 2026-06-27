<?php
// public/workflows_listar.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

try {
    $workflows = [];
    $stmt = $pdo->query("SELECT w.*, u.nome as criador_nome, 
                         (SELECT COUNT(*) FROM workflow_documentos wd WHERE wd.workflow_id = w.id) as documentos_count
                         FROM workflows w 
                         LEFT JOIN usuarios u ON w.criado_por = u.id 
                         ORDER BY w.data_criacao DESC");
    $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $workflows = [];
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Workflows</h1>
                </div>
                <div class="col-sm-6">
                    <a href="workflows_criar.php" class="btn btn-success float-right">
                        <i class="fas fa-plus"></i> Criar Workflow
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title">Fluxos de Aprovação Cadastrados</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Criado Por</th>
                                <th>Status</th>
                                <th>Docs em Fluxo</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workflows)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-project-diagram fa-3x mb-3"></i>
                                        <p>Nenhum workflow cadastrado ainda.</p>
                                        <a href="workflows_criar.php" class="btn btn-success btn-sm">Criar Primeiro Workflow</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($workflows as $w): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($w['id']) ?></td>
                                        <td><strong><?= htmlspecialchars($w['nome']) ?></strong></td>
                                        <td><?= htmlspecialchars($w['descricao'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($w['criador_nome'] ?? 'Sistema') ?></td>
                                        <td>
                                            <?php if ($w['status'] === 'ativo'): ?>
                                                <span class="badge badge-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$w['documentos_count'] ?></td>
                                        <td class="text-right">
                                            <a href="workflows_detalhes.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="workflows_editar.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

<?php require_once '../templates/footer.php'; ?>
