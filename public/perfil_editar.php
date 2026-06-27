<?php
// public/perfil_editar.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Busca os dados atuais do usuário para preencher o formulário
try {
    $stmt_user = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Se por algum motivo o usuário não for encontrado, desloga
        header('Location: logout.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao carregar dados do perfil: " . $e->getMessage());
}


include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Editando <?= htmlspecialchars($user['nome']) ?></h1>
                <a href="perfil" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Voltar para Perfil</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <form action="perfil_atualizar" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="form-group row mb-4">
                            <label class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="email" readonly class="form-control-plaintext" value="<?= htmlspecialchars($user['email']) ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label for="nome" class="col-sm-3 col-form-label">Nome</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="nome" id="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalAlterarSenha">
                            <i class="fas fa-key mr-1"></i> Alterar Senha
                        </button>
                        <button type="submit" class="btn btn-success float-right">
                            <i class="fas fa-save mr-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-primary mt-4">
                <div class="card-header"><h3 class="card-title">Vincular Certificado Digital (A1)</h3></div>
                <div class="card-body">
                    <form action="perfil_certificado_upload" method="post" enctype="multipart/form-data" id="formCert">
                        <div class="form-group row">
                            <label for="certificado" class="col-sm-3 col-form-label">Arquivo .pfx/.p12</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="certificado" name="certificado" accept=".pfx,.p12" required>
                                        <label class="custom-file-label" for="certificado">Escolher arquivo .pfx/.p12...</label>
                                    </div>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Senha do Certificado</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha do certificado" required>
                                    <div class="input-group-append"><button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Vincular PFX</button></div>
                                </div>
                                <small class="form-text text-muted">Seu certificado A1 ficará armazenado de forma protegida no servidor para uso nas assinaturas.</small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalAlterarSenha" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="perfil_alterar_senha" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Alterar Senha</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" name="senha_atual" id="senha_atual" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" name="nova_senha" id="nova_senha" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar Nova Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>

<script>
// Script para mostrar o nome do arquivo no campo de upload
$('.custom-file-input').on('change', function(e) {
   var fileName = e.target.files.length > 0 ? e.target.files[0].name : 'Escolher arquivo...';
   $(this).next('.custom-file-label').html(fileName);
});
</script>