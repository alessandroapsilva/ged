<?php
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();

$title = 'Relatório de Assinaturas';
$rows = [];
$err = null;

try {
    // Verifica se existe tabela 'assinaturas' antes de consultar
    $has = false;
    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'assinaturas'");
        $has = (bool)($chk && $chk->fetchColumn());
    } catch (Throwable $e) { $has = false; }

    if ($has) {
        $st = $pdo->query("SELECT id, documento_id, usuario_id, tipo, status, created_at FROM assinaturas ORDER BY created_at DESC LIMIT 100");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    $err = $e->getMessage();
}

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1><?= htmlspecialchars($title) ?></h1></div></section>
  <section class="content"><div class="container-fluid">
    <?php if ($err): ?>
      <div class="alert alert-danger">Erro ao carregar: <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if (!empty($rows)): ?>
      <div class="card card-outline card-dark">
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th><th>Documento</th><th>Usuário</th><th>Tipo</th><th>Status</th><th>Criado em</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><a href="documentos_propriedades.php?id=<?= (int)$r['documento_id'] ?>">#<?= (int)$r['documento_id'] ?></a></td>
                  <td>#<?= (int)$r['usuario_id'] ?></td>
                  <td><?= htmlspecialchars((string)$r['tipo']) ?></td>
                  <td><span class="badge badge-<?= ($r['status'] ?? '') === 'aprovado' ? 'success' : 'secondary' ?>"><?= htmlspecialchars((string)$r['status']) ?></span></td>
                  <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-info mb-0">
        Nenhum dado de assinaturas encontrado. Importe as migrações de assinaturas (sql/assinaturas*.sql) e gere eventos de assinatura para visualizar aqui.
      </div>
    <?php endif; ?>
  </div></section>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>

