<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (!usuario_tem_permissao('role.edit')) { header('Location: ../acesso_negado.php'); exit(); }
$funcao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$funcao_id) { header('Location: funcoes_listar.php'); exit(); }
try {
    $stmt = $pdo->prepare("SELECT * FROM funcoes WHERE id = ?"); $stmt->execute([$funcao_id]);
    $funcao = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$funcao) { header('Location: funcoes_listar.php'); exit(); }
    $permissoes_disponiveis = $pdo->query("SELECT * FROM permissoes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT permissao_id FROM funcao_permissao WHERE funcao_id = ?"); $stmt->execute([$funcao_id]);
    $permissoes_atuais_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { die("Erro ao carregar dados para edição: " . $e->getMessage()); }
include '../templates/header.php';
include '../templates/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Editar Função</h1></div></section>
    <section class="content"><div class="container-fluid"><div class="card card-warning card-outline">
        <div class="card-header"><h3 class="card-title">Editando: <?php echo htmlspecialchars($funcao['nome_funcao']); ?></h3></div>
        <form id="form-editar-funcao" action="funcoes_atualizar.php" method="post">
            <input type="hidden" name="funcao_id" value="<?php echo $funcao['id']; ?>">
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-6"><label for="nome_funcao">Nome da Função</label><input type="text" class="form-control" name="nome_funcao" value="<?php echo htmlspecialchars($funcao['nome_funcao']); ?>" required></div>
                    <div class="form-group col-md-6"><label for="chave">Chave</label><input type="text" class="form-control" name="chave" value="<?php echo htmlspecialchars($funcao['chave']); ?>" required></div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6"><label for="descricao">Descrição</label><input type="text" class="form-control" name="descricao" value="<?php echo htmlspecialchars($funcao['descricao']); ?>"></div>
                    <div class="form-group col-md-6"><label for="nivel">Nível</label><input type="number" class="form-control" name="nivel" value="<?php echo htmlspecialchars($funcao['nivel']); ?>" required></div>
                </div>
                <div class="form-group">
                    <label for="permissoes">Permissões</label>
                    <select name="permissoes[]" id="permissoes" class="form-control" multiple="multiple" style="height: 300px;">
                        <?php foreach ($permissoes_disponiveis as $permissao): ?>
                            <option value="<?php echo $permissao['id']; ?>" <?php echo in_array($permissao['id'], $permissoes_atuais_raw) ? 'selected' : ''; ?>><?php echo htmlspecialchars($permissao['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" id="btn-salvar-funcao" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
            </div>
        </form>
    </div></div></section>
</div>
<?php include '../templates/footer.php'; ?>
<script>
$(function(){
    $('#btn-salvar-funcao').on('click', function(e) {
        e.preventDefault();
        var form = $('#form-editar-funcao');
        var data = form.serialize();
        $.ajax({
            type: "POST", url: form.attr('action'), data: data, dataType: 'json',
            success: function(response) {
                if (response.sucesso) {
                    Swal.fire({ icon: 'success', title: 'Salvo!', text: 'A função foi atualizada com sucesso.', timer: 2000, showConfirmButton: false})
                    .then(() => { window.location.href = 'funcoes_listar.php'; });
                } else { Swal.fire({ icon: 'error', title: 'Falha ao Salvar!', text: response.erro }); }
            },
            error: function(jqXHR) {
                Swal.fire({ icon: 'error', title: 'Erro Fatal no Servidor!',
                    html: '<p>O script PHP falhou. Resposta do servidor:</p><pre style="text-align: left; max-height: 200px; overflow-y: auto;">' + jqXHR.responseText + '</pre>',
                    confirmButtonText: 'Entendi'
                });
            }
        });
    });
});
</script>