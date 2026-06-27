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

// Le configurações do provedor
function get($k,$def=''){ try{ $st=$GLOBALS['pdo']->prepare('SELECT valor FROM app_settings WHERE chave=?'); $st->execute([$k]); $r=$st->fetch(PDO::FETCH_ASSOC); return $r['valor'] ?? $def; }catch(Throwable $e){ return $def; } }
$base = get('CLOUD_ICP_BASE_URL','');
$cid = get('CLOUD_ICP_CLIENT_ID','');
$secret = get('CLOUD_ICP_CLIENT_SECRET','');
$cb = get('CLOUD_ICP_CALLBACK_URL','');
$tenant = get('CLOUD_ICP_TENANT','');

if ($base === '' || $cid === '' || $cb === '') {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Provedor ICP em nuvem não está configurado. Abra Administração > Configurar ICP.'];
    header('Location: index.php?id=' . $docId);
    exit();
}

// Observação: a integração real depende do provedor. Abaixo, deixamos um placeholder de OAuth/Autorização.
// Exemplo genérico de construção de URL de autorização:
$state = base64_encode(json_encode(['doc'=>$docId,'uid'=>$_SESSION['user_id'],'ts'=>time()]));
$authUrl = $base . '/oauth/authorize?response_type=code'
          . '&client_id=' . urlencode($cid)
          . '&redirect_uri=' . urlencode($cb)
          . '&scope=' . urlencode('sign')
          . '&state=' . urlencode($state)
          . ($tenant ? ('&tenant=' . urlencode($tenant)) : '');

header('Location: ' . $authUrl);
exit();