<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Lógica de Paginação
$resultados_por_pagina = 25;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $resultados_por_pagina;

// Lógica de Filtros (Adicionando o filtro fixo de Categoria)
$where_clauses = ["l.categoria = 'Atividade'"];
$params = [];

if (!empty($_GET['usuario_id'])) {
    $where_clauses[] = "l.usuario_id = ?";
    $params[] = (int)$_GET['usuario_id'];
}
if (!empty($_GET['descricao'])) {
    $where_clauses[] = "l.acao LIKE ?";
    $params[] = '%' . $_GET['descricao'] . '%';
}
$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Contagem total para paginação
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM logs l $where_sql");
$total_stmt->execute($params);
$total_logs = $total_stmt->fetchColumn();
$total_paginas = ($total_logs > 0) ? ceil($total_logs / $resultados_por_pagina) : 1;

// Busca dos logs da página atual
$sql = "SELECT l.*, u.nome as usuario_nome 
        FROM logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        $where_sql
        ORDER BY l.data_ocorrencia DESC 
        LIMIT $resultados_por_pagina OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid"><h1>Atividades do Sistema</h1></div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <form method="get" class="form-inline">
                        <div class="form-group mr-2">
                            <label for="descricao" class="mr-2">Descrição:</label>
                            <input type="text" name="descricao" id="descricao" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['descricao'] ?? '') ?>" placeholder="Filtrar por ação...">
                        </div>
                        <div class="form-group mr-2">
                            <label for="usuario_id" class="mr-2">Usuário:</label>
                            <select name="usuario_id" id="usuario_id" class="form-control form-control-sm">
                                <option value="">Todos os Usuários</option>
                                <?php foreach($usuarios as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= (($_GET['usuario_id'] ?? '') == $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Pesquisar</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead><tr><th>ID</th><th>Quando</th><th>Descrição</th><th>Usuário</th></tr></thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum registro de atividade encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['data_ocorrencia'])) ?></td>
                                    <td><?= htmlspecialchars($log['acao']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between">
                        <div class="text-muted">Mostrando de <?= $offset + 1 ?> a <?= $offset + count($logs) ?> de <?= $total_logs ?> registros</div>
                        <ul class="pagination pagination-sm m-0">
                            <?php $queryParams = $_GET; unset($queryParams['page']); $queryString = http_build_query($queryParams); ?>
                            <li class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=1&<?= $queryString ?>">&laquo;</a></li>
                            <li class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $pagina_atual - 1 ?>&<?= $queryString ?>">&lsaquo;</a></li>
                            <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                                <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&<?= $queryString ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $pagina_atual + 1 ?>&<?= $queryString ?>">&rsaquo;</a></li>
                            <li class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $total_paginas ?>&<?= $queryString ?>">&raquo;</a></li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php require_once '../templates/footer.php'; ?>