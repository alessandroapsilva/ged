<?php
// public/documentos_separar.php (VERSÃO REFATORADA)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id === 0) { header('Location: documentos.php'); exit(); }

// ✅ CONSULTA SIMPLIFICADA: Busca direto na tabela 'documentos'
$stmt = $pdo->prepare("SELECT d.*, t.nome as tipo_nome 
                       FROM documentos d
                       LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
                       WHERE d.id = ?");
$stmt->execute([$documento_id]);
$documento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$documento) {
    die("Documento não encontrado.");
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Separar Páginas: <?php echo htmlspecialchars($documento['titulo']); ?></h1>
                </div>
                 <div class="col-sm-6">
                    <a href="documentos.php?pasta_id=<?= $documento['pasta_id'] ?>" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Voltar para a Pasta Anterior</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <form action="documentos_separar_process.php" method="POST" id="form-separar">
                        <input type="hidden" name="documento_id" value="<?php echo $documento_id; ?>">
                        <div class="card card-dark card-outline">
                            <div class="card-header"><h3 class="card-title">Informações</h3></div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">ID</dt><dd class="col-sm-8"><?php echo $documento['id']; ?></dd>
                                    <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8"><?php echo htmlspecialchars($documento['tipo_nome'] ?? 'Não definido'); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="card card-dark card-outline">
                            <div class="card-header"><h3 class="card-title">Intervalo de Páginas</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="intervalo">Digite o intervalo de páginas:</label>
                                    <input type="text" class="form-control" id="intervalo" name="intervalo" placeholder="Ex: 1,3,5-7" required>
                                    <small class="form-text text-muted">Exemplos: <strong>1,3,5</strong> | <strong>5-7</strong> | <strong>1,5-7,13</strong></small>
                                </div>
                                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-cut"></i> Separar Páginas</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-8">
                    <div class="card"><div class="card-header"><h3 class="card-title">Preview do Documento Original</h3></div><div class="card-body p-0"><iframe src="documentos_ver.php?id=<?php echo $documento_id; ?>" style="width: 100%; height: 70vh; border: none;"></iframe></div></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#form-separar').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Aguarde...', text: 'Separando página(s) do documento...', allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        $.ajax({
            url: $(this).attr('action'), type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(response) {
                if (response.sucesso) {
                    window.location.href = 'documentos.php?pasta_id=' + response.pasta_id + '&sucesso=separado';
                } else {
                    Swal.fire('Erro!', response.mensagem, 'error');
                }
            },
            error: function() {
                Swal.fire('Erro de Comunicação!', 'Não foi possível conectar ao servidor.', 'error');
            }
        });
    });
});
</script>