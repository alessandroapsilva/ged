<?php
// helpers/lgpd_helper.php - utilitários para LGPD & privacidade

function lgpd_get_setting(PDO $pdo, string $key, $default = '') {
    try {
        $st = $pdo->prepare('SELECT valor FROM app_settings WHERE chave = ?');
        $st->execute([$key]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r && isset($r['valor']) ? $r['valor'] : $default;
    } catch (Throwable $e) { return $default; }
}

function lgpd_set_setting(PDO $pdo, string $key, string $value): bool {
    try {
        $st = $pdo->prepare('INSERT INTO app_settings (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        return $st->execute([$key, $value]);
    } catch (Throwable $e) { return false; }
}

function lgpd_is_consent_required(PDO $pdo): bool {
    return lgpd_get_setting($pdo, 'LGPD_REQUIRE_CONSENT', '0') === '1';
}

function lgpd_policy_url(PDO $pdo): string {
    return (string)lgpd_get_setting($pdo, 'LGPD_POLICY_URL', '');
}

function lgpd_log_ips(PDO $pdo): bool {
    return lgpd_get_setting($pdo, 'LGPD_LOG_IPS', '1') === '1';
}

function lgpd_render_consent_checkbox(PDO $pdo): string {
    $required = lgpd_is_consent_required($pdo);
    $url = lgpd_policy_url($pdo);
    $label = 'Li e concordo com a Política de Privacidade';
    if (!empty($url)) {
        $label = 'Li e concordo com a <a href="'.htmlspecialchars($url).'" target="_blank">Política de Privacidade</a>';
    }
    return '<div class="form-group form-check mt-2">'
         . '<input type="checkbox" class="form-check-input" id="lgpd_consent" name="lgpd_consent" value="1"'
         . ($required ? ' required' : '') . '>'
         . '<label class="form-check-label" for="lgpd_consent">' . $label . '</label>'
         . '</div>';
}
