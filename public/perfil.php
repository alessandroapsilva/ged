<?php
// public/perfil.php (VERSÃO FINAL - ESTILO EDOK)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$mensagem_sucesso = '';
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'atualizado') {
        $mensagem_sucesso = 'Seu perfil foi atualizado com sucesso!';
    } elseif ($_GET['sucesso'] == 'senha_alterada') {
        $mensagem_sucesso = 'Sua senha foi alterada com sucesso!';
    }
}

try {
    // Busca os dados do usuário (inclui colunas de 2FA se existirem)
    $stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: logout.php');
        exit();
    }
    
    // Conta os documentos do usuário
    $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM documentos WHERE usuario_id = ? AND apagado_em IS NULL");
    $stmt_count->execute([$user_id]);
    $item_count = $stmt_count->fetchColumn();

    // Define o nome da função
    $funcao_nome = 'Usuário Padrão';
    if (isset($user['funcao_id']) && $user['funcao_id'] == 1) {
        $funcao_nome = 'Admin';
    }

    // Certificado do usuário (registro mais recente ativo)
    $certAtual = null;
    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'usuario_certificados'");
        if ($chk && $chk->rowCount() > 0) {
            $stc = $pdo->prepare("SELECT * FROM usuario_certificados WHERE usuario_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1");
            $stc->execute([$user_id]);
            $certAtual = $stc->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) { /* silencioso */ }

} catch (PDOException $e) {
    die("Erro ao carregar dados do perfil: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Perfil de <?= htmlspecialchars($user['nome']) ?></h1>
        </div>
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

            <div class="card card-dark card-outline">
                <div class="card-body">
                    <div class="text-center mb-5">
                        <h2 class="mt-2"><?= htmlspecialchars($user['nome']) ?></h2>
                        <p class="text-warning"><?= htmlspecialchars($user['email']) ?></p>
                        <a href="perfil_editar" class="btn btn-warning btn-lg"><i class="fas fa-edit mr-1"></i> Editar</a>
                    </div>

                    <dl class="row">
                        <dt class="col-sm-3 text-right">ID</dt>
                        <dd class="col-sm-9"><?= $user['id'] ?></dd>

                        <dt class="col-sm-3 text-right">Itens no Repositório</dt>
                        <dd class="col-sm-9"><?= $item_count > 0 ? $item_count : 'Nenhum' ?></dd>

                        <dt class="col-sm-3 text-right">Email</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($user['email']) ?></dd>

                        <dt class="col-sm-3 text-right">Nome</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($user['nome']) ?></dd>
                        
                        <dt class="col-sm-3 text-right">Unidade(s)</dt>
                        <dd class="col-sm-9">Todas</dd>

                        <dt class="col-sm-3 text-right">Acesso em Duas Etapas</dt>
                        <dd class="col-sm-9">
                            <?php $twofaOn = !empty($user['twofa_enabled']) && !empty($user['twofa_secret']); ?>
                            <?php if ($twofaOn): ?>
                                <span class="badge bg-success">Ativado</span>
                                <a href="2fa_disable" class="btn btn-xs btn-outline-danger ml-2"><i class="fas fa-shield-alt mr-1"></i> Desativar</a>
                            <?php else: ?>
                                <span class="badge bg-danger">Desativado</span>
                                <a href="2fa_setup" class="btn btn-xs btn-success ml-2"><i class="fas fa-shield-alt mr-1"></i> Ativar</a>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="col-sm-3 text-right">Certificado Digital</dt>
                        <dd class="col-sm-9">
                            <?php if ($certAtual): $exp = $certAquiExp = $certAtual['valid_to'] ?? null; $ehValido = $exp ? (strtotime($exp) >= time()) : null; ?>
                                <span class="badge <?= $ehValido ? 'bg-success' : 'bg-warning' ?>"><?= $ehValido ? 'Instalado' : 'Instalado (expirado)' ?></span>
                                <?php if (!empty($certAtual['subject_cn'])): ?>
                                    <div class="mt-2 small text-muted">
                                        <div><strong>Titular:</strong> <?= htmlspecialchars($certAtual['subject_cn']) ?></div>
                                        <div><strong>AC:</strong> <?= htmlspecialchars($certAtual['issuer_cn'] ?? '—') ?></div>
                                        <?php if ($certAtual['valid_from'] || $certAtual['valid_to']): ?>
                                            <div><strong>Validade:</strong> <?= $certAtual['valid_from'] ? date('d/m/Y', strtotime($certAtual['valid_from'])) : '—' ?> a <?= $certAtual['valid_to'] ? date('d/m/Y', strtotime($certAtual['valid_to'])) : '—' ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger">Não Instalado</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="col-sm-3 text-right">Função</dt>
                        <dd class="col-sm-9"><span class="badge bg-primary"><?= $funcao_nome ?></span></dd>

                        <dt class="col-sm-3 text-right">Criado em</dt>
                        <dd class="col-sm-9"><?= isset($user['data_criacao']) ? date('d/m/Y H:i', strtotime($user['data_criacao'])) : 'N/A' ?></dd>
                        
                        <dt class="col-sm-3 text-right">Atualizado em</dt>
                        <dd class="col-sm-9"><?= isset($user['data_atualizacao']) ? date('d/m/Y H:i', strtotime($user['data_atualizacao'])) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>