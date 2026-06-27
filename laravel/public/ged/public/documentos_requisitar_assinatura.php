<?php

// public/documentos_requisitar_assinatura.php

require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }



$documento_id = (int)($_GET['id'] ?? 0);

if ($documento_id === 0) { header('Location: documentos.php'); exit(); }



$stmt = $pdo->prepare("SELECT titulo FROM documentos WHERE id = ?");

$stmt->execute([$documento_id]);

$documento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$documento) { die("Documento não encontrado."); }



include '../templates/header.php';

include '../templates/sidebar.php';

?>



<div class="content-wrapper">

    <section class="content-header">

        <div class="container-fluid">

            <div class="row mb-2">

                <div class="col-sm-6">

                    <h1>Requisitar Assinaturas em "<?= htmlspecialchars($documento['titulo']); ?>"</h1>

                </div>

                <div class="col-sm-6 text-right">

                     <a href="documentos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar para a Pasta Anterior</a>

                </div>

            </div>

        </div>

    </section>



    <section class="content">

        <div class="container-fluid">

            <div class="row">

                <div class="col-md-4">
                    <form action="documentos_requisitar_assinatura_process.php" method="POST">
                        <input type="hidden" name="documento_id" value="<?= $documento_id; ?>">
                        <div class="card card-primary card-outline">
                            <div class="card-header"><h3 class="card-title">Destinatários</h3></div>
                            <div class="card-body">
                                <link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/select2/css/select2.min.css">
                                <link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
                                <div class="form-group">
                                    <label for="usuarios_select">Usuários do sistema</label>
                                    <select name="usuarios[]" id="usuarios_select" class="form-control" multiple data-placeholder="Selecione usuários..." style="width:100%"></select>
                                    <small class="form-text text-muted">Os usuários selecionados também recebem por e-mail e notificação.</small>
                                </div>
                                <div class="form-group">
                                    <label for="emails_externos">Emails Externos</label>
                                    <textarea name="emails" id="emails_externos" class="form-control" rows="8" placeholder="Digite os e-mails, separados por vírgula ou um por linha."></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="aceite_responsabilidade" required>
                                        <label class="custom-control-label" for="aceite_responsabilidade"><small>Estou ciente e aceito a responsabilidade legal ao requisitar a assinatura.</small></label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane mr-1"></i> Requisitar Assinaturas</button>
                            </div>
                        </div>
                    </form>

                </div>

                <div class="col-md-8">

                    <div class="card card-primary card-outline" style="height: 80vh;">

                       <div class="card-body p-0">

                           <iframe src="documentos_ver.php?id=<?= $documento_id; ?>" style="width:100%; height:100%; border:none;"></iframe>

                       </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>



<?php include '../templates/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/plugins/select2/js/select2.full.min.js"></script>
<script>
$(function(){
    var $sel = $('#usuarios_select');
    if ($sel.length){
        $sel.select2({
            theme:'bootstrap4', width:'100%', placeholder:$sel.data('placeholder')||'Selecione usuários...',
            ajax:{ url:'ajax_buscar_usuarios.php', dataType:'json', delay:200,
                   data:params=>({q:params.term||''}),
                   processResults:data=>({results:(Array.isArray(data)?data:[]).map(it=>({id:it.id,text:it.text}))}) },
            minimumInputLength:1
        });
    }
});
</script>