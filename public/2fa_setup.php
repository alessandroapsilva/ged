<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Carrega autoload para Google2FA
require_once __DIR__ . '/../vendor/autoload.php';
use PragmaRX\Google2FAQRCode\Google2FA;

$userId = (int)$_SESSION['user_id'];
$userEmail = '';
try {
    $st = $pdo->prepare('SELECT email, twofa_enabled FROM usuarios WHERE id = ?');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $userEmail = $row['email'] ?? '';
    if (!empty($row['twofa_enabled'])) {
        header('Location: perfil.php');
        exit();
    }
} catch (Throwable $e) {}

$g2fa = new Google2FA();
$secret = $g2fa->generateSecretKey(32);
$_SESSION['2fa_setup_secret'] = $secret;
$issuer = 'ENFAS GED';
$otpauthUrl = $g2fa->getQRCodeUrl($issuer, $userEmail ?: ('user'.$userId), $secret);
$inlineQr = $g2fa->getQRCodeInline($issuer, $userEmail ?: ('user'.$userId), $secret, 200);

include '../templates/header.php';
include '../templates/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Ativar Acesso em Duas Etapas (2FA)</h1></div></section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-7">
                    <div class="card card-dark card-outline">
                        <div class="card-header"><h3 class="card-title">1) Escaneie o QR Code no seu autenticador</h3></div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="<?= $inlineQr ?>" alt="QR 2FA" style="max-width: 220px; border: 8px solid #111; padding: 8px; background:#fff; border-radius: 6px;">
                                <p class="mt-3"><code style="user-select:all;"><?= htmlspecialchars($otpauthUrl) ?></code></p>
                                <small class="text-muted">Use Google Authenticator, Microsoft Authenticator, Authy, etc.</small>
                            </div>
                            <hr>
                            <form action="2fa_setup_process.php" method="post" class="form-inline">
                                <label for="otp" class="mr-2">2) Digite o código de 6 dígitos:</label>
                                <input type="text" class="form-control mr-2" id="otp" name="otp" pattern="[0-9]{6}" maxlength="6" required>
                                <button type="submit" class="btn btn-success"><i class="fas fa-shield-alt mr-1"></i> Ativar 2FA</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="callout callout-info">
                        <h5>Dica de segurança</h5>
                        <p>Guarde códigos de recuperação fora do computador. Se perder o autenticador e não tiver códigos de emergência, um administrador precisará remover a 2FA manualmente.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php include '../templates/footer.php'; ?>
