<?php
require_once '../core/init.php';
require_once '../core/assinatura_digital.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtém o documento
$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT d.*, u.nome as usuario_nome, u.email as usuario_email 
        FROM documentos d 
        LEFT JOIN usuarios u ON u.id = d.usuario_id 
        WHERE d.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$documento_id]);
$documento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$documento) {
    header('Location: documentos.php');
    exit;
}

// Busca assinaturas existentes
$sql = "SELECT da.*, u.nome as usuario_nome, u.email as usuario_email 
        FROM documentos_assinaturas da 
        JOIN usuarios u ON u.id = da.usuario_id 
        WHERE da.documento_id = ? 
        ORDER BY da.data_assinatura DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$documento_id]);
$assinaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assinatura Digital - <?php echo htmlspecialchars($documento['nome']); ?></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Informações do Documento</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Nome</th>
                                    <td><?php echo htmlspecialchars($documento['nome']); ?></td>
                                </tr>
                                <tr>
                                    <th>Enviado por</th>
                                    <td><?php echo htmlspecialchars($documento['usuario_nome']); ?></td>
                                </tr>
                                <tr>
                                    <th>Data Upload</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($documento['data_upload'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($documento['assinado']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-certificate"></i> Assinado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Aguardando Assinatura
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>

                            <?php if (!$documento['assinado']): ?>
                                <div class="mt-4">
                                    <h5>Assinar Documento</h5>
                                    <form id="formAssinar" action="documentos_assinar_process.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="documento_id" value="<?php echo $documento_id; ?>">
                                        
                                        <div class="form-group">
                                            <label>Certificado Digital (A1)</label>
                                            <input type="file" name="certificado" class="form-control" accept=".pfx,.p12" required>
                                            <small class="form-text text-muted">
                                                Selecione seu certificado digital ICP-Brasil no formato PFX ou P12
                                            </small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Senha do Certificado</label>
                                            <input type="password" name="senha" class="form-control" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-signature"></i> Assinar Documento
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($assinaturas)): ?>
                                <div class="mt-4">
                                    <h5>Histórico de Assinaturas</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Data/Hora</th>
                                                    <th>Assinado por</th>
                                                    <th>Tipo</th>
                                                    <th>Detalhes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assinaturas as $assinatura): 
                                                    $detalhes = json_decode($assinatura['detalhes'], true);
                                                ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($assinatura['data_assinatura'])); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($assinatura['usuario_nome']); ?><br>
                                                        <small><?php echo htmlspecialchars($assinatura['usuario_email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($assinatura['tipo_assinatura'] == 'ICP-Brasil'): ?>
                                                            <span class="badge badge-success">ICP-Brasil</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info"><?php echo $assinatura['tipo_assinatura']; ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($detalhes): ?>
                                                            <button type="button" class="btn btn-sm btn-info" 
                                                                    data-toggle="modal" 
                                                                    data-target="#modalDetalhes<?php echo $assinatura['id']; ?>">
                                                                <i class="fas fa-info-circle"></i> Ver Detalhes
                                                            </button>
                                                            
                                                            <!-- Modal Detalhes -->
                                                            <div class="modal fade" id="modalDetalhes<?php echo $assinatura['id']; ?>" tabindex="-1" role="dialog">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Detalhes da Assinatura</h5>
                                                                            <button type="button" class="close" data-dismiss="modal">
                                                                                <span>&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <dl>
                                                                                <dt>Titular</dt>
                                                                                <dd><?php echo $detalhes['subject']['CN']; ?></dd>
                                                                                
                                                                                <dt>Emissor</dt>
                                                                                <dd><?php echo $detalhes['issuer']['CN']; ?></dd>
                                                                                
                                                                                <dt>Válido de</dt>
                                                                                <dd><?php echo date('d/m/Y H:i', strtotime($detalhes['validFrom'])); ?></dd>
                                                                                
                                                                                <dt>Válido até</dt>
                                                                                <dd><?php echo date('d/m/Y H:i', strtotime($detalhes['validTo'])); ?></dd>
                                                                            </dl>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Validação da Assinatura</h3>
                </div>
                <div class="card-body">
                    <?php
                    if ($documento['assinado']):
                        $assinatura = new AssinaturaDigital($pdo, $_SESSION['usuario']['id']);
                        $validacao = $assinatura->verificarAssinatura($documento_id);
                    ?>
                        <div class="text-center mb-4">
                            <?php if ($validacao['valido']): ?>
                                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                                <h4 class="mt-2 text-success">Assinatura Válida</h4>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger" style="font-size: 48px;"></i>
                                <h4 class="mt-2 text-danger">Assinatura Inválida</h4>
                            <?php endif; ?>
                        </div>

                        <dl>
                            <dt>Status</dt>
                            <dd><?php echo $validacao['mensagem']; ?></dd>
                            
                            <?php if ($validacao['valido'] && isset($validacao['detalhes'])): ?>
                                <dt>Certificado</dt>
                                <dd><?php echo $validacao['detalhes']['subject']['CN']; ?></dd>
                                
                                <dt>Autoridade Certificadora</dt>
                                <dd><?php echo $validacao['detalhes']['issuer']['CN']; ?></dd>
                                
                                <dt>Data da Assinatura</dt>
                                <dd><?php echo date('d/m/Y H:i', strtotime($documento['data_assinatura'])); ?></dd>
                            <?php endif; ?>
                        </dl>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-file-signature" style="font-size: 48px;"></i>
                            <p class="mt-2">Este documento ainda não foi assinado digitalmente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#formAssinar').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + response.error);
                }
            },
            error: function() {
                alert('Erro ao processar a assinatura');
            }
        });
    });
});
</script>

<?php include '../templates/footer.php'; ?>