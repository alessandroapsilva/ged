<?php
// Proteção e conexão
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Busca os tipos de documento para preencher o select (preservado)
$tipos_sql = "SELECT id, nome FROM tipos_documento ORDER BY nome ASC";
$tipos_stmt = $pdo->prepare($tipos_sql);
$tipos_stmt->execute();
$tipos_disponiveis = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pega o ID da pasta atual da URL para o formulário
$pasta_id_atual = isset($_GET['pasta_id']) ? (int)$_GET['pasta_id'] : null;

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Adicionar Novo Documento</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erro!</strong> 
                    <?php
                        switch ($_GET['erro']) {
                            case 'tipo_invalido':
                                echo 'Apenas arquivos .pdf são permitidos.';
                                break;
                            case 'mover':
                                echo 'Não foi possível salvar o arquivo no servidor. Verifique as permissões de escrita na pasta /storage/.';
                                break;
                            case 'db':
                                echo 'Houve um problema ao registrar o documento no banco de dados.';
                                break;
                            case 'arquivo':
                            default:
                                echo 'Nenhum arquivo foi enviado ou ocorreu um problema no upload.';
                                break;
                        }
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Sucesso!</strong> Documento enviado com sucesso!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            <?php endif; ?>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dados do Documento</h3>
                </div>
                <form action="documentos_salvar.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="pasta_id" value="<?php echo $pasta_id_atual; ?>">

                    <div class="card-body">
                        <div class="form-group">
                            <label for="titulo">Título ou Descrição</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ex: Contrato de Aluguel 2025" required>
                        </div>
                        <div class="form-group">
                            <label for="tipo_documento_id">Tipo de Documento</label>
                            <select class="form-control" id="tipo_documento_id" name="tipo_documento_id" required>
                                <option value="">-- Selecione um tipo --</option>
                                <?php foreach ($tipos_disponiveis as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="documento_pdf">Arquivo PDF</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="documento_pdf" name="documento_pdf" accept=".pdf" required>
                                    <label class="custom-file-label" for="documento_pdf">Escolher arquivo...</label>
                                </div>
                            </div>
                            <small>Apenas arquivos .pdf são permitidos.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Enviar Documento</button>
                        <a href="documentos_listar.php?pasta_id=<?php echo $pasta_id_atual; ?>" class="btn btn-secondary">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
    document.querySelector('.custom-file-input').addEventListener('change', function (e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : "Escolher arquivo...";
        e.target.nextElementSibling.innerText = fileName;
    });
</script>