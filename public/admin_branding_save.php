<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';

require_auth();
if (!(usuario_tem_permissao('admin.branding') || usuario_tem_permissao('admin.access'))) {
    header('Location: acesso_negado.php');
    exit();
}
if (!csrf_validate()) { http_response_code(403); exit('CSRF inválido'); }

$name = trim($_POST['name'] ?? '');
$primary = trim($_POST['primary_color'] ?? '');
$accent = trim($_POST['accent_color'] ?? '');

$errors = [];
if ($name === '') { $errors[] = 'Nome é obrigatório.'; }

$logoUrl = null;
if (!empty($_FILES['logo']['name'])) {
    $f = $_FILES['logo'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        // Valida tamanho (2MB)
        if ($f['size'] > 2*1024*1024) { $errors[] = 'Logo excede 2MB.'; }
        // Valida tipo
        $tmp = $f['tmp_name'];
        $info = @getimagesize($tmp);
        $mime = $info['mime'] ?? '';
        $allowed = ['image/png','image/jpeg','image/webp'];
        if (!in_array($mime, $allowed, true)) { $errors[] = 'Formato de logo não suportado. Use PNG/JPG/WEBP.'; }
        if (empty($errors)) {
            $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $dir = PROJECT_ROOT . '/public/uploads/branding';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $fname = 'logo_' . (function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid()) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $fname;
            if (@move_uploaded_file($tmp, $dest)) {
                // URL pública
                $logoUrl = BASE_URL . '/uploads/branding/' . $fname;
            } else {
                $errors[] = 'Falha ao salvar logo enviada.';
            }
        }
    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Falha no upload da logo (código ' . $f['error'] . ').';
    }
}

if (!empty($errors)) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => implode(' ', $errors)];
    header('Location: admin_branding.php');
    exit();
}

$brandingFile = PROJECT_ROOT . '/config/branding.json';
$branding = [];
if (is_file($brandingFile)) {
    try { $branding = json_decode(file_get_contents($brandingFile), true) ?: []; } catch (Throwable $e) {}
}
$branding['name'] = $name;
if ($primary) { $branding['primary_color'] = $primary; }
if ($accent) { $branding['accent_color'] = $accent; }
if ($logoUrl) { $branding['logo'] = $logoUrl; }

// Persiste como JSON (atomic write)
$tmpFile = $brandingFile . '.tmp';
@mkdir(dirname($brandingFile), 0775, true);
file_put_contents($tmpFile, json_encode($branding, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
@rename($tmpFile, $brandingFile);

// Audit log
try {
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
    $st->execute([(int)($_SESSION['user_id'] ?? 0), 'BRANDING_UPDATE', 'branding', 0, json_encode($branding, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {}

$_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Branding atualizado com sucesso.'];
header('Location: admin_branding.php');
exit();
