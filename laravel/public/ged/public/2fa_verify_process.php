<?php
require_once '../core/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PragmaRX\Google2FAQRCode\Google2FA;

if (!isset($_SESSION['2fa_pending_user']) || time() > ($_SESSION['2fa_pending_until'] ?? 0)) {
    header('Location: login.php');
    exit();
}
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
if (!preg_match('/^[0-9]{6}$/', $otp)) { header('Location: 2fa_verify.php?e=1'); exit(); }

$userId = (int)$_SESSION['2fa_pending_user'];
$secret = null; $nome = $_SESSION['2fa_pending_name'] ?? '';
try {
    $st = $pdo->prepare('SELECT twofa_secret FROM usuarios WHERE id = ?');
    $st->execute([$userId]);
    $secret = $st->fetchColumn();
} catch (Throwable $e) {}

if (!$secret) { header('Location: login.php'); exit(); }

$g2fa = new Google2FA();
$ok = false;
try { $ok = $g2fa->verifyKey($secret, $otp); } catch (Throwable $e) { $ok = false; }

if ($ok) {
    // Conclui o login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_funcao_id'] = $_SESSION['2fa_pending_funcao_id'] ?? 3;
    unset($_SESSION['2fa_pending_user'], $_SESSION['2fa_pending_name'], $_SESSION['2fa_pending_funcao_id'], $_SESSION['2fa_pending_until']);
    header('Location: documentos.php');
    exit();
}

header('Location: 2fa_verify.php?e=1');
exit();
