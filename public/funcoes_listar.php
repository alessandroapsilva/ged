<?php
// public/funcoes_listar.php (CORRIGIDO)
require_once '../core/init.php';
// A linha session_start() foi removida daqui, pois o init.php já a executa.

if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Lógica para exibir mensagens de sucesso
$mensagem_sucesso = '';
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'salvo') {
        $mensagem_sucesso = 'A função foi salva com sucesso.';
    } elseif ($_GET['sucesso'] == 'apagado') {
        $mensagem_sucesso = 'A função foi apagada com sucesso.';
    } elseif ($_GET['sucesso'] == 'permissao_add') {
        $mensagem_sucesso = 'Nova permissão criada com sucesso.';
    }
}

try {
    // 1. Buscar todas as Funções
    $funcoes = $pdo->query("SELECT * FROM funcoes ORDER BY nivel DESC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar todas as Permissões
    $permissoes = $pdo->query("SELECT * FROM permissoes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Buscar as ligações entre Funções e Permissões
    $funcao_permissoes_raw = $pdo->query("SELECT * FROM funcao_permissao")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Contar quantos usuários existem em cada função
    $users_por_funcao_raw = $pdo->query("SELECT funcao_id, COUNT(id) as total_usuarios FROM usuarios GROUP BY funcao_id")->fetchAll(PDO::FETCH_ASSOC);
    
    // --- Processamento dos dados para facilitar o uso no HTML ---

    $users_por_funcao = [];
    foreach ($users_por_funcao_raw as $item) {
        $users_por_funcao[$item['funcao_id']] = $item['total_usuarios'];
    }

    $permissoes_por_funcao = [];
    foreach($funcao_permissoes_raw as $fp) {
        $permissoes_por_funcao[$fp['funcao_id']][] = $fp['permissao_id'];
    }
    
    $funcoes_por_permissao = [];
    foreach($funcao_permissoes_raw as $fp) {
        $funcoes_por_permissao[$fp['permissao_id']][] = $fp['funcao_id'];
    }

} catch (PDOException $e) {
    die("Erro ao carregar dados de Funções e Permissões: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><h1>Funções & Permissões</h1></div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                <?= $mensagem_sucesso; ?>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-dark card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Funções (<?= count($funcoes) ?>)</h3>
                            <div class="card-tools">
                                <a href="funcao_gerenciar.php" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nova Função</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($funcoes as $funcao): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-tag text-secondary mr-2"></i>
                                            <strong><?= htmlspecialchars($funcao['nome_funcao']) ?></strong>
                                            <span class="badge bg-primary ml-2">Nível <?= $funcao['nivel'] ?></span>
                                        </div>
                                        <div>
                                            <span class="badge bg-info mr-1"><?= $users_por_funcao[$funcao['id']] ?? 0 ?> usuário(s)</span>
                                            <span class="badge bg-warning mr-2"><?= isset($permissoes_por_funcao[$funcao['id']]) ? count($permissoes_por_funcao[$funcao['id']]) : 0 ?> permissão(ões)</span>
                                            <a href="funcao_gerenciar.php?id=<?= $funcao['id'] ?>" class="btn btn-xs btn-warning" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                            <a href="funcoes_apagar.php?id=<?= $funcao['id'] ?>" class="btn btn-xs btn-danger btn-apagar-swal" title="Apagar"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-dark card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Permissões (<?= count($permissoes) ?>)</h3>
                            <div class="card-tools">
                                <a href="permissoes_adicionar.php" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nova Permissão</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                             <ul class="list-group list-group-flush">
                                <?php foreach ($permissoes as $permissao): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-key text-secondary mr-2"></i>
                                            <?= htmlspecialchars($permissao['nome']) ?>
                                            <small class="text-muted ml-2">(<?= htmlspecialchars($permissao['chave']) ?>)</small>
                                        </div>
                                        <span class="badge bg-info">
                                            <?= isset($funcoes_por_permissao[$permissao['id']]) ? count($funcoes_por_permissao[$permissao['id']]) : 0 ?> função(ões)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>