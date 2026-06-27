<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Busca todas as permissões disponíveis para preencher a caixa de seleção
try {
    $permissoes_sql = "SELECT id, nome, descricao FROM permissoes ORDER BY nome ASC";
    $permissoes_stmt = $pdo->prepare($permissoes_sql);
    $permissoes_stmt->execute();
    $permissoes = $permissoes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar permissões: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Criar Nova Função</h1>
                </div>
                <div class="col-sm-6">
                    <a href="funcoes_listar.php" class="btn btn-sm btn-secondary float-sm-right">
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
                    <h3 class="card-title">Dados da Função</h3>
                </div>
                <form action="funcoes_salvar.php" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nome_funcao">Nome</label>
                                    <input type="text" class="form-control" id="nome_funcao" name="nome_funcao" placeholder="Ex: Colaborador" required>
                                </div>
                                <div class="form-group">
                                    <label for="chave">Chave</label>
                                    <input type="text" class="form-control" id="chave" name="chave" placeholder="Ex: colaborador" required>
                                </div>
                                <div class="form-group">
                                    <label for="descricao">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descreva o propósito desta função"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nivel">Nível</label>
                                    <input type="number" class="form-control" id="nivel" name="nivel" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Permissões</label>
                                    <select multiple class="form-control" name="permissoes[]" size="8">
                                        <?php foreach ($permissoes as $permissao): ?>
                                            <option value="<?php echo $permissao['id']; ?>">
                                                <?php echo htmlspecialchars($permissao['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar mais de uma opção.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Salvar Função</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>