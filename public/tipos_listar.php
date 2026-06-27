<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// --- LÓGICA DE BUSCA E PAGINAÇÃO ---
$resultados_por_pagina = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $resultados_por_pagina;
$busca = $_GET['busca'] ?? '';

$sql_base = "FROM tipos_documento td
             LEFT JOIN (
                 SELECT tipo_documento_id, COUNT(id) as doc_count 
                 FROM documentos 
                 WHERE apagado_em IS NULL
                 GROUP BY tipo_documento_id
             ) as counts ON td.id = counts.tipo_documento_id";
$where_conditions = [];
$params = [];
if (!empty($busca)) {
    $where_conditions[] = "(td.nome LIKE :busca OR td.codigo LIKE :busca)";
    $params[':busca'] = "%" . $busca . "%";
}
$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

$total_stmt = $pdo->prepare("SELECT COUNT(td.id) " . $sql_base . $where_clause);
$total_stmt->execute($params);
$total_itens = $total_stmt->fetchColumn();
$total_paginas = $total_itens > 0 ? ceil($total_itens / $resultados_por_pagina) : 1;

// CORREÇÃO: A consulta agora busca todas as colunas necessárias, incluindo 'assinado'
$data_sql = "SELECT td.*, IFNULL(counts.doc_count, 0) as total_documentos " . $sql_base . $where_clause . " ORDER BY td.nome ASC LIMIT :limit OFFSET :offset";
$data_stmt = $pdo->prepare($data_sql);
// Bind dos parâmetros com tipos corretos (LIMIT/OFFSET exigem inteiros)
foreach ($params as $k => $v) { $data_stmt->bindValue($k, $v, PDO::PARAM_STR); }
$data_stmt->bindValue(':limit', (int)$resultados_por_pagina, PDO::PARAM_INT);
$data_stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$data_stmt->execute();
$tipos = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid"><h1>Tipos de Documentos</h1></div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <form method="get" class="form-inline">
                            <label for="limit" class="mr-2">Mostrar</label>
                            <select name="limit" id="limit" class="form-control form-control-sm mr-3" onchange="this.form.submit()">
                                <option value="10" <?= $resultados_por_pagina == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $resultados_por_pagina == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $resultados_por_pagina == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $resultados_por_pagina == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                            <div class="input-group input-group-sm">
                                <input type="text" name="busca" class="form-control" placeholder="Pesquisar por nome ou código..." value="<?= htmlspecialchars($busca) ?>">
                                <div class="input-group-append"><button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button></div>
                            </div>
                        </form>
                        <div class="card-tools">
                            <a href="tipos_adicionar.php" class="btn btn-success"><i class="fas fa-plus"></i> Criar Tipo de Documento</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 15%">Código do Tipo</th>
                                <th>Nome</th>
                                <th style="width: 15%" class="text-center">Prazo de Vencimento</th>
                                <th style="width: 10%" class="text-center">Restrito</th>
                                <th style="width: 10%" class="text-center">Requer Assinatura</th>
                                <th style="width: 10%" class="text-center">Documentos</th>
                                <th style="width: 250px" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tipos)): ?>
                                <tr><td colspan="7" class="text-center py-4">Nenhum tipo de documento encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($tipos as $tipo): ?>
                                    <tr>
                                        <td><?= $tipo['id']; ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($tipo['codigo']); ?></span></td>
                                        <td><strong><?= htmlspecialchars($tipo['nome']); ?></strong></td>
                                        <td class="text-center">
                                            <?php 
                                                if (!empty($tipo['vencimento_prazo']) && !empty($tipo['vencimento_unidade'])) {
                                                    echo htmlspecialchars($tipo['vencimento_prazo'] . ' ' . $tipo['vencimento_unidade']);
                                                } else {
                                                    echo '<span class="badge bg-info">Permanente</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="text-center"><?= $tipo['restrito'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-danger">Não</span>'; ?></td>
                                        <td class="text-center"><?= $tipo['assinado'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-danger">Não</span>'; ?></td>
                                        <td class="text-center"><?= $tipo['total_documentos']; ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a class="btn btn-primary" href="tipos_ver.php?id=<?= $tipo['id']; ?>"><i class="fas fa-folder mr-1"></i> Ver</a>
                                                <a class="btn btn-info" href="tipos_editar.php?id=<?= $tipo['id']; ?>"><i class="fas fa-pencil-alt mr-1"></i> Editar</a>
                                                <a class="btn btn-danger btn-apagar-swal" href="tipos_apagar.php?id=<?= $tipo['id']; ?>"><i class="fas fa-trash mr-1"></i> Apagar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between">
                        <div class="text-muted">Mostrando de <?= $offset + 1 ?> a <?= min($offset + $resultados_por_pagina, $total_itens) ?> de <?= $total_itens ?> itens</div>
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
