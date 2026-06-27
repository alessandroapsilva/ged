<?php
// core/branding.php
// Configurações de branding com suporte a override via arquivo JSON (config/branding.json)

$brandingFile = PROJECT_ROOT . '/config/branding.json';
$branding = [];
if (is_file($brandingFile)) {
    try {
        $json = file_get_contents($brandingFile);
        $branding = json_decode($json, true) ?: [];
    } catch (Throwable $e) { $branding = []; }
}

$brandName = $branding['name'] ?? 'ENFAS GED';
$brandLogoCfg = $branding['logo'] ?? '';
$brandFaviconCfg = $branding['favicon'] ?? '';
// Normaliza caminho do logo: se vier relatif (ex.: '/assets/...'), prefixa BASE_URL
if (!empty($brandLogoCfg)) {
    if (strpos($brandLogoCfg, 'http://') === 0 || strpos($brandLogoCfg, 'https://') === 0) {
        $brandLogo = $brandLogoCfg;
    } else {
        $brandLogo = BASE_URL . '/' . ltrim(str_replace('public/', '', $brandLogoCfg), '/');
    }
} else {
    $brandLogo = BASE_URL . '/assets/dist/img/logo_enfasged.svg';
}

// Favicon: aceita absoluto ou relativo (prefixa BASE_URL quando relativo)
if (!empty($brandFaviconCfg)) {
    if (strpos($brandFaviconCfg, 'http://') === 0 || strpos($brandFaviconCfg, 'https://') === 0) {
        $brandFavicon = $brandFaviconCfg;
    } else {
        $brandFavicon = BASE_URL . '/' . ltrim(str_replace('public/', '', $brandFaviconCfg), '/');
    }
} else {
    $brandFavicon = $brandLogo; // fallback: usa o mesmo logo como ícone
}
$brandPrimary = $branding['primary_color'] ?? '#007bff';
$brandAccent = $branding['accent_color'] ?? '#28a745';
// Slogan opcional (pode vir de ENV ou do branding.json)
$brandSlogan = getenv('GED_SLOGAN') ?: ($branding['slogan'] ?? 'Sua gestão documental mais simples, segura e eficiente.');

// Versão/Build (opcional): pode vir de config/version.json ou variáveis de ambiente
$versionFile = PROJECT_ROOT . '/config/version.json';
$versionData = [];
if (is_file($versionFile)) {
    try { $versionData = json_decode((string)file_get_contents($versionFile), true) ?: []; } catch (Throwable $e) { $versionData = []; }
}
$appVersion  = getenv('GED_VERSION')  ?: ($versionData['version'] ?? '2.0.0');
$appRevision = getenv('GED_REVISION') ?: ($versionData['revision'] ?? 'local');
$appBuild    = getenv('GED_BUILD_DATE') ?: ($versionData['build_date'] ?? date('Y-m-d'));

if (!defined('BRAND_NAME')) { define('BRAND_NAME', $brandName); }
if (!defined('BRAND_LOGO')) { define('BRAND_LOGO', $brandLogo); }
if (!defined('BRAND_FAVICON')) { define('BRAND_FAVICON', $brandFavicon); }
if (!defined('BRAND_PRIMARY_COLOR')) { define('BRAND_PRIMARY_COLOR', $brandPrimary); }
if (!defined('BRAND_ACCENT_COLOR')) { define('BRAND_ACCENT_COLOR', $brandAccent); }
if (!defined('BRAND_SLOGAN')) { define('BRAND_SLOGAN', $brandSlogan); }
if (!defined('APP_VERSION')) { define('APP_VERSION', $appVersion); }
if (!defined('APP_REVISION')) { define('APP_REVISION', $appRevision); }
if (!defined('APP_BUILD_DATE')) { define('APP_BUILD_DATE', $appBuild); }
