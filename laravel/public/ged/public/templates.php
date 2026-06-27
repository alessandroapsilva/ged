<?php
// public/templates.php - Gestão de Templates (metadados) para criação rápida de documentos
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$user_id = (int)$_SESSION['user_id'];

// Garante a existência da tabela de templates (modo seguro)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS doc_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(190) NOT NULL,
        descricao TEXT NULL,
        tipo_documento_id INT NULL,
        publico TINYINT(1) NOT NULL DEFAULT 1,
        criado_por INT NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (tipo_documento_id),
        INDEX (criado_por)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Throwable $e) {
    // silencioso
}

$mensagem = null; $mensagem_tipo = 'success';

// Carrega tipos de documento para o select
$tipos_documento = [];
try {
    $tipos_stmt = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome");
    $tipos_documento = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar template
    if (isset($_POST['acao']) && $_POST['acao'] === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo_id = !empty($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : null;
        $publico = isset($_POST['publico']) ? 1 : 0;

        if ($nome === '') {
            $mensagem = 'Informe o nome do template.'; $mensagem_tipo = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO doc_templates (nome, descricao, tipo_documento_id, publico, criado_por) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao ?: null, $tipo_id, $publico, $user_id]);
                $mensagem = 'Template criado com sucesso!';
            } catch (Throwable $e) {
                $mensagem = 'Erro ao criar template: ' . $e->getMessage(); $mensagem_tipo = 'danger';
            }
        }
    }

    // Excluir template (opcional simples)
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir' && isset($_POST['template_id'])) {
        $tid = (int)$_POST['template_id'];
        try {
            // Permite excluir se criador ou template não for público (ajuste conforme necessidade)
            $stmt = $pdo->prepare("DELETE FROM doc_templates WHERE id = ? AND (criado_por = ? OR publico = 0)");
            $stmt->execute([$tid, $user_id]);
            $mensagem = 'Template excluído.';
        } catch (Throwable $e) {
            $mensagem = 'Erro ao excluir template.'; $mensagem_tipo = 'danger';
        }
    }
}

// Lista templates visíveis ao usuário
$templates = [];
try {
    $stmt = $pdo->prepare("SELECT t.*, u.nome as criador_nome, td.nome as tipo_nome
                           FROM doc_templates t
                           LEFT JOIN usuarios u ON u.id = t.criado_por
                           LEFT JOIN tipos_documento td ON td.id = t.tipo_documento_id
                           WHERE t.publico = 1 OR t.criado_por = ?
                           ORDER BY t.criado_em DESC");
    $stmt->execute([$user_id]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1>Templates</h1>
                <p class="text-muted mb-0">Crie modelos para agilizar o cadastro de documentos</p>
            </div>
            <a href="documentos_adicionar.php" class="btn btn-secondary"><i class="fas fa-file-upload mr-1"></i>Novo Documento</a>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?= htmlspecialchars($mensagem_tipo) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title mb-0">Novo Template</h3></div>
                        <form method="post">
                            <input type="hidden" name="acao" value="criar">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nome</label>
                                    <input type="text" name="nome" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="3" placeholder="Opcional"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Tipo do Documento (padrão)</label>
                                    <select name="tipo_documento_id" class="form-control">
                                        <option value="">— Nenhum —</option>
                                        <?php foreach ($tipos_documento as $t): ?>
                                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group form-check">
                                    <input type="checkbox" name="publico" id="publico" class="form-check-input" checked>
                                    <label for="publico" class="form-check-label">Template público (visível para todos)</label>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Criar Template</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card card-dark card-outline">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Templates Disponíveis</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Tipo Padrão</th>
                                        <th>Criado por</th>
                                        <th>Visibilidade</th>
                                        <th class="text-right pr-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($templates)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum template encontrado.</td></tr>
                                    <?php else: foreach ($templates as $tpl): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($tpl['nome']) ?></strong>
                                                <?php if (!empty($tpl['descricao'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($tpl['descricao']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($tpl['tipo_nome'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($tpl['criador_nome'] ?? '—') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $tpl['publico'] ? 'success' : 'secondary' ?>"><?= $tpl['publico'] ? 'Público' : 'Privado' ?></span>
                                            </td>
                                            <td class="text-right pr-3">
                                                <a class="btn btn-sm btn-primary" href="documentos_adicionar.php?from_template=1&titulo=<?= urlencode('Documento - ' . $tpl['nome']) ?><?= $tpl['tipo_documento_id'] ? '&tipo_documento_id='.(int)$tpl['tipo_documento_id'] : '' ?>">
                                                    <i class="fas fa-magic mr-1"></i> Usar
                                                </a>
                                                <?php if (!$tpl['publico'] || (int)$tpl['criado_por'] === $user_id): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir template?');">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="template_id" value="<?= (int)$tpl['id'] ?>">
                                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>
