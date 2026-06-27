<?php
/**
 * Componente para exibir o workflow na página de detalhes do documento
 */

if (!isset($documento_id)) {
    return;
}

// Busca workflow ativo do documento
$sql = "SELECT wd.*, w.nome as workflow_nome, we.nome as etapa_nome, we.descricao as etapa_descricao,
        we.tipo_aprovacao, we.percentual_aprovacao, we.prazo_dias
        FROM workflow_documentos wd
        JOIN workflows w ON w.id = wd.workflow_id
        JOIN workflow_etapas we ON we.id = wd.etapa_atual
        WHERE wd.documento_id = ? AND wd.status = 'em_andamento'
        ORDER BY wd.data_inicio DESC
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$documento_id]);
$workflow_ativo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($workflow_ativo):
    // Busca histórico de aprovações
    $sql = "SELECT wa.*, u.nome as usuario_nome, we.nome as etapa_nome 
            FROM workflow_aprovacoes wa
            JOIN usuarios u ON u.id = wa.usuario_id
            JOIN workflow_etapas we ON we.id = wa.etapa_id
            WHERE wa.workflow_documento_id = ?
            ORDER BY wa.data_acao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$workflow_ativo['id']]);
    $aprovacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verifica se usuário atual é aprovador da etapa atual
    $sql = "SELECT COUNT(*) as total FROM workflow_aprovadores 
            WHERE etapa_id = ? AND usuario_id = ? AND tipo = 'aprovador'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$workflow_ativo['etapa_atual'], $_SESSION['usuario']['id']]);
    $eh_aprovador = $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tasks"></i> Workflow: <?php echo htmlspecialchars($workflow_ativo['workflow_nome']); ?>
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Etapa Atual: <?php echo htmlspecialchars($workflow_ativo['etapa_nome']); ?></h5>
                <p><?php echo nl2br(htmlspecialchars($workflow_ativo['etapa_descricao'])); ?></p>
                
                <?php if ($workflow_ativo['prazo_dias']): ?>
                    <p>
                        <strong>Prazo:</strong> 
                        <?php 
                        $data_limite = date('Y-m-d', strtotime("+{$workflow_ativo['prazo_dias']} days", strtotime($workflow_ativo['data_inicio'])));
                        echo date('d/m/Y', strtotime($data_limite));
                        ?>
                    </p>
                <?php endif; ?>

                <?php if ($eh_aprovador): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success" onclick="aprovarDocumento()">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="rejeitarDocumento()">
                            <i class="fas fa-times"></i> Rejeitar
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h5>Histórico de Aprovações</h5>
                <div class="timeline">
                    <?php foreach ($aprovacoes as $aprovacao): ?>
                        <div>
                            <i class="fas fa-<?php echo $aprovacao['acao'] == 'aprovado' ? 'check bg-success' : 'times bg-danger'; ?>"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($aprovacao['data_acao'])); ?>
                                </span>
                                <h3 class="timeline-header">
                                    <strong><?php echo htmlspecialchars($aprovacao['usuario_nome']); ?></strong>
                                    <?php echo $aprovacao['acao'] == 'aprovado' ? 'aprovou' : 'rejeitou'; ?>
                                    na etapa <?php echo htmlspecialchars($aprovacao['etapa_nome']); ?>
                                </h3>
                                <?php if ($aprovacao['comentario']): ?>
                                    <div class="timeline-body">
                                        <?php echo nl2br(htmlspecialchars($aprovacao['comentario'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Aprovação/Rejeição -->
<div class="modal fade" id="modalWorkflowAcao" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Ação</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formWorkflowAcao" action="documentos_workflow_acao.php" method="POST">
                <input type="hidden" name="workflow_documento_id" value="<?php echo $workflow_ativo['id']; ?>">
                <input type="hidden" name="acao" id="acao" value="">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="comentario">Comentário (opcional)</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aprovarDocumento() {
    $('#acao').val('aprovar');
    $('#modalWorkflowAcao').modal('show');
}

function rejeitarDocumento() {
    $('#acao').val('rejeitar');
    $('#modalWorkflowAcao').modal('show');
}

$('#formWorkflowAcao').on('submit', function(e) {
    e.preventDefault();
    
    $.post($(this).attr('action'), $(this).serialize(), function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Erro: ' + response.error);
        }
    });
});
</script>

<?php endif; ?>