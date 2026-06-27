
<?php
// public/documentos_compartilhar.php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$documento_id = (int)($_GET['id'] ?? 0);
if ($documento_id === 0) { header('Location: documentos.php'); exit(); }

// Busca os detalhes do documento para exibir o título
$stmt = $pdo->prepare("SELECT titulo, pasta_id FROM documentos WHERE id = ?");
$stmt->execute([$documento_id]);
$documento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$documento) { die("Documento nao encontrado."); }

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Compartilhar Documento</h1>
                    <h5 class="text-muted"><?= htmlspecialchars($documento['titulo']); ?></h5>
                </div>
                <div class="col-sm-6">
                    <a href="documentos.php?pasta_id=<?= $documento['pasta_id'] ?>" class="btn btn-secondary float-sm-right">
                        <i class="fas fa-arrow-left mr-1"></i> Voltar para a Pasta Anterior
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <form action="documentos_compartilhar_process" method="POST">
                        <input type="hidden" name="documento_id" value="<?= $documento_id; ?>">
                        <div class="card card-primary card-outline">
                            <div class="card-header"><h3 class="card-title">Destinatários</h3></div>
                            <div class="card-body">
                                <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                <div class="form-group">
                                    <label for="usuarios_internos">Usuários do Sistema</label>
                                    <input type="text" name="users" id="usuarios_internos" class="form-control" placeholder="IDs de usuários, separados por vírgula (use a busca abaixo)">
                                    <small class="form-text text-muted">Dica: use a busca por nome/e-mail abaixo para localizar IDs e adicioná-los aqui.</small>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="view_only" name="view_only" value="1">
                                            <label class="custom-control-label" for="view_only">Somente visualização</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="can_download" name="can_download" value="1" checked>
                                            <label class="custom-control-label" for="can_download">Permitir download</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-2">
                                    <label>Expira em (opcional)</label>
                                    <input type="datetime-local" name="expires_at" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Mensagem ao destinatário (opcional)</label>
                                    <textarea name="message" class="form-control" rows="2" placeholder="Mensagem adicional"></textarea>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label for="emails_externos">Emails Externos</label>
                                    <textarea name="emails" id="emails_externos" class="form-control" rows="5" placeholder="Digite os endereços de e-mail, separados por vírgula ou um por linha."></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="aceite_responsabilidade" name="aceite_responsabilidade" value="1" required>
                                        <label class="custom-control-label" for="aceite_responsabilidade">
                                            <small>Estou ciente e aceito a responsabilidade legal ao compartilhar este documento.</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-paper-plane mr-1"></i> Enviar Compartilhamento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-8">
                    <div class="card card-primary card-outline" style="height: 80vh;">
                         <div class="card-body p-0">
                            <iframe src="<?= BASE_URL ?>/documentos_ver?id=<?= $documento_id; ?>" style="width:100%; height:100%; border:none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script>
(function(){
    const input = document.getElementById('usuarios_internos');
    const search = document.createElement('input');
    search.type = 'text';
    search.className = 'form-control mt-2';
    search.placeholder = 'Buscar usuário por nome ou e-mail...';
    const container = document.getElementById('usuarios_internos').parentElement;
    container.appendChild(search);
    const results = document.createElement('div');
    results.className = 'list-group mt-2';
    container.appendChild(results);
    let t=null;
    search.addEventListener('input', function(){
        clearTimeout(t);
        const q=this.value.trim();
        if (q.length<2){ results.innerHTML=''; return; }
        t=setTimeout(()=>{
            fetch('ajax_buscar_usuarios.php?q='+encodeURIComponent(q),{credentials:'same-origin'})
                .then(r=>r.json()).then(rows=>{
                    results.innerHTML='';
                    (rows||[]).forEach(u=>{
                        const a=document.createElement('a');
                        a.href='#'; a.className='list-group-item list-group-item-action';
                        a.textContent = `#${u.id} · ${u.text}`;
                        a.addEventListener('click', (e)=>{ e.preventDefault(); input.value = (input.value? input.value + ', ':'') + u.id; });
                        results.appendChild(a);
                    });
                }).catch(()=>{});
        },200);
    });
})();
</script>