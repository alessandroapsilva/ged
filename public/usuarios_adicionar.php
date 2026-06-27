<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';

// Busca as funções disponíveis para preencher o <select>
try {
    $funcoes_sql = "SELECT id, nome_funcao FROM funcoes ORDER BY nome_funcao ASC";
    $funcoes_stmt = $pdo->prepare($funcoes_sql);
    $funcoes_stmt->execute();
    $funcoes = $funcoes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar funções.");
}

// Detecta se a coluna username existe para exibir o campo no formulário
$hasUsername = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
    $hasUsername = (bool)$col->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $hasUsername = false; }

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Adicionar Novo Usuário</h1>
                </div>
                <div class="col-sm-6">
                    <a href="usuarios_listar.php" class="btn btn-sm btn-secondary float-sm-right">
                        <i class="fas fa-arrow-left"></i> Voltar para a Lista
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?php 
                    switch ($_GET['erro']) {
                        case 'username_vazio':
                            echo 'O <strong>Nome de Usuário</strong> é obrigatório e não pode ficar vazio.';
                            break;
                        case 'username_duplicado':
                            echo 'Este <strong>Nome de Usuário</strong> já está em uso. Por favor, escolha outro.';
                            break;
                        case 'campos_vazios':
                            echo 'Por favor, preencha todos os campos obrigatórios.';
                            break;
                        case 'db_error':
                            echo 'Erro ao salvar no banco de dados. Tente novamente ou contate o administrador.';
                            break;
                        default:
                            echo 'Ocorreu um erro ao processar sua solicitação.';
                    }
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dados do Usuário</h3>
                </div>
                <form action="usuarios_salvar.php" method="post">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite o nome do usuário" required>
                        </div>
                        <?php if ($hasUsername): ?>
                        <div class="form-group">
                            <label for="username">
                                Nome de Usuário 
                                <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                placeholder="ex.: joaosilva" 
                                pattern="^[a-z0-9._-]{3,30}$"
                                title="Use apenas letras minúsculas, números, ponto, hífen e underline (3 a 30 caracteres)"
                                required
                            >
                            <small class="form-text text-muted">Login de acesso ao sistema. Obrigatório.</small>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Digite o e-mail de acesso" required>
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite a senha inicial" required>
                        </div>
                        <div class="form-group">
                            <label for="funcao_id">Função</label>
                            <select class="form-control" id="funcao_id" name="funcao_id" required>
                                <option value="">Selecione uma função</option>
                                <?php foreach ($funcoes as $funcao): ?>
                                    <option value="<?php echo $funcao['id']; ?>">
                                        <?php echo htmlspecialchars($funcao['nome_funcao']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                              <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1" checked>
                              <label class="custom-control-label" for="ativo">Usuário Ativo</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>