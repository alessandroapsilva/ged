<?php
// core/init.php (ambiente seguro e pronto para produção)

// 1) Ambiente e relatórios de erro
date_default_timezone_set('America/Sao_Paulo');
$APP_ENV = getenv('APP_ENV') ?: getenv('GED_ENV') ?: 'development';
$IS_PROD = in_array(strtolower($APP_ENV), ['prod','production']);
if ($IS_PROD) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

define('PROJECT_ROOT', dirname(__DIR__));
// BASE_URL dinâmico para facilitar deploy em produção
if (!defined('BASE_URL')) {
    $baseFromEnv = getenv('GED_BASE_URL');
    if (!empty($baseFromEnv)) {
        define('BASE_URL', rtrim(str_replace('\\','/', $baseFromEnv), '/'));
    } else if (PHP_SAPI !== 'cli' && !empty($_SERVER['SCRIPT_NAME'])) {
        // Normaliza para esconder a pasta /public das URLs públicas
        // Ex.: /ged/public/esign/index.php -> BASE_URL = /ged
        $dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if (preg_match('#^(.*?)(/public)(?:/.*)?$#', $dir, $m)) {
            $dir = rtrim($m[1], '/');
        }
        define('BASE_URL', $dir === '/' ? '' : $dir);
    } else {
        // Fallback para desenvolvimento local padrão
        define('BASE_URL', '/ged');
    }
}

// 2) Sessão com cookies endurecidos
if (session_status() === PHP_SESSION_NONE) {
    $cookieSecure = (getenv('GED_COOKIE_SECURE') === '1') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (function_exists('session_set_cookie_params')) { session_set_cookie_params($cookieParams); }
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once PROJECT_ROOT . '/config.php';
$pdo = getDBConnection();
require_once PROJECT_ROOT . '/core/branding.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/log_helper.php';
// CSRF helper para formulários seguros
if (file_exists(PROJECT_ROOT . '/helpers/csrf_helper.php')) {
    require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
}
// Flags simples (podem vir de env)
if (!defined('ENABLE_SHARE_WATERMARK')) {
    define('ENABLE_SHARE_WATERMARK', (getenv('GED_SHARE_WATERMARK') === '0') ? false : true);
}

// 3) Cabeçalhos de segurança básicos
if (PHP_SAPI !== 'cli') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    if ($IS_PROD && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// 4) Atualiza contador de notificações (workflow/notificações internas) para o topo
try {
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $count = 0;
        // workflow_notificacoes (se existir)
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM workflow_notificacoes WHERE usuario_id = ? AND lida = FALSE");
            $stmt->execute([$uid]);
            $count += (int)$stmt->fetchColumn();
        } catch (Throwable $e) {}
        // notifications (tabela alternativa, se existir)
        try {
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
            $stmt2->execute([$uid]);
            $count += (int)$stmt2->fetchColumn();
        } catch (Throwable $e) {}
        $_SESSION['notification_count'] = $count;
    }
} catch (Throwable $e) {
    // silencioso para não afetar a navegação
}
?>