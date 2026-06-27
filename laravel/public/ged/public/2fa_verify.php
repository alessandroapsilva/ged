<?php
require_once '../core/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PragmaRX\Google2FAQRCode\Google2FA;

// Apenas quando login_process definiu sessão pendente
if (!isset($_SESSION['2fa_pending_user']) || time() > ($_SESSION['2fa_pending_until'] ?? 0)) {
    header('Location: login.php');
    exit();
}

$error = isset($_GET['e']) ? (string)$_GET['e'] : '';
include '../templates/header.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Verificação em Duas Etapas</h1></div></section>
    <section class="content"><div class="container-fluid">
        <div class="row justify-content-center"><div class="col-md-6">
            <div class="card card-dark card-outline">
                <div class="card-body">
                    <p>Olá, <strong><?= htmlspecialchars($_SESSION['2fa_pending_name'] ?? 'usuário') ?></strong>. Digite o código do seu autenticador para concluir o login.</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">Código inválido. Tente novamente.</div>
                    <?php endif; ?>
                    <form action="2fa_verify_process.php" method="post" class="form-inline">
                        <input type="text" name="otp" class="form-control mr-2" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autofocus required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-unlock mr-1"></i> Entrar</button>
                    </form>
                    <p class="mt-3 text-muted"><small>Expira em 5 minutos. Para segurança, fechará automaticamente após esse período.</small></p>
                </div>
            </div>
        </div></div>
    </div></section>
</div>
<?php include '../templates/footer.php'; ?>
