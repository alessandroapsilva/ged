<?php
// admin_owner_protect_smtp.php - Bootstrap seguro para definir/selar SMTP
// Acesso somente com token do dono: defina GED_OWNER_TOKEN no ambiente
// e chame este script com ?owner=TOKEN ou header X-Owner-Token: TOKEN

require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/email.php';

function forbid($msg='Acesso negado.') {
    http_response_code(403);
    echo '<h3 style="font-family:Arial">' . htmlspecialchars($msg) . '</h3>';
    exit;
}

$token = getenv('GED_OWNER_TOKEN') ?: '';
$provided = $_GET['owner'] ?? ($_SERVER['HTTP_X_OWNER_TOKEN'] ?? '');
if ($token === '' || !hash_equals($token, (string)$provided)) {
    forbid('Token ausente ou inválido. Configure GED_OWNER_TOKEN e use ?owner=TOKEN.');
}

// Cria tabela de proteção, se não existir
try {
    $GLOBALS['pdo']->exec("CREATE TABLE IF NOT EXISTS app_settings_protected (
        chave VARCHAR(190) PRIMARY KEY
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
} catch (Throwable $e) {
    forbid('Falha ao preparar estrutura: ' . $e->getMessage());
}

// Coleta valores preferindo ENV; se vazios, usa constantes
$val = function(string $env, $constVal, $fallback='') {
    $v = getenv($env);
    if ($v !== false && $v !== '') return $v;
    return ($constVal !== null && $constVal !== '') ? $constVal : $fallback;
};

$cfg = [
    'smtp_host'        => $val('GED_SMTP_HOST', defined('SMTP_HOST') ? SMTP_HOST : ''),
    'smtp_port'        => $val('GED_SMTP_PORT', defined('SMTP_PORT') ? SMTP_PORT : '587'),
    'smtp_user'        => $val('GED_SMTP_USER', defined('SMTP_USER') ? SMTP_USER : ''),
    'smtp_pass'        => $val('GED_SMTP_PASS', defined('SMTP_PASS') ? SMTP_PASS : ''),
    'smtp_secure'      => strtolower($val('GED_SMTP_SECURE', defined('SMTP_SECURE') ? SMTP_SECURE : 'tls', 'tls')),
    'mail_from'        => $val('GED_MAIL_FROM', defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost', 'no-reply@localhost'),
    'mail_from_name'   => $val('GED_MAIL_FROM_NAME', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'GED', 'GED'),
    'reply_to'         => $val('GED_REPLY_TO', defined('REPLY_TO') ? REPLY_TO : ''),
    'reply_to_name'    => $val('GED_REPLY_TO_NAME', defined('REPLY_TO_NAME') ? REPLY_TO_NAME : ''),
];

$protectedKeys = array_keys($cfg);

// Persiste valores e protege as chaves
$applied = [];
foreach ($cfg as $k => $v) {
    if ($v === '' && $k !== 'reply_to' && $k !== 'reply_to_name') continue; // permite reply_to vazio
    app_setting_set($GLOBALS['pdo'], $k, (string)$v); // owner mode permitido (token válido)
    $applied[$k] = $v;
    $st = $GLOBALS['pdo']->prepare('INSERT IGNORE INTO app_settings_protected (chave) VALUES (?)');
    $st->execute([$k]);
}

echo '<pre style="font-family:Consolas,monospace">';
echo "SMTP protegido com sucesso. Chaves definidas e seladas:\n\n";
foreach ($applied as $k=>$v) {
    if ($k === 'smtp_pass') { $v = str_repeat('*', max(8, strlen($v))); }
    echo str_pad($k, 18) . ' = ' . $v . "\n";
}
echo "\nObservação:\n- Para alterar futuramente, ajuste as variáveis de ambiente e reexecute este script com o token.\n- Sem o token correto, qualquer tentativa de edição será ignorada.\n";
echo '</pre>';
