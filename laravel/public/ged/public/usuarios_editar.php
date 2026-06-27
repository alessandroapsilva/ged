<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';

// 1. Pega o ID do usuário da URL e valida
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: usuarios_listar.php');
    exit();
}
$id = (int)$_GET['id'];

try {
    // 2. Busca os dados do usuário específico que será editado
    $sql = "SELECT u.*, f.nome_funcao FROM usuarios u LEFT JOIN funcoes f ON u.funcao_id = f.id WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se não encontrar o usuário, volta para a lista
    if (!$usuario) {
        header('Location: usuarios_listar.php');
        exit();
    }

    // 3. Busca todas as funções para preencher o <select>
    $funcoes_sql = "SELECT id, nome_funcao FROM funcoes ORDER BY nome_funcao ASC";
    $funcoes_stmt = $pdo->query($funcoes_sql);
    $funcoes = $funcoes_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Estatísticas do usuário
    $stats = [];
    try {
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_docs,
                COUNT(CASE WHEN DATE(data_upload) >= CURDATE() - INTERVAL 30 DAY THEN 1 END) as docs_mes,
                COUNT(CASE WHEN DATE(data_upload) = CURDATE() THEN 1 END) as docs_hoje
            FROM documentos 
            WHERE usuario_id = ? AND apagado_em IS NULL
        ");
        $stats_stmt->execute([$id]);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stats = ['total_docs' => 0, 'docs_mes' => 0, 'docs_hoje' => 0];
    }

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-user-edit"></i> Editar Usuário</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="usuarios_listar.php">Usuários</a></li>
                        <li class="breadcrumb-item active">Editar</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Coluna Principal - Formulário -->
                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-circle"></i> 
                                Editando: <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                            </h3>
                        </div>
                        <form action="usuarios_atualizar.php" method="post" id="formEditarUsuario">
                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="nome">
                                                <i class="fas fa-user"></i> Nome Completo
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="nome" 
                                                name="nome" 
                                                value="<?php echo htmlspecialchars($usuario['nome']); ?>" 
                                                required
                                                placeholder="Digite o nome completo"
                                            >
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">
                                                <i class="fas fa-envelope"></i> E-mail
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input 
                                                type="email" 
                                                class="form-control" 
                                                id="email" 
                                                name="email" 
                                                value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                                                required
                                                placeholder="usuario@exemplo.com"
                                            >
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="funcao_id">
                                                <i class="fas fa-id-badge"></i> Função
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="funcao_id" name="funcao_id" required>
                                                <option value="">Selecione uma função</option>
                                                <?php foreach ($funcoes as $funcao): ?>
                                                    <option 
                                                        value="<?php echo $funcao['id']; ?>" 
                                                        <?php echo ($usuario['funcao_id'] == $funcao['id']) ? 'selected' : ''; ?>
                                                    >
                                                        <?php echo htmlspecialchars($funcao['nome_funcao']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="senha">
                                                <i class="fas fa-lock"></i> Nova Senha
                                            </label>
                                            <div class="input-group">
                                                <input 
                                                    type="password" 
                                                    class="form-control" 
                                                    id="senha" 
                                                    name="senha" 
                                                    placeholder="Deixe em branco para não alterar"
                                                    minlength="6"
                                                >
                                                <div class="input-group-append">
                                                    <button 
                                                        class="btn btn-outline-secondary" 
                                                        type="button" 
                                                        id="togglePassword"
                                                        title="Mostrar/Ocultar senha"
                                                    >
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                Mínimo de 6 caracteres. Deixe em branco para manter a senha atual.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch custom-switch-lg">
                                                <input 
                                                    type="checkbox" 
                                                    class="custom-control-input" 
                                                    id="ativo" 
                                                    name="ativo" 
                                                    value="1" 
                                                    <?php echo ($usuario['ativo']) ? 'checked' : ''; ?>
                                                >
                                                <label class="custom-control-label" for="ativo">
                                                    <strong>Usuário Ativo</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Desmarque para desabilitar o acesso ao sistema
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="usuarios_listar.php" class="btn btn-default">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Atualizar Usuário
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Coluna Lateral - Informações e Estatísticas -->
                <div class="col-md-4">
                    <!-- Info Básica -->
                    <div class="card card-widget widget-user-2">
                        <div class="widget-user-header bg-gradient-primary">
                            <div class="widget-user-image">
                                <i class="fas fa-user-circle fa-3x" style="color: white;"></i>
                            </div>
                            <h3 class="widget-user-username"><?php echo htmlspecialchars($usuario['nome']); ?></h3>
                            <h5 class="widget-user-desc"><?php echo htmlspecialchars($usuario['nome_funcao'] ?? 'Sem função'); ?></h5>
                        </div>
                        <div class="card-footer p-0">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <span class="nav-link">
                                        <i class="fas fa-envelope text-primary"></i> E-mail
                                        <span class="float-right"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                    </span>
                                </li>
                                <li class="nav-item">
                                    <span class="nav-link">
                                        <i class="fas fa-calendar-alt text-success"></i> Cadastrado em
                                        <span class="float-right">
                                            <?php echo isset($usuario['data_cadastro']) ? date('d/m/Y', strtotime($usuario['data_cadastro'])) : 'N/A'; ?>
                                        </span>
                                    </span>
                                </li>
                                <li class="nav-item">
                                    <span class="nav-link">
                                        <i class="fas fa-circle text-<?php echo $usuario['ativo'] ? 'success' : 'danger'; ?>"></i> Status
                                        <span class="float-right">
                                            <span class="badge badge-<?php echo $usuario['ativo'] ? 'success' : 'danger'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Estatísticas
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-file-alt text-primary"></i> Total de Documentos
                                        </span>
                                        <span class="badge badge-primary badge-pill">
                                            <?php echo number_format($stats['total_docs']); ?>
                                        </span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-calendar-check text-success"></i> Este Mês
                                        </span>
                                        <span class="badge badge-success badge-pill">
                                            <?php echo number_format($stats['docs_mes']); ?>
                                        </span>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-clock text-warning"></i> Hoje
                                        </span>
                                        <span class="badge badge-warning badge-pill">
                                            <?php echo number_format($stats['docs_hoje']); ?>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="documentos.php?usuario_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-folder-open"></i> Ver Documentos
                            </a>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle"></i> Ações Perigosas
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle"></i> 
                                Estas ações são irreversíveis. Use com cuidado.
                            </p>
                            <button 
                                type="button" 
                                class="btn btn-danger btn-block" 
                                data-confirm="Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita!"
                                onclick="if(confirm(this.getAttribute('data-confirm'))) window.location.href='usuarios_apagar.php?id=<?php echo $id; ?>'"
                            >
                                <i class="fas fa-trash-alt"></i> Excluir Usuário
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
$(function() {
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#senha');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Validação do formulário
    $('#formEditarUsuario').on('submit', function(e) {
        const senha = $('#senha').val();
        
        // Se senha foi preenchida, valida o tamanho mínimo
        if (senha && senha.length < 6) {
            e.preventDefault();
            GED.Toast.error('A senha deve ter no mínimo 6 caracteres', 'Erro de validação');
            return false;
        }

        // Confirma se está inativando o usuário
        if (!$('#ativo').is(':checked')) {
            if (!confirm('Você está desativando este usuário. Ele não poderá mais acessar o sistema. Deseja continuar?')) {
                e.preventDefault();
                return false;
            }
        }

        return true;
    });

    // Feedback visual ao salvar
    $('#formEditarUsuario').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
    });
});
</script>