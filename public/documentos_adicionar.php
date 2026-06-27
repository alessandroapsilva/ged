<?php
// public/documentos_adicionar.php (VERSÃO REFATORADA)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$pasta_id = isset($_GET['pasta_id']) ? (int)$_GET['pasta_id'] : null;
// Se não houver pasta, segue padrão eDok: criação de documento apenas dentro de pastas
if (!$pasta_id) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Para criar um documento, primeiro entre em uma pasta.'];
    header('Location: documentos.php');
    exit();
}
// Valores padrão vindos de Template ou links pré-preenchidos
$prefill_titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';
$prefill_tipo = isset($_GET['tipo_documento_id']) ? (int)$_GET['tipo_documento_id'] : null;

// Busca a lista de tipos de documento para o dropdown
$tipos_stmt = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome");
$tipos_documento = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Adicionar Novo Documento</h1>
                </div>
                <div class="col-sm-6">
                    <a href="documentos.php?pasta_id=<?= (int)$pasta_id ?>" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Cancelar e Voltar</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Detalhes do Documento</h3></div>
                <form action="documentos_adicionar_process.php" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                        <input type="hidden" name="pasta_id" value="<?= (int)$pasta_id; ?>">
                        
                        <div class="form-group">
                            <label for="arquivo">Arquivo PDF</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="arquivo" name="arquivo" required accept=".pdf">
                                <label class="custom-file-label" for="arquivo">Escolher arquivo...</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="titulo">Título do Documento</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required placeholder="Digite um título para o documento" value="<?= htmlspecialchars($prefill_titulo) ?>">
                        </div>

                        <div class="form-group">
                            <label for="descricao">Descrição (Opcional)</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Digite uma breve descrição sobre o documento"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_documento_id">Tipo do Documento</label>
                            <select class="form-control" id="tipo_documento_id" name="tipo_documento_id" required>
                                <option value="">-- Selecione um Tipo --</option>
                                <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?php echo (int)$tipo['id']; ?>" <?= ($prefill_tipo && (int)$prefill_tipo === (int)$tipo['id']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($tipo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Salvar e Enviar Documento</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
// Script para mostrar o nome do arquivo no input customizado
$(function () {
  bsCustomFileInput.init();
});
</script>