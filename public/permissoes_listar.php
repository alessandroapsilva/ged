<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/auth_helper.php';

// Proteção da página
if (!usuario_tem_permissao('admin.access') && $_SESSION['user_id'] != 1) {
    header('Location: acesso_negado.php');
    exit();
}

// Query para buscar as permissões e as funções que as utilizam
$sql = "SELECT p.id, p.nome, p.chave, p.descricao, 
               GROUP_CONCAT(CONCAT(f.id, ':', f.nome_funcao) SEPARATOR ';') AS funcoes
        FROM permissoes p
        LEFT JOIN funcao_permissao fp ON p.id = fp.permissao_id
        LEFT JOIN funcoes f ON fp.funcao_id = f.id
        GROUP BY p.id, p.nome, p.chave, p.descricao 
        ORDER BY p.nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1>Permissões Disponíveis</h1></div>
                <div class="col-sm-6"><a href="funcoes_listar.php" class="btn btn-sm btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Voltar para Funções</a></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Permissões Cadastradas</h3>
                    <div class="card-tools">
                        <a href="permissoes_adicionar.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Criar Permissão</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th> <th>Nome</th> <th>Chave</th> <th>Descrição</th> <th>Utilizado Por (Funções)</th> <th style="width: 15%">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permissoes as $permissao): ?>
                                <tr>
                                    <td><?php echo $permissao['id']; ?></td>
                                    <td><?php echo htmlspecialchars($permissao['nome']); ?></td>
                                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($permissao['chave']); ?></span></td>
                                    <td><?php echo htmlspecialchars($permissao['descricao']); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($permissao['funcoes'])) {
                                            $funcoes_array = explode(';', $permissao['funcoes']);
                                            foreach ($funcoes_array as $funcao_str) {
                                                if (strpos($funcao_str, ':') !== false) {
                                                    list($funcao_id, $funcao_nome) = explode(':', $funcao_str, 2);
                                                    echo '<a href="funcoes_editar.php?id=' . (int)$funcao_id . '" class="badge badge-info mr-1">' . htmlspecialchars(trim($funcao_nome)) . '</a>';
                                                }
                                            }
                                        } else {
                                            echo '<span class="badge badge-light">Nenhuma</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="permissoes_editar.php?id=<?php echo $permissao['id']; ?>" class="btn btn-info" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                            <a href="permissoes_apagar.php?id=<?php echo $permissao['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza?')" title="Apagar"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>