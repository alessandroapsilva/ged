<?php
require_once '../../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id <= 0) { header('Location: ../documentos.php'); exit(); }

$stmt = $pdo->prepare("SELECT id, titulo FROM documentos WHERE id = ? AND apagado_em IS NULL");
$stmt->execute([$documento_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) { $_SESSION['flash_message']=['type'=>'erro','text'=>'Documento não encontrado.']; header('Location: ../documentos.php'); exit(); }

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center">
    <h1>Solicitar Assinatura por E-mail</h1>
    <a class="btn btn-secondary" href="../documentos_propriedades.php?id=<?= (int)$documento_id ?>"><i class="fas fa-arrow-left mr-1"></i> Voltar</a>
  </div></section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-dark card-outline">
        <div class="card-body">
          <p class="mb-3">Documento: <strong><?= htmlspecialchars($doc['titulo']) ?></strong></p>
          <form method="post" action="solicitar_enviar.php">
            <?= function_exists('csrf_input') ? csrf_input() : '' ?>
            <input type="hidden" name="documento_id" value="<?= (int)$documento_id ?>">
            <div class="form-group">
              <label>Destinatários (e-mails separados por vírgula)</label>
              <input type="text" name="emails" class="form-control" placeholder="ex.: usuario1@dominio.com, usuario2@dominio.com" required>
            </div>
            <div class="form-group">
              <label>Mensagem (opcional)</label>
              <textarea name="mensagem" class="form-control" rows="3" placeholder="Mensagem que acompanhará o convite de assinatura"></textarea>
            </div>
            <div class="text-right">
              <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Enviar Convites</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require_once '../../templates/footer.php'; ?>

