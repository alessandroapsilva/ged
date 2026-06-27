<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Adicionar Nova Permissão</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title">Detalhes da Permissão</h3>
                </div>
                <form action="permissoes_adicionar_process.php" method="post">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nome">Nome (o que o usuário vê)</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Gerenciar Faturamento" required>
                        </div>
                        <div class="form-group">
                            <label for="chave">Chave (identificador interno)</label>
                            <input type="text" class="form-control" id="chave" name="chave" placeholder="Ex: faturamento.gerenciar" required>
                            <small class="form-text text-muted">Use o formato 'modulo.acao', sem espaços ou acentos.</small>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="O que esta permissão faz?"></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="funcoes_listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Salvar Permissão</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>