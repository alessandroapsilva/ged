<?php
// core/email.php - Serviço de e-mail com templates
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    // Autoload do Composer
    $composer = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($composer)) {
        require_once $composer;
    }
}

/**
 * Lê configuração SMTP das variáveis de ambiente ou defaults
 */
function ged_mailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    // Carrega configuração com a seguinte precedência: ENV > app_settings (DB) > constantes
    $pdo = $GLOBALS['pdo'] ?? null;
    $get = function(string $env, string $dbKey, $constVal, $default = '') use ($pdo) {
        $val = getenv($env);
        if ($val !== false && $val !== '') return $val;
        if ($pdo instanceof PDO) {
            try { $dbVal = app_setting_get($pdo, $dbKey, ''); if ($dbVal !== '') return $dbVal; } catch (Throwable $e) {}
        }
        return ($constVal !== null && $constVal !== '') ? $constVal : $default;
    };

    $host = $get('GED_SMTP_HOST', 'smtp_host', defined('SMTP_HOST') ? SMTP_HOST : '', '');
    $port = (int)$get('GED_SMTP_PORT', 'smtp_port', defined('SMTP_PORT') ? SMTP_PORT : 587, 587);
    $user = $get('GED_SMTP_USER', 'smtp_user', defined('SMTP_USER') ? SMTP_USER : '', '');
    $pass = $get('GED_SMTP_PASS', 'smtp_pass', defined('SMTP_PASS') ? SMTP_PASS : '', '');
    $secure = strtolower((string)$get('GED_SMTP_SECURE', 'smtp_secure', defined('SMTP_SECURE') ? SMTP_SECURE : 'tls', 'tls'));
    $from = $get('GED_MAIL_FROM', 'mail_from', defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost', 'no-reply@localhost');
    $fromName = $get('GED_MAIL_FROM_NAME', 'mail_from_name', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'GED', 'GED');
    $replyTo = $get('GED_REPLY_TO', 'reply_to', defined('REPLY_TO') ? REPLY_TO : '', '');
    $replyToName = $get('GED_REPLY_TO_NAME', 'reply_to_name', defined('REPLY_TO_NAME') ? REPLY_TO_NAME : '', '');

    if (!empty($host)) {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port ?: 587;
        // Habilita TLS automático quando possível (Exim/cPanel geralmente suporta)
        $mail->SMTPAutoTLS = true;
        if (!empty($user)) {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
        }
        // Correção automática comum: porta 587 -> TLS; porta 465 -> SSL
        if ($port == 587 && $secure === 'ssl') { $secure = 'tls'; }
        if ($port == 465 && $secure === 'tls') { $secure = 'ssl'; }
        if ($secure === 'ssl' || $secure === 'tls') {
            $mail->SMTPSecure = $secure;
        }
    }

    // Alguns provedores (cPanel/SMTP autenticado) exigem que o from seja o mesmo usuário autenticado.
    // Fallback: se MAIL_FROM não estiver definido, usa o próprio usuário SMTP como remetente.
    $effectiveFrom = !empty($from) ? $from : $user;
    if (!empty($effectiveFrom)) {
        $mail->setFrom($effectiveFrom, $fromName);
    }
    // Ajusta o envelope sender quando o domínio do FROM for diferente do usuário autenticado
    if (!empty($user)) {
        $fromDomain = strpos($effectiveFrom, '@') !== false ? substr(strrchr($effectiveFrom, '@'), 1) : '';
        $userDomain = strpos($user, '@') !== false ? substr(strrchr($user, '@'), 1) : '';
        if ($fromDomain !== '' && $userDomain !== '' && strcasecmp($fromDomain, $userDomain) !== 0) {
            // Define o envelope sender para evitar rejeições por política do servidor
            $mail->Sender = $user;
        }
    }
    if (!empty($replyTo)) {
        $mail->addReplyTo($replyTo, $replyToName !== '' ? $replyToName : $fromName);
    }
    return $mail;
}

/** Utilitário: obtém valor por caminho com dot notation em um array */
function _arr_get(array $arr, string $path, $default = '') {
    if ($path === '') return $default;
    $parts = explode('.', $path);
    $val = $arr;
    foreach ($parts as $p) {
        if (is_array($val) && array_key_exists($p, $val)) {
            $val = $val[$p];
        } else {
            return $default;
        }
    }
    return is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_UNICODE);
}

/** Renderiza placeholders {{chave}} ou {{chave|Default}} */
function render_placeholders(?string $text, array $data): string {
    if ($text === null) return '';
    return preg_replace_callback('/{{\s*([a-zA-Z0-9_\.]+)(\|([^}]+))?\s*}}/', function($m) use ($data){
        $key = $m[1] ?? '';
        $def = isset($m[3]) ? $m[3] : '';
        $val = _arr_get($data, $key, $def);
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }, $text);
}

/**
 * Carrega e renderiza um template por slug com dados
 * @return array{assunto:string, html:string, text:string}
 */
function email_render_template(PDO $pdo, string $slug, array $data = []): array {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE slug = ? AND ativo = 1");
    $stmt->execute([$slug]);
    $tpl = $stmt->fetch();
    if (!$tpl) {
        throw new RuntimeException("Template de e-mail não encontrado ou inativo: $slug");
    }
    $assunto = render_placeholders($tpl['assunto'] ?? '', $data);
    // Fallback: se não houver corpo_html/corpo_texto, usa coluna 'corpo'
    $rawHtml = $tpl['corpo_html'] ?? ($tpl['corpo'] ?? '');
    $html = render_placeholders($rawHtml, $data);
    $rawText = $tpl['corpo_texto'] ?? '';
    $text = $rawText !== '' ? render_placeholders($rawText, $data) : strip_tags($html);
    
    // Aplica template ENFAS GED (estilo eDok) sempre que não houver HTML completo
    $looksPlain = trim($rawHtml) === '' || strip_tags($rawHtml) === $rawHtml || strpos($rawHtml, '<html') === false;
    
    if ($looksPlain) {
        // Template único ENFAS GED - Estilo eDok
        $brand = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
        // URL absoluta para produção - ajusta automaticamente entre dev e prod
        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'ged.enfas.com.br');
        $logoUrl = $baseUrl . '/assets/dist/img/logo_enfasged.svg';
        $safeHtml = nl2br($html);
        
        $html = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{$assunto}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .email-wrapper { background: linear-gradient(135deg, #1d3441 0%, #2b3f4c 100%); min-height: 100vh; padding: 40px 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .email-header { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); padding: 32px 28px; text-align: center; border-bottom: 4px solid #1d4ed8; }
        .email-logo { max-width: 180px; height: auto; filter: brightness(0) invert(1); }
        .email-body { padding: 36px 28px; color: #1f2d3d; font-size: 15px; line-height: 1.7; }
        .email-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 20px 0; letter-spacing: -0.02em; }
        .email-content { color: #475569; }
        .email-button { display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; margin: 24px 0; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.3s ease; }
        .email-button:hover { box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4); transform: translateY(-1px); }
        .email-footer { background: #f8fafc; padding: 24px 28px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 13px; color: #64748b; }
        .email-footer a { color: #2563eb; text-decoration: none; font-weight: 500; }
        .email-footer a:hover { text-decoration: underline; }
        .divider { height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 20px 0; }
        .highlight-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 16px; border-radius: 6px; margin: 20px 0; }
        .info-text { font-size: 13px; color: #64748b; font-style: italic; margin-top: 16px; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { padding: 20px 10px; }
            .email-header { padding: 24px 20px; }
            .email-body { padding: 28px 20px; }
            .email-title { font-size: 20px; }
            .email-logo { max-width: 150px; }
            .email-button { padding: 12px 24px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header com Logo -->
            <div class="email-header">
                <img src="{$logoUrl}" alt="{$brand}" class="email-logo" onerror="this.style.display='none';">
            </div>
            
            <!-- Corpo do E-mail -->
            <div class="email-body">
                <h1 class="email-title">{$assunto}</h1>
                <div class="email-content">
                    {$safeHtml}
                </div>
            </div>
            
            <!-- Rodapé -->
            <div class="email-footer">
                <p style="margin: 0 0 8px 0;">
                    <strong>{$brand}</strong> - Sistema de Gestão Eletrônica de Documentos
                </p>
                <p style="margin: 0 0 12px 0; font-size: 12px;">
                    Este é um e-mail automático. Por favor, não responda.
                </p>
                <div class="divider"></div>
                <p style="margin: 8px 0 0 0; font-size: 12px;">
                    <a href="{$baseUrl}">Acessar Sistema</a> · 
                    <a href="{$baseUrl}/politica-privacidade">Política de Privacidade</a> · 
                    <a href="mailto:suporte@enfas.com.br">Suporte</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    return ['assunto' => $assunto, 'html' => $html, 'text' => $text];
}

/** Grava no log de e-mails */
function email_log(PDO $pdo, string $status, array $info): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO emails_log (template_slug, assunto, destinatario, remetente, status, erro, payload_json, usuario_id) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $info['template_slug'] ?? null,
            $info['assunto'] ?? null,
            $info['destinatario'] ?? '',
            $info['remetente'] ?? null,
            $status,
            $info['erro'] ?? null,
            isset($info['payload']) ? json_encode($info['payload'], JSON_UNESCAPED_UNICODE) : null,
            $info['usuario_id'] ?? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null)
        ]);
    } catch (Throwable $e) {
        error_log('Falha ao registrar emails_log: ' . $e->getMessage());
    }
}

/** Envia e-mail usando template */
function email_send_template(PDO $pdo, string $to, string $slug, array $data = [], array $options = []): bool {
    require_once __DIR__ . '/email.php';
    $render = email_render_template($pdo, $slug, $data);
    $mail = ged_mailer();
    if (!empty($options['smtp_debug'])) {
        // 0 = off, 1 = client msgs, 2 = client+server
        $mail->SMTPDebug = (int)$options['smtp_debug'];
    }
    $mail->Subject = $render['assunto'];
    $mail->Body = $render['html'];
    $mail->AltBody = $render['text'];
    $mail->addAddress($to);

    // opções extras
    if (!empty($options['cc']) && is_array($options['cc'])) {
        foreach ($options['cc'] as $cc) { $mail->addCC($cc); }
    }
    if (!empty($options['bcc']) && is_array($options['bcc'])) {
        foreach ($options['bcc'] as $bcc) { $mail->addBCC($bcc); }
    }
    if (!empty($options['attachments']) && is_array($options['attachments'])) {
        foreach ($options['attachments'] as $att) {
            if (is_array($att)) { $mail->addAttachment($att['path'], $att['name'] ?? ''); }
            elseif (is_string($att)) { $mail->addAttachment($att); }
        }
    }

    try {
        $ok = $mail->send();
        email_log($pdo, 'sucesso', [
            'template_slug' => $slug,
            'assunto' => $render['assunto'],
            'destinatario' => $to,
            'remetente' => $mail->From,
            'payload' => ['data' => $data, 'options' => $options]
        ]);
        if (function_exists('registrar_log')) {
            registrar_log($pdo, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null, "E-mail enviado para $to (template: $slug)", 'E-mail');
        }
        return $ok;
    } catch (Exception $ex) {
        email_log($pdo, 'falha', [
            'template_slug' => $slug,
            'assunto' => $render['assunto'],
            'destinatario' => $to,
            'remetente' => $mail->From ?? null,
            'erro' => $ex->getMessage(),
            'payload' => ['data' => $data, 'options' => $options]
        ]);
        if (function_exists('registrar_log')) {
            registrar_log($pdo, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null, "Falha ao enviar e-mail p/ $to (template: $slug) - " . $ex->getMessage(), 'E-mail');
        }
        return false;
    }
}

/** Pré-visualização (sem envio) */
function email_preview_template(PDO $pdo, string $slug, array $data = []): array {
    return email_render_template($pdo, $slug, $data);
}
// Fim do serviço de e-mail

/**
 * Compat: wrapper legado enviar_email($dest, $nome, $slug, $dados, $anexoPath=null, $anexoNome=null)
 * Redireciona para email_send_template mantendo compatibilidade com chamadas antigas.
 */
function enviar_email(string $to, string $name, string $slug, array $data = [], $attachmentPath = null, $attachmentName = null): bool {
    try {
        // Usa o $pdo global inicializado em core/init.php
        if (!isset($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
            throw new RuntimeException('PDO não inicializado.');
        }
        $pdo = $GLOBALS['pdo'];
        $options = [];
        if (!empty($attachmentPath) && is_string($attachmentPath)) {
            $options['attachments'] = [[ 'path' => $attachmentPath, 'name' => (string)($attachmentName ?: basename($attachmentPath)) ]];
        }
        // Alguns slugs legados podem ter nomes diferentes; normaliza quando possível
        $map = [
            'redefinir_senha' => 'recuperar_senha',
        ];
        if (isset($map[$slug])) { $slug = $map[$slug]; }
        return email_send_template($pdo, $to, $slug, $data, $options);
    } catch (Throwable $e) {
        error_log('enviar_email (compat) falhou: ' . $e->getMessage());
        return false;
    }
}

/** Obtém configuração simples do app (key/value) */
function app_setting_get(PDO $pdo, string $chave, string $default = ''): string {
    try {
        $st = $pdo->prepare('SELECT valor FROM app_settings WHERE chave = ?');
        $st->execute([$chave]);
        $row = $st->fetch();
        if ($row && isset($row['valor'])) return (string)$row['valor'];
    } catch (Throwable $e) { /* silencioso */ }
    return $default;
}

/** Define configuração simples do app (key/value) */
function app_setting_set(PDO $pdo, string $chave, string $valor): bool {
    try {
        // Protege chaves sensíveis: somente o "dono" pode alterar
        if (app_setting_is_protected($pdo, $chave) && !is_owner_mode()) {
            return false;
        }
        $st = $pdo->prepare('INSERT INTO app_settings (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        return $st->execute([$chave, $valor]);
    } catch (Throwable $e) { return false; }
}

/** Verifica se a chave está protegida (não-editável por usuários comuns) */
function app_setting_is_protected(PDO $pdo, string $chave): bool {
    static $defaultProtected = [
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure',
        'mail_from','mail_from_name','reply_to','reply_to_name'
    ];
    if (in_array($chave, $defaultProtected, true)) return true;
    try {
        // Tabela opcional com chaves protegidas
        $st = $pdo->prepare('SELECT 1 FROM app_settings_protected WHERE chave = ? LIMIT 1');
        $st->execute([$chave]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false; // se tabela não existir, usa apenas a lista padrão
    }
}

/** Determina se a execução está em modo proprietário (dono do sistema) */
function is_owner_mode(): bool {
    $token = getenv('GED_OWNER_TOKEN') ?: '';
    if ($token === '') return false;
    // Permite via header ou query string; também aceita em CLI (env)
    $hdr = $_SERVER['HTTP_X_OWNER_TOKEN'] ?? '';
    $qry = $_GET['owner'] ?? '';
    return hash_equals($token, (string)$hdr) || hash_equals($token, (string)$qry) || (PHP_SAPI === 'cli');
}