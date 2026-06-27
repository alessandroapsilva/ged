<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';

// Detecta se a coluna username existe
$hasUsername = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
    $hasUsername = (bool)$col->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $hasUsername = false; }

// Filtros de busca
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

// Busca todos os usuários e o nome de suas funções (usando JOIN)
$usernameSel = $hasUsername ? ', u.username' : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.nome LIKE :q OR u.email LIKE :q' . ($hasUsername ? ' OR u.username LIKE :q' : '') . ')';
    $params[':q'] = "%$q%";
}
if ($status === 'ativo') { $where[] = 'u.ativo = 1'; }
if ($status === 'inativo') { $where[] = 'u.ativo = 0'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT u.id, u.nome{$usernameSel}, u.email, u.ativo, f.nome_funcao 
    FROM usuarios u 
    JOIN funcoes f ON u.funcao_id = f.id
    $whereSql
    ORDER BY u.nome ASC";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Gerenciamento de Usuários</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Usuários Cadastrados</h3>
                    <div class="d-flex align-items-center">
                        <form method="get" class="form-inline mr-2">
                            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Buscar por nome, e-mail<?= $hasUsername ? ', usuário' : '' ?>" value="<?= htmlspecialchars($q) ?>">
                            <select name="status" class="form-control form-control-sm mr-2">
                                <option value="">Todos</option>
                                <option value="ativo" <?= $status==='ativo' ? 'selected' : '' ?>>Ativos</option>
                                <option value="inativo" <?= $status==='inativo' ? 'selected' : '' ?>>Inativos</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                        </form>
                        <a href="usuarios_adicionar.php" class="btn btn-sm btn-primary"><i class="fas fa-user-plus"></i> Novo Usuário</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                            <tr>
                                <th style="width: 1%"><input type="checkbox" id="check-all"></th>
                                <th>Nome</th>
                                <?php if ($hasUsername): ?>
                                <th>Usuário</th>
                                <?php endif; ?>
                                <th>Email</th>
                                <th>Função</th>
                                <th>Status</th>
                                <th style="width: 15%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr id="user-row-<?php echo $usuario['id']; ?>">
                                    <td><input type="checkbox" class="check-item" name="user_ids[]" value="<?php echo $usuario['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <?php if ($hasUsername): ?>
                                    <td><code><?php echo htmlspecialchars($usuario['username'] ?? ''); ?></code></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome_funcao']); ?></td>
                                    <td>
                                        <?php if ($usuario['ativo']): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="project-actions text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a class="btn btn-info" href="usuarios_editar.php?id=<?php echo $usuario['id']; ?>" title="Editar">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a class="btn btn-danger" href="usuarios_apagar.php?id=<?php echo $usuario['id']; ?>" onclick="return confirm('Tem certeza que deseja apagar o usuário \'<?php echo addslashes($usuario['nome']); ?>\'?')" title="Apagar"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="barra-selecionados" class="card-footer" style="display: none;">
                        <strong>Com selecionados:</strong>
                        <button class="btn btn-sm btn-success ml-2"><i class="fas fa-check"></i> Ativar</button>
                        <button class="btn btn-sm btn-warning"><i class="fas fa-ban"></i> Desativar</button>
                        <button id="btn-apagar-lote" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Apagar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // LÓGICA DAS AÇÕES EM LOTE
    function toggleActionBar() {
        if ($('.check-item:checked').length > 0) {
            $('#barra-selecionados').slideDown();
        } else {
            $('#barra-selecionados').slideUp();
        }
    }

    $('#check-all').on('click', function() {
        $('.check-item').prop('checked', $(this).prop('checked'));
        toggleActionBar();
    });

    $('.check-item').on('click', function() {
        if (!$(this).prop('checked')) {
            $('#check-all').prop('checked', false);
        }
        toggleActionBar();
    });

    // Ações dos botões em lote (por enquanto, apenas visuais)
    $('#btn-apagar-lote').on('click', function() {
        var count = $('.check-item:checked').length;
        if (count > 0) {
            alert('Ação de apagar ' + count + ' usuário(s) será implementada a seguir.');
        }
    });
});
</script>