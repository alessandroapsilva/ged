<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';

// Valida o ID da permissão vindo da URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: permissoes_listar.php');
    exit();
}
$id = (int)$_GET['id'];

// Busca os dados da permissão que será editada
try {
    $sql = "SELECT * FROM permissoes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $permissao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$permissao) {
        header('Location: permissoes_listar.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados da permissão.");
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Editar Permissão</h1>
                </div>
                <div class="col-sm-6">
                    <a href="permissoes_listar.php" class="btn btn-sm btn-secondary float-sm-right">
                        <i class="fas fa-arrow-left"></i> Voltar para a Lista
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Editando: <?php echo htmlspecialchars($permissao['nome']); ?></h3>
                </div>
                <form action="permissoes_atualizar.php" method="post">
                    <input type="hidden" name="id" value="<?php echo $permissao['id']; ?>">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nome">Nome da Permissão</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($permissao['nome']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="chave">Chave</label>
                            <input type="text" class="form-control" id="chave" name="chave" value="<?php echo htmlspecialchars($permissao['chave']); ?>" required>
                            <small class="form-text text-muted">Identificador único para o sistema (use o padrão: modulo.acao).</small>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($permissao['descricao']); ?>">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>