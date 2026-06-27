<?php
require_once '../core/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PragmaRX\Google2FAQRCode\Google2FA;

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
$secret = $_SESSION['2fa_setup_secret'] ?? null;
if (!$secret || !preg_match('/^[0-9]{6}$/', $otp)) { header('Location: 2fa_setup.php'); exit(); }

$g2fa = new Google2FA();
$valid = false;
try { $valid = $g2fa->verifyKey($secret, $otp); } catch (Throwable $e) { $valid = false; }

if ($valid) {
    try {
        $stmt = $pdo->prepare('UPDATE usuarios SET twofa_enabled = 1, twofa_secret = ? WHERE id = ?');
        $stmt->execute([$secret, (int)$_SESSION['user_id']]);
        unset($_SESSION['2fa_setup_secret']);
        $_SESSION['flash_message'] = ['type'=>'sucesso','text'=>'Acesso em Duas Etapas ativado com sucesso.'];
        header('Location: perfil.php');
        exit();
    } catch (Throwable $e) {
        $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Falha ao salvar 2FA: '.$e->getMessage()];
        header('Location: perfil.php');
        exit();
    }
}

$_SESSION['flash_message'] = ['type'=>'erro','text'=>'Código inválido. Tente novamente.'];
header('Location: 2fa_setup.php');
exit();
