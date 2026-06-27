<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$pasta_pai_id = isset($_GET['pasta_pai_id']) ? (int)$_GET['pasta_pai_id'] : null;
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1>Criar Pastas (Lote)</h1>
      <a href="documentos.php<?= $pasta_pai_id? ('?pasta_id='.(int)$pasta_pai_id):'' ?>" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Voltar</a>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-dark card-outline">
        <div class="card-body">
          <form method="post" action="pastas_criar_lote_process.php">
            <?= function_exists('csrf_input') ? csrf_input() : '' ?>
            <input type="hidden" name="pasta_pai_id" value="<?= (int)$pasta_pai_id ?>">
            <div class="form-group">
              <label>Nomes das pastas (um por linha)</label>
              <textarea class="form-control" name="nomes" rows="8" placeholder="Ex.: Contratos
Fornecedores
2025"></textarea>
            </div>
            <div class="text-right">
              <button type="submit" class="btn btn-primary"><i class="fas fa-folder-plus mr-1"></i> Criar Pastas</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>

