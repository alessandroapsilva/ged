<?php
require_once '../../core/init.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../documentos.php'); exit(); }

$docId = (int)($_POST['documento_id'] ?? 0);
if ($docId <= 0) { header('Location: ../documentos.php'); exit(); }

// LGPD consent
if (lgpd_is_consent_required($pdo)) {
    if (!isset($_POST['lgpd_consent']) || $_POST['lgpd_consent'] !== '1') {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Você deve concordar com a Política de Privacidade para prosseguir.'];
        header('Location: index.php?id=' . $docId);
        exit();
    }
}

// Carrega configurações do Bird ID
function get($k,$def=''){ try{ $st=$GLOBALS['pdo']->prepare('SELECT valor FROM app_settings WHERE chave=?'); $st->execute([$k]); $r=$st->fetch(PDO::FETCH_ASSOC); return $r['valor'] ?? $def; }catch(Throwable $e){ return $def; } }
$authUrl = get('BIRD_AUTH_URL','');
$clientId = get('BIRD_CLIENT_ID','');
$cb = get('BIRD_CALLBACK_URL','');

if ($authUrl === '' || $clientId === '' || $cb === '') {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Bird ID não está configurado. Abra Administração > Configurar ICP.'];
    header('Location: index.php?id=' . $docId);
    exit();
}

// Monta URL de autorização OAuth2
$state = base64_encode(json_encode(['doc'=>$docId,'uid'=>$_SESSION['user_id'],'ts'=>time()]));
$redirect = $authUrl
    . (strpos($authUrl,'?')===false ? '?' : '&')
    . 'response_type=code'
    . '&client_id=' . urlencode($clientId)
    . '&redirect_uri=' . urlencode($cb)
    . '&scope=' . urlencode('sign')
    . '&state=' . urlencode($state);

header('Location: ' . $redirect);
exit();
