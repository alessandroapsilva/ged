<?php
require_once __DIR__ . '/../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: tipos_listar.php'); exit(); }

try {
    $st = $pdo->prepare('SELECT * FROM tipos_documento WHERE id = ?');
    $st->execute([$id]);
    $tipo = $st->fetch(PDO::FETCH_ASSOC);
    if (!$tipo) { header('Location: tipos_listar.php'); exit(); }

    $campos = $pdo->prepare('SELECT * FROM metadado_campos WHERE tipo_documento_id = ? ORDER BY ordem ASC');
    $campos->execute([$id]);
    $lista = $campos->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { die('Erro: '.$e->getMessage()); }

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center">
    <h1>Tipo: <?= htmlspecialchars($tipo['nome']) ?></h1>
    <div>
      <a href="tipos_editar.php?id=<?= $id ?>" class="btn btn-sm btn-info"><i class="fas fa-pencil-alt mr-1"></i>Editar</a>
      <a href="tipos_listar.php" class="btn btn-sm btn-secondary">Voltar</a>
    </div>
  </div></section>
  <section class="content"><div class="container-fluid">
    <div class="row">
      <div class="col-md-8">
        <div class="card card-dark card-outline">
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Código</dt><dd class="col-sm-9"><code><?= htmlspecialchars($tipo['codigo'] ?? '') ?></code></dd>
              <dt class="col-sm-3">Restrito</dt><dd class="col-sm-9"><?= !empty($tipo['restrito']) ? 'Sim' : 'Não' ?></dd>
              <dt class="col-sm-3">Assinado</dt><dd class="col-sm-9"><?= !empty($tipo['assinado']) ? 'Sim' : 'Não' ?></dd>
              <dt class="col-sm-3">Vencimento</dt><dd class="col-sm-9"><?= (!empty($tipo['vencimento_prazo']) && !empty($tipo['vencimento_unidade'])) ? htmlspecialchars($tipo['vencimento_prazo'].' '.$tipo['vencimento_unidade']) : 'Permanente' ?></dd>
            </dl>
          </div>
        </div>
        <div class="card card-dark card-outline">
          <div class="card-header"><h3 class="card-title">Metadados</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0"><thead><tr><th>Rótulo</th><th>Identificador</th><th>Conteúdo</th><th>Largura</th><th>Obrigatório</th></tr></thead><tbody>
              <?php foreach ($lista as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c['rotulo']) ?></td>
                  <td><code><?= htmlspecialchars($c['identificador']) ?></code></td>
                  <td><?= htmlspecialchars($c['conteudo']) ?></td>
                  <td><?= (int)$c['largura'] ?></td>
                  <td><?= !empty($c['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                </tr>
              <?php endforeach; if (empty($lista)): ?>
                <tr><td colspan="5" class="text-center text-muted p-3">Sem metadados.</td></tr>
              <?php endif; ?>
            </tbody></table>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-dark card-outline">
          <div class="card-header"><h3 class="card-title">QR Code (Amostra)</h3></div>
          <div class="card-body">
            <?php
              $codigo = $tipo['codigo'] ?? '';
              $sep = $tipo['separador'] ?? '-';
              $texto = $codigo;
              foreach ($lista as $c) { $texto .= $sep . strtoupper(substr($c['conteudo'],0,1)) . str_pad((string)($c['largura'] ?? 0), 2, '0', STR_PAD_LEFT); }
            ?>
            <div class="text-center">
              <img src="qrcode_generator.php?text=<?= urlencode($texto) ?>" alt="QR" style="max-width:160px;">
              <div class="small mt-2"><code><?= htmlspecialchars($texto) ?></code></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div></section>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>

