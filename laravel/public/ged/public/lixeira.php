<?php
// public/lixeira.php (VERSÃO FINAL E FUNCIONAL)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// --- LÓGICA DE BUSCA ---
// Busca por pastas na lixeira (com colunas padronizadas)
$pastas_apagadas_stmt = $pdo->query("SELECT id, nome, 'p' AS tipo_item, 'Pasta' AS tipo_nome, apagado_em FROM pastas WHERE apagado_em IS NOT NULL");
$pastas_apagadas = $pastas_apagadas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca por documentos na lixeira (com colunas padronizadas)
$docs_apagados_sql = "SELECT d.id, d.titulo AS nome, 'd' AS tipo_item, t.nome AS tipo_nome, d.apagado_em 
                      FROM documentos d 
                      LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id 
                      WHERE d.apagado_em IS NOT NULL";
$docs_apagados_stmt = $pdo->query($docs_apagados_sql);
$documentos_apagados = $docs_apagados_stmt->fetchAll(PDO::FETCH_ASSOC);

// Junta os dois arrays e ordena pela data de exclusão
$itens_lixeira = array_merge($pastas_apagadas, $documentos_apagados);
usort($itens_lixeira, function($a, $b) {
    return strtotime($b['apagado_em']) - strtotime($a['apagado_em']);
});


include '../templates/header.php';
include '../templates/sidebar.php';
?>

<style>
    #barra-selecionados-container { position: fixed; bottom: 0; left: 250px; right: 0; z-index: 1020; transform: translateY(100%); transition: transform 0.3s ease-in-out, left 0.3s ease-in-out; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); }
    #barra-selecionados-container.visible { transform: translateY(0); }
    body.sidebar-collapse #barra-selecionados-container { left: 4.6rem; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1>Lixeira</h1></div>
                <div class="col-sm-6 text-right">
                    <button id="btn-esvaziar-lixeira" class="btn btn-danger"><i class="fas fa-fire-alt mr-1"></i> Esvaziar Lixeira</button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 1%;"><input type="checkbox" id="check-all"></th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Apagado em</th>
                                <th class="text-right" style="width: 20%;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itens_lixeira)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-trash-alt fa-3x mb-3"></i><br>A lixeira está vazia.</td></tr>
                            <?php else: ?>
                                <?php foreach($itens_lixeira as $item): ?>
                                <tr>
                                    <td><input type="checkbox" class="check-item" value="<?= $item['tipo_item'] . '-' . $item['id']; ?>"></td>
                                    <td>
                                        <i class="fas <?= $item['tipo_item'] === 'p' ? 'fa-folder' : 'fa-file-alt' ?> text-secondary mr-2"></i>
                                        <?= htmlspecialchars($item['nome'] ?? 'Nome inválido'); ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['tipo_nome'] ?? 'Tipo inválido'); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($item['apagado_em'])); ?></td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a href="lixeira_restaurar.php?tipo=<?= $item['tipo_item'] ?>&id=<?= $item['id']; ?>" class="btn btn-success btn-restaurar-swal" title="Restaurar"><i class="fas fa-undo"></i></a>
                                            <a href="lixeira_apagar_permanente.php?tipo=<?= $item['tipo_item'] ?>&id=<?= $item['id']; ?>" class="btn btn-danger btn-apagar-definitivo-swal" title="Apagar Permanentemente"><i class="fas fa-times"></i></a>
                                        </div>
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

<div id="barra-selecionados-container">
    <div class="card-footer d-flex justify-content-center align-items-center bg-dark py-2">
        <strong class="mr-3 text-light">Com selecionados:</strong>
        <div class="btn-group">
            <button id="btn-restaurar-lote" class="btn btn-success"><i class="fas fa-undo mr-1"></i> Restaurar</button>
            <button id="btn-apagar-definitivo-lote" class="btn btn-danger ml-2"><i class="fas fa-times mr-1"></i> Apagar Permanentemente</button>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    // --- LÓGICA BÁSICA DA BARRA DE AÇÕES ---
    function toggleActionBar(){
        var container = $('#barra-selecionados-container');
        var selecionados = $('.check-item:checked').length;
        container.toggleClass('visible', selecionados > 0);
    }
    $('#check-all').on('click', function(){ $('.check-item').prop('checked', $(this).prop('checked')); toggleActionBar(); });
    $('.check-item').on('click', function(){ if(!$(this).prop('checked')){$('#check-all').prop('checked', false);} toggleActionBar(); });

    // --- AÇÃO: RESTAURAR ITEM INDIVIDUAL ---
    $(document).on('click', '.btn-restaurar-swal', function(e) { e.preventDefault(); const url = $(this).attr('href'); Swal.fire({ title: 'Restaurar este item?', icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Sim, restaurar!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) { window.location.href = url; } }); });

    // --- AÇÃO: APAGAR ITEM INDIVIDUAL (PERMANENTEMENTE) ---
    $(document).on('click', '.btn-apagar-definitivo-swal', function(e) { e.preventDefault(); const url = $(this).attr('href'); Swal.fire({ title: 'APAGAR PERMANENTEMENTE?', text: "Esta ação não pode ser desfeita!", icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sim, apagar para sempre!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) { window.location.href = url; } }); });

    // --- AÇÃO: RESTAURAR EM LOTE ---
    $('#btn-restaurar-lote').on('click', function() {
        var ids = $('.check-item:checked').map(function(){ return $(this).val(); }).get();
        if(ids.length === 0) { return; }
        Swal.fire({ title: `Restaurar ${ids.length} item(s)?`, icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Sim, restaurar!', cancelButtonText: 'Cancelar' }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({ url: 'lixeira_acoes_lote.php', type: 'POST', data: { action: 'restaurar', ids: ids }, success: () => location.reload() });
            }
        });
    });

    // --- AÇÃO: APAGAR EM LOTE (PERMANENTEMENTE) ---
    $('#btn-apagar-definitivo-lote, #btn-esvaziar-lixeira').on('click', function() {
        // Pega todos os itens se o botão for 'esvaziar', senão pega só os selecionados
        var ids = $(this).is('#btn-esvaziar-lixeira')
            ? $('.check-item').map(function(){ return $(this).val(); }).get()
            : $('.check-item:checked').map(function(){ return $(this).val(); }).get();
        
        if(ids.length === 0) { return Swal.fire('Atenção', 'Nenhum item selecionado.', 'info'); }

        Swal.fire({
            title: `APAGAR ${ids.length} ITEM(NS) PERMANENTEMENTE?`,
            text: "Esta ação não pode ser desfeita!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, apagar para sempre!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({ url: 'lixeira_acoes_lote.php', type: 'POST', data: { action: 'apagar_permanente', ids: ids }, success: () => location.reload() });
            }
        });
    });
});
</script>