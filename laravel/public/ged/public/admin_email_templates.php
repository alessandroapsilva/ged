<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
require_auth();
require_permission('email.templates.manage');

// Lista templates (tratamento caso a tabela não exista)
try {
  $stmt = $pdo->query("SELECT id, slug, nome, assunto, ativo, updated_at FROM email_templates ORDER BY nome");
  $templates = $stmt->fetchAll();
} catch (PDOException $e) {
  // Se a tabela não existir, evitamos o fatal error e mostramos uma mensagem amigável.
  error_log("admin_email_templates.php - erro ao buscar templates: " . $e->getMessage());
  $templates = [];
  $templates_error = $e->getMessage();
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Templates de E-mail</h1></div>
        <div class="col-sm-6 text-right">
          <a href="admin_email_layout.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-columns mr-1"></i>Layout Cabeçalho/Rodapé</a>
          <a href="admin_email_template_edit.php" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Novo Template</a>
        </div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
        <div class="card card-dark card-outline">
        <div class="card-body table-responsive p-0">
          <?php if (!empty($templates_error)): ?>
            <div class="m-3">
              <div class="alert alert-warning" role="alert">
                <strong>Atenção:</strong> a tabela <code>email_templates</code> não foi encontrada ou há um erro ao ler templates.
                <div class="mt-2">Erro: <code><?= htmlspecialchars($templates_error); ?></code></div>
                <div class="mt-2">Solução: crie a tabela <code>email_templates</code> no banco ou execute a migração correspondente. (Verifique o arquivo <code>sql/</code> ou o dump do sistema.)</div>
              </div>
            </div>
          <?php endif; ?>
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Assunto</th>
                <th>Status</th>
                <th>Atualizado</th>
                <th style="width:260px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($templates as $t): ?>
                <tr>
                  <td><?= htmlspecialchars($t['nome']) ?></td>
                  <td><code><?= htmlspecialchars($t['slug']) ?></code></td>
                  <td><?= htmlspecialchars($t['assunto']) ?></td>
                  <td><?= $t['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-secondary">Inativo</span>' ?></td>
                  <td><?= htmlspecialchars($t['updated_at']) ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="admin_email_template_preview.php?id=<?= (int)$t['id'] ?>" title="Pré-visualizar"><i class="fas fa-eye"></i></a>
                    <a class="btn btn-sm btn-outline-primary" href="admin_email_template_edit.php?id=<?= (int)$t['id'] ?>"><i class="fas fa-edit"></i></a>
                    <a class="btn btn-sm btn-outline-info" href="admin_email_template_test.php?id=<?= (int)$t['id'] ?>"><i class="fas fa-paper-plane"></i></a>
                    <form action="admin_email_template_delete.php" method="post" style="display:inline;" onsubmit="return confirm('Desativar este template?');">
                      <?php if (function_exists('csrf_input')) echo csrf_input(); ?>
                      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($templates)): ?>
                <tr><td colspan="6" class="text-center text-muted p-4">Nenhum template cadastrado ainda.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="alert alert-secondary">
        <strong>Dica:</strong> Use placeholders como <code>{{nome}}</code>, <code>{{documento.titulo}}</code> ou com padrão <code>{{campo|Padrão}}</code>. No envio, passamos os valores via array.
      </div>
    </div>
  </section>
  
</div>
<?php require_once '../templates/footer.php'; ?>
