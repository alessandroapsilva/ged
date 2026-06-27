<?php
// public/documentos.php (VERSÃƒO COMPLETA E FUNCIONAL com NotificaÃ§Ãµes eDok)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// --- LÃ“GICA PHP COMPLETA E ESTÃVEL ---
try {
    $resultados_por_pagina = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($pagina_atual < 1) $pagina_atual = 1;

    $pasta_atual_id = isset($_GET['pasta_id']) && filter_var($_GET['pasta_id'], FILTER_VALIDATE_INT) ? (int)$_GET['pasta_id'] : null;
    $tipo_id = isset($_GET['tipo_id']) && filter_var($_GET['tipo_id'], FILTER_VALIDATE_INT) ? (int)$_GET['tipo_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

    $breadcrumbs = [['id' => null, 'nome' => 'Raiz']];
    if ($pasta_atual_id) {
        $caminho_reverso = []; $temp_pasta_id = $pasta_atual_id;
        while ($temp_pasta_id !== null) {
            $pasta_info_stmt = $pdo->prepare("SELECT id, nome, pasta_pai_id FROM pastas WHERE id = ?");
            $pasta_info_stmt->execute([$temp_pasta_id]);
            $pasta_info = $pasta_info_stmt->fetch(PDO::FETCH_ASSOC);
            if ($pasta_info) { $caminho_reverso[] = $pasta_info; $temp_pasta_id = $pasta_info['pasta_pai_id']; } else { $temp_pasta_id = null; }
        }
        $breadcrumbs = array_merge($breadcrumbs, array_reverse($caminho_reverso));
    }
    $titulo_pagina = htmlspecialchars(end($breadcrumbs)['nome']);

    $subpastas_stmt = $pdo->prepare("SELECT * FROM pastas WHERE pasta_pai_id <=> :pasta_pai_id AND apagado_em IS NULL ORDER BY nome ASC");
    $subpastas_stmt->execute([':pasta_pai_id' => $pasta_atual_id]);
    $subpastas = $subpastas_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monta os filtros dinÃ¢micos
    $filtros = ["d.pasta_id <=> :pasta_id", "d.apagado_em IS NULL"];
    $params = [':pasta_id' => $pasta_atual_id];
    if ($tipo_id) { $filtros[] = "d.tipo_documento_id = :tipo_id"; $params[':tipo_id'] = $tipo_id; }
    if ($q !== '') { $filtros[] = "d.titulo LIKE :q"; $params[':q'] = "%$q%"; }
    if ($status === 'a_vencer') {
        $filtros[] = "d.data_vencimento IS NOT NULL AND d.data_vencimento >= CURDATE() AND d.data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($status === 'vencidos') {
        $filtros[] = "d.data_vencimento IS NOT NULL AND d.data_vencimento < CURDATE()";
    } elseif ($status === 'sem_vencimento') {
        $filtros[] = "d.data_vencimento IS NULL";
    }

    $count_sql = "SELECT COUNT(*) FROM documentos d WHERE " . implode(' AND ', $filtros);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_encontrados = $count_stmt->fetchColumn();
    $total_paginas = ($total_encontrados > 0) ? ceil($total_encontrados / $resultados_por_pagina) : 1;
    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
    $offset = ($pagina_atual - 1) * $resultados_por_pagina;

    $documentos_sql = "SELECT d.*, t.nome AS tipo_nome, t.restrito 
                       FROM documentos d 
                       LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id 
                       WHERE " . implode(' AND ', $filtros) . "
                       ORDER BY d.titulo ASC
                       LIMIT :limit OFFSET :offset";
    $documentos_stmt = $pdo->prepare($documentos_sql);
    foreach ($params as $k => $v) {
        if ($k === ':pasta_id') {
            $documentos_stmt->bindValue($k, $v, PDO::PARAM_INT);
        } elseif ($k === ':tipo_id') {
            $documentos_stmt->bindValue($k, $v, PDO::PARAM_INT);
        } else {
            $documentos_stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
    }
    $documentos_stmt->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $documentos_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $documentos_stmt->execute();
    $documentos = $documentos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tipos para chips de filtro (com contagem dentro do escopo atual)
    $tipos_sql = "SELECT t.id, t.nome, COUNT(d.id) as total
                  FROM tipos_documento t
                  LEFT JOIN documentos d ON d.tipo_documento_id = t.id
                      AND d.apagado_em IS NULL
                      AND d.pasta_id <=> :pasta_id"
                  . ($q !== '' ? " AND d.titulo LIKE :q" : "") .
                 " GROUP BY t.id, t.nome
                   ORDER BY t.nome ASC";
    $tipos_stmt = $pdo->prepare($tipos_sql);
    $tipos_stmt->bindValue(':pasta_id', $pasta_atual_id, PDO::PARAM_INT);
    if ($q !== '') { $tipos_stmt->bindValue(':q', "%$q%", PDO::PARAM_STR); }
    $tipos_stmt->execute();
    $tipos = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // PreferÃªncia de visualizaÃ§Ã£o (list/grid)
    $view = (isset($_GET['view']) && $_GET['view'] === 'grid') ? 'grid' : 'list';
    $qsNoView = $_GET; unset($qsNoView['view'], $qsNoView['page']);
    $baseQS = http_build_query($qsNoView);
    $linkList = '?' . $baseQS . ($baseQS ? '&' : '') . 'view=list';
    $linkGrid = '?' . $baseQS . ($baseQS ? '&' : '') . 'view=grid';

} catch (PDOException $e) {
    die("Erro fatal de banco de dados: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">

    <?php
    // ##### ATUALIZAÃ‡ÃƒO: Inclui o novo sistema de notificaÃ§Ãµes #####
    include_once '../templates/partials/notifications.php';
    ?>

    
    <section class="content-header">
        <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1><?= $titulo_pagina ?></h1></div><div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="breadcrumb-item <?= ($index === count($breadcrumbs) - 1) ? 'active' : '' ?>">
                        <?php if ($index < count($breadcrumbs) - 1): ?><a href="documentos.php?pasta_id=<?= $crumb['id'] ?>"><?= htmlspecialchars($crumb['nome']) ?></a><?php else: ?><?= htmlspecialchars($crumb['nome']) ?><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div></div></div>
    </section>
    <section class="content"><div class="container-fluid"><div class="card card-dark card-outline">
        <div class="card-header"><div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center my-1">
                <form method="get" class="form-inline">
                    <?php if($pasta_atual_id): ?><input type="hidden" name="pasta_id" value="<?= htmlspecialchars($pasta_atual_id) ?>"><?php endif; ?>
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    <label for="limit" class="mr-2">Mostrar</label>
                    <select name="limit" id="limit" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="10" <?= $resultados_por_pagina == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $resultados_por_pagina == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $resultados_por_pagina == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $resultados_por_pagina == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <input type="text" class="form-control form-control-sm ml-2" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Filtrar por tÃ­tulo...">
                    <?php if($tipo_id): ?><input type="hidden" name="tipo_id" value="<?= (int)$tipo_id ?>"><?php endif; ?>
                </form>
                <div class="btn-group btn-group-toggle view-toggler ml-2" role="group" aria-label="Alternar visualizaÃ§Ã£o">
                    <a href="<?= htmlspecialchars($linkList) ?>" id="btn-list-view" class="btn btn-sm btn-outline-secondary <?= $view === 'list' ? 'active' : '' ?>" title="Lista"><i class="fas fa-list"></i></a>
                    <a href="<?= htmlspecialchars($linkGrid) ?>" id="btn-grid-view" class="btn btn-sm btn-outline-secondary <?= $view === 'grid' ? 'active' : '' ?>" title="Grade"><i class="fas fa-th-large"></i></a>
                </div>
            </div>
                        <div class="my-1">
                            <a href="pastas_criar.php<?= $pasta_atual_id ? ('?pasta_pai_id='.(int)$pasta_atual_id) : '' ?>" class="btn btn-sm btn-secondary"><i class="fas fa-folder-plus"></i> Criar Pasta</a>
                                <?php if ($pasta_atual_id): ?>
                                <a href="documentos_adicionar.php?pasta_id=<?= (int)$pasta_atual_id ?>" class="btn btn-sm btn-success ml-2"><i class="fas fa-plus"></i> Criar Documento</a>
                                <?php endif; ?>
                            <a href="ingest.php" class="btn btn-sm btn-outline-primary ml-2" title="Ingest (Upload em lote / Scanner)"><i class="fas fa-file-upload"></i> Ingest</a>
                        </div>
        </div>
        <div class="px-3 py-2 border-top">
            <div class="d-flex align-items-center flex-wrap">
                <strong class="mr-2">Tipos:</strong>
                <a href="?<?= htmlspecialchars($baseQS . ($baseQS? '&' : '') . 'view=' . $view) ?>" class="badge badge-pill <?= $tipo_id ? 'badge-light' : 'badge-primary' ?> mr-2 mb-1">Todos</a>
                <?php foreach ($tipos as $t): ?>
                    <a href="?<?= htmlspecialchars($baseQS . ($baseQS? '&' : '') . 'view=' . $view . '&tipo_id=' . (int)$t['id']) ?>" class="badge badge-pill <?= ($tipo_id == (int)$t['id']) ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1">
                        <?= htmlspecialchars($t['nome']) ?> <span class="ml-1 badge badge-secondary"><?= (int)$t['total'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="d-flex align-items-center flex-wrap mt-2">
                <strong class="mr-2">Vencimento:</strong>
                <?php $qsNoStatus = $_GET; unset($qsNoStatus['status'], $qsNoStatus['page']); $qsBaseNoStatus = http_build_query($qsNoStatus); ?>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'view=' . $view) ?>" class="badge badge-pill <?= $status === '' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1">Todos</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=a_vencer&view=' . $view) ?>" class="badge badge-pill <?= $status === 'a_vencer' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-hourglass-half mr-1"></i> A vencer (30d)</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=vencidos&view=' . $view) ?>" class="badge badge-pill <?= $status === 'vencidos' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Vencidos</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=sem_vencimento&view=' . $view) ?>" class="badge badge-pill <?= $status === 'sem_vencimento' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-minus-circle mr-1"></i> Sem vencimento</a>
            </div>
        </div></div>
        <div class="card-body p-0">
            <?php if ($view === 'grid'): ?>
                <div class="items-container">
                    <?php if(empty($subpastas) && empty($documentos)): ?>
                        <div class="empty-state w-100">
                            <div class="empty-state-icon"><i class="far fa-folder-open"></i></div>
                            <p>Esta pasta estÃ¡ vazia.</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach($subpastas as $pasta): ?>
                        <a class="item" href="documentos.php?pasta_id=<?= htmlspecialchars($pasta['id']); ?>&view=grid" title="Abrir pasta: <?= htmlspecialchars($pasta['nome']); ?>">
                            <div class="item-icon fas fa-folder"></div>
                            <div class="item-name"><?= htmlspecialchars($pasta['nome']); ?></div>
                            <div class="item-details">Criada em <?= date('d/m/Y H:i', strtotime($pasta['data_criacao'])); ?></div>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach($documentos as $doc): ?>
                        <div class="item" role="button" onclick="window.location.href='documentos_ver?id=<?= htmlspecialchars($doc['id']); ?>'" title="Abrir: <?= htmlspecialchars($doc['titulo']); ?>">
                            <div class="item-icon fas fa-file-alt"></div>
                            <div class="item-name"><?= htmlspecialchars($doc['titulo']); ?></div>
                            <?php if (!empty($doc['descricao'])): ?>
                                <div class="item-details text-muted small"><?= htmlspecialchars($doc['descricao']); ?></div>
                            <?php endif; ?>
                            <div class="item-details">
                                <?= htmlspecialchars($doc['tipo_nome']); ?> Â· <?= date('d/m/Y H:i', strtotime($doc['data_upload'])); ?>
                                <?php if (!empty($doc['data_vencimento'])): ?>
                                    <?php 
                                        $dataVenc = new DateTime($doc['data_vencimento']);
                                        $agora = new DateTime();
                                        $diff = $agora->diff($dataVenc);
                                        $textoVencimento = $diff->y > 0 ? "em " . $diff->y . " anos" : ($diff->m > 0 ? "em " . $diff->m . " meses" : "em " . $diff->d . " dias");
                                        if ($diff->invert) $textoVencimento = "Vencido";
                                    ?>
                                    Â· Vence: <?= date('d/m/Y', strtotime($doc['data_vencimento'])); ?> (<?= $textoVencimento ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover">
            <thead><tr><th style="width:1%"><input type="checkbox" id="check-all"></th><th>ID</th><th>Nome</th><th>Tipo</th><th>Criado(a)</th><th class="text-right">AÃ§Ãµes</th></tr></thead>
            <tbody>
                <?php if(empty($subpastas) && empty($documentos)):?><tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-folder-open mr-2"></i>Esta pasta estÃ¡ vazia.</td></tr><?php endif;?>
                <?php foreach($subpastas as $pasta):?><tr><td><input type="checkbox" class="check-item" value="p-<?= htmlspecialchars($pasta['id']);?>"></td><td><?= htmlspecialchars($pasta['id']) ?></td><td><a href="documentos.php?pasta_id=<?= htmlspecialchars($pasta['id']);?>" class="text-primary"><i class="fas fa-folder mr-2"></i><?=htmlspecialchars($pasta['nome']);?></a></td><td>Pasta</td><td><?=date('d/m/Y H:i',strtotime($pasta['data_criacao']));?></td><td class="text-right actions-cell"><a href="pastas_propriedades.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a><a href="pastas_propriedades.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-warning" title="Renomear"><i class="fas fa-pencil-alt"></i></a><a href="pastas_apagar.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Lixeira"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?>
                <?php foreach($documentos as $doc):?><?php $vparam = !empty($doc['atualizado_em']) ? strtotime($doc['atualizado_em']) : time(); ?><tr>
                    <td><input type="checkbox" class="check-item" value="d-<?= htmlspecialchars($doc['id']);?>"></td>
                    <td><?= htmlspecialchars($doc['id']) ?></td>
                    <td><a href="documentos_ver?id=<?= htmlspecialchars($doc['id']);?>&v=<?= $vparam; ?>" title="Visualizar"><i class="fas fa-file-alt mr-2 text-muted"></i><strong><?=htmlspecialchars($doc['titulo']);?></strong></a><?php if (!empty($doc['descricao'])): ?><br><small class="text-muted ml-4"><?= htmlspecialchars($doc['descricao']); ?></small><?php endif; ?></td>
                    <td><?=htmlspecialchars($doc['tipo_nome'] ?? '');?> <?= isset($doc['restrito']) && $doc['restrito'] ? '<i class="fas fa-lock text-warning ml-1" title="Restrito"></i>' : '' ?></td>
                    <td><?=date('d/m/Y H:i',strtotime($doc['data_upload']));?></td>
                    <td class="text-right actions-cell">
                        <a href="documentos_ver?id=<?= htmlspecialchars($doc['id']);?>&v=<?= $vparam; ?>" class="action-icon text-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                        <a href="documentos_ver?id=<?= htmlspecialchars($doc['id']);?>&v=<?= $vparam; ?>" target="_blank" class="action-icon text-primary" title="Ver em nova aba"><i class="fas fa-external-link-alt"></i></a>
                        <a href="documentos_ver?id=<?= htmlspecialchars($doc['id']);?>&v=<?= $vparam; ?>&download=1" class="action-icon text-success" title="Baixar"><i class="fas fa-download"></i></a>
                        <a href="#" class="action-icon text-secondary action-print" data-href="documentos_ver?id=<?= htmlspecialchars($doc['id']);?>&v=<?= $vparam; ?>" title="Imprimir"><i class="fas fa-print"></i></a>
                        <a href="documentos_compartilhar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Compartilhar por link"><i class="fas fa-share-alt"></i></a>
                        <a href="documentos_propriedades?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a>
                        <a href="documentos_separar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Separar"><i class="fas fa-cut"></i></a>
                        <a href="documentos_editar?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-warning" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                        
                        <a href="esign/index.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-success" title="Assinar Documento"><i class="fas fa-signature"></i></a>
                        <a href="documentos_apagar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Mover para Lixeira"><i class="fas fa-trash"></i></a>
                    </td>
                </tr><?php endforeach;?>
            </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($view !== 'grid'): ?>
        <div class="card-footer actions-bar-footer d-none" id="card-footer-actions"><div class="d-flex justify-content-center align-items-center"><strong class="mr-3"><i class="fas fa-check-double"></i> Com selecionados:</strong><button id="btn-combinar" class="btn btn-sm btn-success" disabled>Combinar</button><button id="btn-mover-lote" class="btn btn-sm btn-warning ml-2">Mover</button><button id="btn-apagar-lote" class="btn btn-sm btn-danger ml-2">Apagar</button></div></div>
        <?php endif; ?>
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer clearfix"><div class="d-flex justify-content-between"><div class="text-muted">Mostrando de <span class="count-badge"><?= $offset + 1 ?></span> a <span class="count-badge"><?= $offset + count($documentos) ?></span> de <span class="count-badge"><?= $total_encontrados ?></span> documentos</div><ul class="pagination pagination-sm m-0 float-right"><?php $queryParams = $_GET; unset($queryParams['page']); $queryString = http_build_query($queryParams); ?><li class="page-item <?= (int)$pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=1&<?= $queryString ?>">&laquo;</a></li><li class="page-item <?= (int)$pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= (int)$pagina_atual - 1 ?>&<?= $queryString ?>">&lsaquo;</a></li><?php for ($i = max(1, (int)$pagina_atual - 2); $i <= min($total_paginas, (int)$pagina_atual + 2); $i++): ?><li class="page-item <?= $i == (int)$pagina_atual ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&<?= $queryString ?>"><?= $i ?></a></li><?php endfor; ?><li class="page-item <?= (int)$pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= (int)$pagina_atual + 1 ?>&<?= $queryString ?>">&rsaquo;</a></li><li class="page-item <?= (int)$pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $total_paginas ?>&<?= $queryString ?>">&raquo;</a></li></ul></div></div>
        <?php endif; ?>
    </div></div></section>
    </div>
    <form id="form-combinar" action="documentos_combinar.php" method="post" target="_blank" style="display:none;"></form>

<?php require_once '../templates/footer.php'; ?>

<script>
$(function(){
  // Preferência de visualização (grid/list)
  try{
    var preferred = localStorage.getItem('doc_view');
    var current = '<?= $view ?>';
    if(preferred && preferred !== current){
      var target = preferred === 'grid' ? '<?= $linkGrid ?>' : '<?= $linkList ?>';
      window.location.replace(target);
      return;
    }
    $('#btn-grid-view').on('click', function(){ localStorage.setItem('doc_view','grid'); });
    $('#btn-list-view').on('click', function(){ localStorage.setItem('doc_view','list'); });
  }catch(e){}

  function toggleActionBar(){
    var checked = $('.check-item:checked');
    var any = checked.length>0;
    var docs = checked.filter('[value^="d-"]').length;
    if(any) $('#card-footer-actions').slideDown('fast'); else $('#card-footer-actions').slideUp('fast');
    $('#btn-combinar').prop('disabled', docs < 2);
    $('#btn-mover-lote, #btn-apagar-lote').prop('disabled', !any);
  }
  $(document).on('change', '#check-all, .check-item', function(){
    if($(this).is('#check-all')) $('.check-item').prop('checked', this.checked);
    else $('#check-all').prop('checked', $('.check-item:not(:checked)').length===0);
    toggleActionBar();
  });

  // Combinar selecionados
  $('#btn-combinar').on('click', function(){
    var form = $('#form-combinar'); form.empty();
    $('.check-item:checked[value^="d-"]').each(function(){ var id=$(this).val().replace('d-',''); form.append('<input type="hidden" name="doc_ids[]" value="'+id+'">'); });
    form.submit();
  });

  // Apagar em lote
  $('#btn-apagar-lote').on('click', function(){
    var ids = $('.check-item:checked').map(function(){ return $(this).val(); }).get();
    if(!ids.length) return;
    Swal.fire({ title:'Mover '+ids.length+' itens para a lixeira?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#6c757d', confirmButtonText:'Sim, mover!', cancelButtonText:'Cancelar' }).then(function(r){ if(r.isConfirmed){ $.post('itens_apagar_lote.php',{ ids: ids }).done(function(){ Swal.fire('Movido!','Os itens foram para a lixeira.','success').then(()=>location.reload()); }).fail(function(){ Swal.fire('Erro!','Não foi possível apagar os itens.','error'); }); } });
  });

  // Mover: direciona para página dedicada (ids na query)
  $('#btn-mover-lote').on('click', function(){
    var ids = $('.check-item:checked').map(function(){ return $(this).val(); }).get();
    if(!ids.length) return;
    window.location.href = 'itens_mover.php?ids='+encodeURIComponent(ids.join(','));
  });

  // Imprimir (abre em nova aba)
  $(document).on('click', '.action-print', function(e){ e.preventDefault(); var url=$(this).data('href'); var w=window.open(url,'_blank'); if(w){ var tryPrint=function(){ try{ w.focus(); w.print(); }catch(e){} }; w.addEventListener('load', tryPrint); setTimeout(tryPrint,1200);} });

    // Dica de scroll horizontal nas tabelas responsivas
    function updateScrollableHints(){
        $('.table-responsive').each(function(){
            var el = this;
            var $el = $(this);
            var scrollable = el.scrollWidth > el.clientWidth + 1;
            var atStart = el.scrollLeft <= 1;
            var atEnd = (el.scrollLeft + el.clientWidth) >= (el.scrollWidth - 1);
            $el.toggleClass('is-scrollable', scrollable && !atEnd);
            $el.toggleClass('scrolled-left', scrollable && !atStart);
        });
    }
    $('.table-responsive').on('scroll', updateScrollableHints);
    $(window).on('resize', updateScrollableHints);
    setTimeout(updateScrollableHints, 0);
});
</script>




