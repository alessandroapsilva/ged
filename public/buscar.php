<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$termo_busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

function highlight_term(string $text, string $term): string {
    if ($term === '') return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $pattern = '/' . preg_quote($term, '/') . '/i';
    return preg_replace($pattern, '<mark class="doc-highlight">$0</mark>', $escaped);
}

function build_snippet(string $text, string $term, int $radius = 90): ?string {
    if ($term === '' || $text === '') return null;
    $tLower = mb_strtolower($text, 'UTF-8');
    $qLower = mb_strtolower($term, 'UTF-8');
    $pos = mb_strpos($tLower, $qLower, 0, 'UTF-8');
    if ($pos === false) { return null; }
    $start = max(0, $pos - $radius);
    $len = min(mb_strlen($text, 'UTF-8') - $start, 2*$radius + mb_strlen($term, 'UTF-8'));
    $slice = mb_substr($text, $start, $len, 'UTF-8');
    $prefix = $start > 0 ? '… ' : '';
    $suffix = ($start + $len) < mb_strlen($text, 'UTF-8') ? ' …' : '';
    // Escape e destacar
    $escaped = htmlspecialchars($slice);
    $pattern = '/' . preg_quote($term, '/') . '/i';
    $highlighted = preg_replace($pattern, '<mark>$0</mark>', $escaped);
    return $prefix . $highlighted . $suffix;
}

if ($termo_busca === '') {
    header('Location: documentos.php');
    exit();
}

$termo_like = '%' . $termo_busca . '%';
$documentos = [];
$pastas = [];
$conteudo = [];

try {
    // Busca por PASTAS que correspondem ao termo
    $sql_pastas = "SELECT * FROM pastas WHERE nome LIKE ? AND apagado_em IS NULL ORDER BY nome ASC";
    $stmt_pastas = $pdo->prepare($sql_pastas);
    $stmt_pastas->execute([$termo_like]);
    $pastas = $stmt_pastas->fetchAll(PDO::FETCH_ASSOC);

    // Busca por DOCUMENTOS (título, descrição, tipo)
    $sql_docs = "SELECT d.*, t.nome AS tipo_nome, t.restrito 
                 FROM documentos d
                 LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
                 WHERE (d.titulo LIKE :termo1 OR d.descricao LIKE :termo2 OR t.nome LIKE :termo3) AND d.apagado_em IS NULL";
    if ($filtro_tipo !== '') { $sql_docs .= " AND t.nome = :tipo"; }
    $sql_docs .= " ORDER BY d.titulo ASC";
    $stmt_docs = $pdo->prepare($sql_docs);
    $paramsDocs = [':termo1' => $termo_like, ':termo2' => $termo_like, ':termo3' => $termo_like];
    if ($filtro_tipo !== '') { $paramsDocs[':tipo'] = $filtro_tipo; }
    $stmt_docs->execute($paramsDocs);
    $documentos = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

    // Busca por CONTEÚDO (full-text se disponível)
    try {
    $sql_idx = "SELECT d.*, t.nome AS tipo_nome, MATCH(di.texto_completo) AGAINST(:termo IN BOOLEAN MODE) AS score
                    FROM documentos d
                    JOIN documentos_indice di ON di.documento_id = d.id
                    LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
            WHERE d.apagado_em IS NULL AND MATCH(di.texto_completo) AGAINST(:termo IN BOOLEAN MODE)";
    if ($filtro_tipo !== '') { $sql_idx .= " AND t.nome = :tipo"; }
    $sql_idx .= " ORDER BY score DESC LIMIT 100";
        $stmt_idx = $pdo->prepare($sql_idx);
    $paramsIdx = [':termo' => $termo_busca];
    if ($filtro_tipo !== '') { $paramsIdx[':tipo'] = $filtro_tipo; }
    $stmt_idx->execute($paramsIdx);
        $conteudo = $stmt_idx->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Fallback para LIKE se FULLTEXT não existir
    $sql_idx2 = "SELECT d.*, t.nome AS tipo_nome
                     FROM documentos d
                     LEFT JOIN documentos_indice di ON di.documento_id = d.id
                     LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
             WHERE d.apagado_em IS NULL AND di.texto_completo LIKE :like";
    if ($filtro_tipo !== '') { $sql_idx2 .= " AND t.nome = :tipo"; }
    $sql_idx2 .= " ORDER BY d.titulo ASC LIMIT 100";
        $stmt_idx2 = $pdo->prepare($sql_idx2);
    $paramsIdx2 = [':like' => $termo_like];
    if ($filtro_tipo !== '') { $paramsIdx2[':tipo'] = $filtro_tipo; }
    $stmt_idx2->execute($paramsIdx2);
        $conteudo = $stmt_idx2->fetchAll(PDO::FETCH_ASSOC);
    }

    // Unificar resultados de documentos por ID para não duplicar (prioriza score do índice)
    $porId = [];
    foreach ($documentos as $d) { $porId[$d['id']] = $d; }
    foreach ($conteudo as $c) { $porId[$c['id']] = $c; }
    $documentos = array_values($porId);

    // Facetas por tipo com base na lista de documentos final
    $facetas_tipo = [];
    foreach ($documentos as $d) {
        $key = $d['tipo_nome'] ?? 'N/A';
        $facetas_tipo[$key] = ($facetas_tipo[$key] ?? 0) + 1;
    }

    // Buscar snippets de conteúdo (apenas para primeiros 50 documentos)
    $snippets = [];
    if (!empty($documentos)) {
        $ids = array_slice(array_column($documentos, 'id'), 0, 50);
        $in = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmtSn = $pdo->prepare("SELECT documento_id, texto_completo FROM documentos_indice WHERE documento_id IN ($in)");
            $stmtSn->execute($ids);
            $rows = $stmtSn->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) { $map[(int)$r['documento_id']] = $r['texto_completo']; }
            foreach ($ids as $id) {
                $txt = $map[$id] ?? '';
                $snip = build_snippet($txt, $termo_busca, 90);
                if ($snip) { $snippets[$id] = $snip; }
            }
        } catch (Throwable $e) { /* ignora */ }
    }

} catch (PDOException $e) {
    die("Erro na busca: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-6">
                    <div class="doc-hero-title">
                        <span class="eyebrow">Busca unificada</span>
                        <h1>Resultados</h1>
                        <p class="subtitle">Conteúdo, metadados e pastas com destaque do termo pesquisado.</p>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6">
                    <div class="doc-hero-metrics">
                        <div class="metric">
                            <div class="metric-label">Termo</div>
                            <div class="metric-value">"<?= htmlspecialchars($termo_busca); ?>"</div>
                            <div class="metric-sub">Consulta atual</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Resultados</div>
                            <div class="metric-value"><?= (count($documentos) + count($pastas)); ?></div>
                            <div class="metric-sub">Itens encontrados</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Facetas</div>
                            <div class="metric-value"><?= !empty($facetas_tipo) ? count($facetas_tipo) : 0; ?></div>
                            <div class="metric-sub">Tipos disponíveis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <h3 class="card-title mb-0"><?= (count($documentos) + count($pastas)); ?> resultado(s) encontrado(s)</h3>
                                            <div>
                                                <?php if (!empty($facetas_tipo)): ?>
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Tipos">
                                                        <a class="btn btn-outline-secondary <?= $filtro_tipo===''?'active':'' ?>" href="buscar.php?q=<?= urlencode($termo_busca) ?>">Todos</a>
                                                        <?php foreach ($facetas_tipo as $nome => $qtd): ?>
                                                            <a class="btn btn-outline-secondary <?= ($filtro_tipo===$nome)?'active':'' ?>" href="buscar.php?q=<?= urlencode($termo_busca) ?>&tipo=<?= urlencode($nome) ?>"><?= htmlspecialchars($nome) ?> (<?= $qtd ?>)</a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover doc-table">
                        <thead>
                            <tr>
                                <th style="width:1%"><input type="checkbox" id="check-all"></th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pastas) && empty($documentos)): ?>
                                <tr><td colspan="5" class="text-center py-4">Nenhum item encontrado.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pastas as $pasta): ?>
                                <tr>
                                    <td><input type="checkbox" class="check-item" value="p-<?= htmlspecialchars($pasta['id']);?>"></td>
                                    <td><a href="documentos.php?pasta_id=<?= $pasta['id']; ?>" class="text-primary"><i class="fas fa-folder mr-2"></i><?= htmlspecialchars($pasta['nome']); ?></a></td>
                                    <td><span class="badge badge-secondary">Pasta</span></td>
                                    <td><?= date('d/m/Y', strtotime($pasta['data_criacao'])); ?></td>
                                    <td class="text-right actions-cell">
                                        <a href="pastas_propriedades.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a>
                                        <a href="#" class="action-icon text-warning" data-toggle="modal" data-target="#modal-renomear-pasta" data-pasta-id="<?= htmlspecialchars($pasta['id']);?>" data-pasta-nome="<?=htmlspecialchars($pasta['nome']);?>" title="Renomear"><i class="fas fa-pencil-alt"></i></a>
                                        <a href="pastas_apagar.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Lixeira"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php foreach ($documentos as $doc): ?>
                                <tr>
                                    <td><input type="checkbox" class="check-item" value="d-<?= htmlspecialchars($doc['id']);?>"></td>
                                    <td>
                                        <a href="documentos_ver.php?id=<?= $doc['id']; ?>" data-toggle="modal" data-target="#modal-visualizar" data-doc-title="<?= htmlspecialchars($doc['titulo']); ?>">
                                            <i class="fas fa-file-alt mr-2 text-muted"></i><strong><?= highlight_term($doc['titulo'], $termo_busca); ?></strong>
                                        </a>
                                        <?php if (!empty($doc['descricao'])): ?>
                                            <br><small class="text-muted ml-4"><?= highlight_term($doc['descricao'], $termo_busca); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($snippets[$doc['id']] ?? '')): ?>
                                            <div class="mt-1 ml-4 small text-muted"><?= $snippets[$doc['id']]; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($doc['tipo_nome']); ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($doc['data_upload'])); ?></td>
                                    <td class="text-right actions-cell">
                                        <a href="documentos_ver.php?id=<?= htmlspecialchars($doc['id']);?>" data-toggle="modal" data-target="#modal-visualizar" data-doc-title="<?=htmlspecialchars($doc['titulo']);?>" class="action-icon text-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                                        <a href="compartilhar_documento.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Compartilhar"><i class="fas fa-share-alt"></i></a>
                                        <a href="documentos_propriedades.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a>
                                        <a href="documentos_editar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-warning" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                        <a href="documentos_apagar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Mover para Lixeira"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer actions-bar-footer" id="card-footer-actions"><div class="d-flex justify-content-center align-items-center"><strong class="mr-3"><i class="fas fa-check-double"></i> Com selecionados:</strong><button id="btn-combinar" class="btn btn-sm btn-success" disabled>Combinar</button><button id="btn-mover-lote" class="btn btn-sm btn-warning ml-2">Mover</button><button id="btn-apagar-lote" class="btn btn-sm btn-danger ml-2">Apagar</button></div></div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modal-renomear-pasta" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form action="pastas_renomear.php" method="post"><div class="modal-header"><h5 class="modal-title">Renomear Pasta</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" name="id" id="renomear-pasta-id"><div class="form-group"><label>Novo nome</label><input type="text" name="nome" id="novo_nome_pasta" class="form-control" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div></form></div></div></div>
<div class="modal fade" id="modal-visualizar" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h4 class="modal-title" id="modal-doc-title">Visualizando</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body p-0"><iframe id="pdf-viewer" src="" style="width:100%; height:80vh;" frameborder="0"></iframe></div></div></div></div>
<div class="modal fade" id="modal-mover" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Mover Itens</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><p>Selecione a pasta de destino:</p><div id="arvore-pastas-container" style="height: 250px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px;"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-confirmar-movimentacao" disabled>Mover</button></div></div></div></div>
<form id="form-combinar" action="documentos_combinar.php" method="post" target="_blank" style="display:none;"></form>


<?php require_once '../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Copiando o mesmo script do documentos.php para garantir a funcionalidade
    let itensParaMover = [];
    let pastaDestinoId = null;

    function toggleActionBar() {
        const checkedItems = $('.check-item:checked');
        const anyChecked = checkedItems.length > 0;
        const docsSelecionados = checkedItems.filter('[value^="d-"]').length;
        if (anyChecked) {
            $('#card-footer-actions').slideDown('fast');
        } else {
            $('#card-footer-actions').slideUp('fast');
        }
        $('#btn-combinar').prop('disabled', docsSelecionados < 2);
        $('#btn-mover-lote, #btn-apagar-lote').prop('disabled', !anyChecked);
    }

    $(document).on('change', '#check-all, .check-item', function() {
        if ($(this).is('#check-all')) {
            $('.check-item').prop('checked', this.checked);
        } else {
            $('#check-all').prop('checked', $('.check-item:not(:checked)').length === 0);
        }
        toggleActionBar();
    });

    $('#btn-combinar').on('click', function() {
        const form = $('#form-combinar');
        form.empty();
        $('.check-item:checked[value^="d-"]').each(function() {
            const id = $(this).val().replace('d-', '');
            form.append(`<input type="hidden" name="doc_ids[]" value="${id}">`);
        });
        form.submit();
    });

    $(document).on('click', '.btn-apagar-swal', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        Swal.fire({ title: 'Mover para a lixeira?', text: "A ação poderá ser desfeita.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Sim, mover!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) window.location.href = url; });
    });
    
    $('#btn-apagar-lote').on('click', function() {
        const ids = $('.check-item:checked').map((_, el) => $(el).val()).get();
        if (ids.length === 0) return;
        Swal.fire({ title: `Mover ${ids.length} itens para a lixeira?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Sim, mover!', cancelButtonText: 'Cancelar' }).then((result) => {
            if (result.isConfirmed) {
                $.post('itens_apagar_lote.php', { ids: ids })
                 .done(() => Swal.fire('Movido!', 'Os itens foram para a lixeira.', 'success').then(() => location.reload()))
                 .fail(() => Swal.fire('Erro!', 'Não foi possível apagar os itens.', 'error'));
            }
        });
    });

    function desenharArvore(nodes, container, nivel = 0) {
        const ul = $('<ul>').addClass('list-unstyled').css('padding-left', nivel * 20);
        nodes.forEach(node => {
            const li = $('<li>');
            const link = $('<a>').attr('href', '#').addClass('link-pasta-destino').data('pasta-id', node.id).html(`<i class="fas fa-folder text-primary mr-2"></i> ${node.nome}`);
            li.append(link);
            if (node.subpastas && node.subpastas.length > 0) desenharArvore(node.subpastas, li, nivel + 1);
            ul.append(li);
        });
        container.append(ul);
    }

    $('#btn-mover-lote').on('click', function() {
        itensParaMover = $('.check-item:checked').map((_, el) => $(el).val()).get();
        if (itensParaMover.length === 0) return;
        const container = $('#arvore-pastas-container');
        container.html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>');
        $('#modal-mover').modal('show');
        pastaDestinoId = null;
        $('#btn-confirmar-movimentacao').prop('disabled', true);
        $.getJSON('pastas_arvore.php')
         .done((arvore) => {
            container.empty();
            const raizLink = $('<ul>').addClass('list-unstyled').append($('<li>').append($('<a>').attr('href', '#').data('pasta-id', '').addClass('link-pasta-destino').html('<i class="fas fa-hdd text-secondary mr-2"></i> Raiz')));
            container.append(raizLink);
            desenharArvore(arvore, container);
         })
         .fail(() => container.html('<p class="text-danger">Erro ao carregar pastas.</p>'));
    });

    $(document).on('click', '.link-pasta-destino', function(e) {
        e.preventDefault();
        $('.link-pasta-destino').css('font-weight', 'normal');
        $(this).css('font-weight', 'bold');
        pastaDestinoId = $(this).data('pasta-id') === '' ? null : $(this).data('pasta-id');
        $('#btn-confirmar-movimentacao').prop('disabled', false);
    });

    $('#btn-confirmar-movimentacao').on('click', function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Movendo...');
        $.post('itens_mover.php', { itens: itensParaMover, destino_id: pastaDestinoId })
         .done(() => {
            $('#modal-mover').modal('hide');
            Swal.fire('Sucesso!', 'Itens movidos.', 'success').then(() => location.reload());
         })
         .fail(() => {
            Swal.fire('Erro!', 'Não foi possível mover os itens.', 'error');
            $(this).prop('disabled', false).html('Mover');
        });
    });
    
    $('#modal-renomear-pasta').on('show.bs.modal', function(e) {
        $('#renomear-pasta-id').val($(e.relatedTarget).data('pasta-id'));
        $('#novo_nome_pasta').val($(e.relatedTarget).data('pasta-nome'));
    });

    $('#modal-visualizar').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var src = button.attr('href');
        var title = button.data('doc-title');
        var modal = $(this);
        modal.find('.modal-title').text('Visualizando: ' + title);
        modal.find('#pdf-viewer').attr('src', src);
    });
    
    $('#modal-visualizar').on('hidden.bs.modal', function() {
        $(this).find('#pdf-viewer').attr('src', '');
    });
});
</script>