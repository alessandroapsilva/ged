<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/share_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
// Watermark (carregado sob demanda mais abaixo)
// require_once PROJECT_ROOT . '/helpers/pdf_watermark.php';
require_once PROJECT_ROOT . '/helpers/image_watermark.php';
require_once PROJECT_ROOT . '/helpers/convert_helper.php';

// ---------- funções auxiliares locais ----------
function share_rate_limited(PDO $pdo, string $code, string $ip, int $maxPerMinute = 30): bool {
    try {
        $agora = date('Y-m-d H:i:s');
        $inicioJanela = date('Y-m-d H:i:s', time() - 60);
        $ident = 'share:' . $code;
        $ins = $pdo->prepare("INSERT INTO api_access_log (identificador, ip, criado_em) VALUES (?,?,?)");
        $ins->execute([$ident, $ip, $agora]);
        $sel = $pdo->prepare("SELECT COUNT(*) FROM api_access_log WHERE identificador = ? AND criado_em >= ?");
        $sel->execute([$ident, $inicioJanela]);
        $count = (int)$sel->fetchColumn();
        return $count > $maxPerMinute;
    } catch (Throwable $e) {
        return false;
    }
}

function atomic_increment_or_block(PDO $pdo, int $linkId): bool {
    // Incrementa apenas se não exceder o max_downloads (atômico)
    $sql = "UPDATE documento_links SET downloads = downloads + 1 WHERE id = ? AND (max_downloads IS NULL OR downloads < max_downloads)";
    $st = $pdo->prepare($sql);
    $st->execute([$linkId]);
    return $st->rowCount() > 0;
}

function safe_filename(string $name): string {
    $name = preg_replace('/[\\\/:*?"<>|]+/', '_', $name);
    $name = trim($name);
    if ($name === '') { $name = 'arquivo'; }
    return $name;
}

// ---------- fluxo principal ----------
$code = $_GET['code'] ?? '';
$senha = null;
$forceInline = isset($_GET['view']) && $_GET['view'] == '1'; // ?view=1 para visualizar inline
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $senha = $_POST['senha'] ?? '';
}

// Rate limit por código+IP (protege contra força bruta de senha e abuso de tráfego)
if (share_rate_limited($pdo, (string)$code, $clientIp)) {
    $retry = 60; // janela de 1 minuto
    http_response_code(429);
    header('Retry-After: ' . $retry);
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Muitas requisições</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:480px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
    echo '<div class="box"><h3>Limite excedido</h3><p>Tente novamente em instantes. Isso protege contra abuso e força bruta.</p></div>';
    echo '</body></html>';
    exit;
}

$auth = validar_link_e_autorizar($pdo, $code, $senha);
if (!$auth['ok'] && !empty($auth['requires_password'])) {
    // Form para senha (UI simples com estilo básico)
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Documento protegido</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}';
    echo '.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:420px;width:100%}';
    echo 'h3{margin-top:0;margin-bottom:12px}.muted{color:#6c757d;font-size:.9rem}input,button{width:100%;padding:10px 12px;margin-top:8px;border-radius:6px;border:1px solid #ced4da}button{background:#007bff;color:#fff;border:none;font-weight:600;cursor:pointer}button:hover{background:#0069d9}.err{color:#dc3545;margin-top:8px;font-size:.9rem}</style></head><body>';
    echo '<div class="box">';
    echo '<h3>Documento protegido</h3><div class="muted">Este link requer senha para acesso.</div>';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($senha)) {
        $msg = htmlspecialchars($auth['error'] ?? 'Senha inválida');
        echo '<div class="err">' . $msg . '</div>';
    }
    echo '<form method="post"><input type="hidden" name="code" value="' . htmlspecialchars($code) . '"><input type="password" name="senha" placeholder="Digite a senha" required> <button type="submit">Acessar</button></form>';
    if (defined('ENABLE_SHARE_WATERMARK') && ENABLE_SHARE_WATERMARK) {
        echo '<div class="muted" style="margin-top:10px;">Dica: a visualização inline adiciona marca d\'água para segurança.</div>';
    }
    echo '</div></body></html>';
    exit;
}
if (!$auth['ok']) {
    http_response_code(403);
    header('X-Robots-Tag: noindex, nofollow');
    $msg = htmlspecialchars($auth['error'] ?? 'Acesso negado');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Acesso negado</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:520px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
    echo '<div class="box"><h3>Acesso negado</h3><p>' . $msg . '</p></div>';
    echo '</body></html>';
    exit;
}

$link = $auth['link'];
$isViewOnly = !empty($link['view_only'] ?? 0);
$isForceWm = !empty($link['force_watermark'] ?? 0);
$forceInline = $forceInline || $isViewOnly; // forçar inline se link for somente visualização

$stmt = $pdo->prepare("SELECT titulo, caminho_arquivo, nome_arquivo_original FROM documentos WHERE id = ?");
$stmt->execute([$link['documento_id']]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) { http_response_code(404); exit('Documento não encontrado'); }

$path = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];
if (!is_file($path)) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Arquivo não encontrado</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:520px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
    echo '<div class="box"><h3>Arquivo ausente</h3><p>O arquivo deste link não está disponível.</p></div>';
    echo '</body></html>';
    exit;
}

// Para links somente visualização, bloquear formatos que não suportem exibição razoável inline
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($isViewOnly) {
    $inlineSupported = in_array($ext, ['pdf','jpg','jpeg','png','gif','webp','svg','txt','csv','json','xml']);
    if (!$inlineSupported) {
        http_response_code(415);
        header('X-Robots-Tag: noindex, nofollow');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Formato não suportado</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:540px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
        echo '<div class="box"><h3>Somente visualização</h3><p>Este link não permite download e o formato do arquivo não possui visualização segura pelo navegador.</p></div>';
        echo '</body></html>';
        exit;
    }
}

// Incremento atômico do contador respeitando max_downloads
if (!atomic_increment_or_block($pdo, (int)$link['id'])) {
    http_response_code(403);
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Limite atingido</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:520px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
    echo '<div class="box"><h3>Limite de downloads atingido</h3><p>Este link atingiu o máximo permitido.</p></div>';
    echo '</body></html>';
    exit;
}

// Auditoria/log
try {
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)");
    $st->execute([null, 'SHARE_DOWNLOAD', 'document', (int)$link['documento_id'], json_encode(['code'=>$code,'ua'=>($_SERVER['HTTP_USER_AGENT'] ?? null)], JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {}
if (function_exists('registrar_log')) { registrar_log($pdo, null, 'Download via link compartilhado', 'Compartilhamento', (int)$link['documento_id']); }

// Cabeçalhos e envio do arquivo
$orig = trim((string)($doc['nome_arquivo_original'] ?? ''));
$downloadName = safe_filename($orig !== '' ? $orig : ($doc['titulo'] . '.pdf'));

// Decide caminho a entregar (aplicar marca d'água conforme flags)
$deliverPath = $path;
$convertedForInline = false;

// Se for visualização inline (view=1) ou link somente visualização, tenta converter formatos não suportados para PDF
if ($forceInline || $isViewOnly) {
    $inlineSupported = in_array($ext, ['pdf','jpg','jpeg','png','gif','webp','svg','txt','csv','json','xml']);
    if (!$inlineSupported) {
        $cacheDir = PROJECT_ROOT . '/public/storage/converted';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
        $sig = @filesize($path) . '|' . @filemtime($path) . '|' . $ext;
        $hashSig = sha1($path . '|' . $sig);
        $cachePdf = $cacheDir . DIRECTORY_SEPARATOR . $hashSig . '.pdf';
        if (!file_exists($cachePdf)) {
            $tmpPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gedconv_shr_' . (function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid()) . '.pdf';
            if (convert_to_pdf_if_needed($path, $ext, $tmpPdf) && file_exists($tmpPdf)) {
                @rename($tmpPdf, $cachePdf);
                if (!file_exists($cachePdf)) { @copy($tmpPdf, $cachePdf); @unlink($tmpPdf); }
            }
        }
        if (file_exists($cachePdf)) {
            $deliverPath = $cachePdf;
            $ext = 'pdf';
            $convertedForInline = true;
        } else if ($isViewOnly) {
            // Se não foi possível converter e o link é somente visualização, bloqueia
            http_response_code(415);
            header('X-Robots-Tag: noindex, nofollow');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>Formato não suportado</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6f7}.box{background:#fff;padding:24px 28px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.08);max-width:540px;width:100%;text-align:center}h3{margin:0 0 8px}p{margin:0;color:#6c757d}</style></head><body>';
            echo '<div class="box"><h3>Somente visualização</h3><p>Não foi possível converter este formato para visualização segura no navegador.</p></div>';
            echo '</body></html>';
            exit;
        }
    }
}
$applyWatermark = ($ext === 'pdf') && (($forceInline && (defined('ENABLE_SHARE_WATERMARK') ? ENABLE_SHARE_WATERMARK : true)) || $isForceWm || $isViewOnly);
if ($applyWatermark) {
    try {
        require_once PROJECT_ROOT . '/helpers/pdf_watermark.php';
        $tmpOut = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gedwm_' . (function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid()) . '.pdf';
        $wmIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $wmText = 'Compartilhado • ' . date('Y-m-d H:i') . ' • ' . substr((string)$code, 0, 8) . ($wmIp ? (' • IP ' . $wmIp) : '');
        if (function_exists('aplicar_marcadagua_pdf') && aplicar_marcadagua_pdf($path, $tmpOut, $wmText, 0.15, 24)) {
            $deliverPath = $tmpOut;
            // Limpa o arquivo temporário ao final da requisição
            register_shutdown_function(function() use ($tmpOut) { if (is_file($tmpOut)) { @unlink($tmpOut); } });
        }
    } catch (Throwable $e) {
        // Em caso de erro na marca d'água, faz fallback silencioso para o arquivo original
    }
}

// Marca d'água para imagens quando visualização inline ou flags exigirem
if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && (($forceInline && (defined('ENABLE_SHARE_WATERMARK') ? ENABLE_SHARE_WATERMARK : true)) || $isForceWm || $isViewOnly)) {
    try {
        $tmpImg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gedwm_share_' . (function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid()) . '.' . $ext;
        $wmIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $wmText = 'Compartilhado • ' . date('Y-m-d H:i') . ' • ' . substr((string)$code, 0, 8) . ($wmIp ? (' • IP ' . $wmIp) : '');
        if (function_exists('aplicar_marcadagua_imagem') && aplicar_marcadagua_imagem($deliverPath, $tmpImg, $wmText, 0.15)) {
            $deliverPath = $tmpImg;
            register_shutdown_function(function() use ($tmpImg) { if (is_file($tmpImg)) { @unlink($tmpImg); } });
        }
    } catch (Throwable $e) { /* silencioso */ }
}

// Mime simples por extensão (fallback para octet-stream)
switch ($ext) {
    case 'pdf': $mime = 'application/pdf'; break;
    case 'jpg': case 'jpeg': $mime = 'image/jpeg'; break;
    case 'png': $mime = 'image/png'; break;
    case 'gif': $mime = 'image/gif'; break;
    case 'webp': $mime = 'image/webp'; break;
    case 'svg': $mime = 'image/svg+xml'; break;
    case 'txt': $mime = 'text/plain; charset=utf-8'; break;
    case 'csv': $mime = 'text/csv; charset=utf-8'; break;
    case 'json': $mime = 'application/json'; break;
    case 'xml': $mime = 'application/xml'; break;
    case 'docx': $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
    case 'xlsx': $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
    case 'pptx': $mime = 'application/vnd.openxmlformats-officedocument.presentationml.presentation'; break;
    case 'doc': $mime = 'application/msword'; break;
    case 'xls': $mime = 'application/vnd.ms-excel'; break;
    case 'ppt': $mime = 'application/vnd.ms-powerpoint'; break;
    case 'zip': $mime = 'application/zip'; break;
    case '7z': $mime = 'application/x-7z-compressed'; break;
    case 'rar': $mime = 'application/vnd.rar'; break;
    default: $mime = 'application/octet-stream'; break;
}

$filesize = filesize($deliverPath);

// Evita buffering indesejado
if (function_exists('ob_get_level')) { while (ob_get_level()) { ob_end_clean(); } }
header('Content-Description: File Transfer');
header('X-Download-Options: noopen');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Content-Type: ' . $mime);
header('Content-Length: ' . $filesize);
header('Accept-Ranges: none');
header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Robots-Tag: noindex, nofollow');
// Se convertemos para inline, ajusta o nome para .pdf quando aplicável
if ($forceInline && $ext === 'pdf' && $convertedForInline) {
    $downloadNameInline = preg_replace('/\.[^.]+$/', '', $downloadName) . '.pdf';
    header('Content-Disposition: inline; filename="' . $downloadNameInline . '"; filename*=UTF-8\'' . rawurlencode($downloadNameInline));
} else {
    header('Content-Disposition: ' . ($forceInline ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"; filename*=UTF-8\'' . rawurlencode($downloadName));
}

// Envia arquivo em blocos para reduzir uso de memória
$chunk = 8192;
$fp = fopen($deliverPath, 'rb');
if ($fp === false) { http_response_code(500); exit('Falha ao abrir o arquivo.'); }
ignore_user_abort(true);
set_time_limit(0);
while (!feof($fp)) {
    $buf = fread($fp, $chunk);
    if ($buf === false) { break; }
    echo $buf;
}
fclose($fp);
exit;
