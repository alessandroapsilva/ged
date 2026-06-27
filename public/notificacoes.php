<?php
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();

$uid = (int)($_SESSION['user_id'] ?? 0);
$items = [];

// Tenta buscar de workflow_notificacoes (preferencial)
try {
    $st = $pdo->prepare("SELECT id, tipo, mensagem, data_envio AS data, lida FROM workflow_notificacoes WHERE usuario_id = ? ORDER BY data_envio DESC LIMIT 200");
    $st->execute([$uid]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* tabela pode não existir */ }

// Fallback: tabela genérica 'notifications'
if (!$items) {
    try {
        $st2 = $pdo->prepare("SELECT id, 'info' AS tipo, message AS mensagem, created_at AS data, is_read AS lida FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 200");
        $st2->execute([$uid]);
        $items = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { /* ignore */ }
}

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Notificações</h1></div></section>
  <section class="content"><div class="container-fluid">
    <?php if (empty($items)): ?>
      <div class="alert alert-info">Sem notificações no momento.</div>
    <?php else: ?>
      <div class="card card-outline card-dark">
        <div class="card-body p-0">
          <table class="table table-hover table-striped mb-0">
            <thead><tr><th>Quando</th><th>Tipo</th><th>Mensagem</th><th>Lida</th></tr></thead>
            <tbody>
            <?php foreach ($items as $n): ?>
              <tr>
                <td style="white-space:nowrap;"><?= htmlspecialchars((string)($n['data'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($n['tipo'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($n['mensagem'] ?? '')) ?></td>
                <td><?= !empty($n['lida']) ? '<span class="badge badge-secondary">Sim</span>' : '<span class="badge badge-warning">Não</span>' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div></section>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>

