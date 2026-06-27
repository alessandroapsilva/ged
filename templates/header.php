<?php
// templates/header.php (VERSÃO FINAL COM BARRA DE PESQUISA)
require_once __DIR__ . '/../core/init.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?></title>
    <?php if (defined('BRAND_PRIMARY_COLOR')): ?>
    <meta name="theme-color" content="<?= htmlspecialchars(BRAND_PRIMARY_COLOR) ?>">
    <meta name="msapplication-TileColor" content="<?= htmlspecialchars(BRAND_PRIMARY_COLOR) ?>">
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml" href="<?= defined('BRAND_FAVICON') ? BRAND_FAVICON : BASE_URL . '/assets/dist/img/logo_enfasged.svg' ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap">
    <?php
        $assetVer = (defined('APP_VERSION') ? APP_VERSION : '2.0.0') . '.' . (defined('APP_REVISION') ? APP_REVISION : 'local');
        // Define base de assets; força /ged se nada estiver configurado
        $assetBase = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if ($assetBase === '' || $assetBase === '/') {
            $assetBase = '/ged';
        }
    ?>
        <?php $assetBasePublic = $assetBase . '/public'; ?>
        <link rel="stylesheet" href="<?= $assetBasePublic ?>/assets/plugins/fontawesome-free/css/all.min.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBasePublic ?>/assets/dist/css/adminlte.min.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBasePublic ?>/assets/dist/css/ged_styles.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBase ?>/assets/dist/css/brand.css?v=<?= urlencode($assetVer) ?>">
        <!-- CSS Moderno -->
        <link rel="stylesheet" href="<?= $assetBase ?>/style.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBasePublic ?>/css/components.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBasePublic ?>/css/forms.css?v=<?= urlencode($assetVer) ?>">
        <link rel="stylesheet" href="<?= $assetBase ?>/assets/dist/css/modern-override.css?v=<?= urlencode($assetVer) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // Aplica o tema cinza (estilo eDok) o mais cedo possÃ­vel para evitar "flash" de tema padrÃ£o
        (function(){
            try {
                var theme = localStorage.getItem('theme');
                if (!theme) { // valor padrÃ£o: cinza
                    localStorage.setItem('theme', 'gray');
                    theme = 'gray';
                }
                if (theme === 'gray') {
                    // Marca no <html> para permitir CSS antecipado, e aplica no body assim que possÃ­vel
                    document.documentElement.classList.add('theme-gray');
                    document.addEventListener('DOMContentLoaded', function(){
                        document.body.classList.add('theme-gray');
                    });
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="hold-transition sidebar-mini theme-gray">
<div class="wrapper">
<nav class="main-header navbar navbar-expand navbar-white navbar-light navbar-sleek">
        <a href="painel_produtividade" class="navbar-brand d-none d-sm-inline-flex align-items-center">
            <img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : BASE_URL . '/assets/dist/img/logo_enfasged.svg' ?>"
                 alt="<?= defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED' ?>"
                 style="height:36px; width:auto; filter: drop-shadow(0 2px 6px rgba(0,0,0,.35));"
                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/dist/img/AdminLTELogo.png';">
        </a>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" title="Menu"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <form action="buscar" method="get" class="form-inline mx-auto d-none d-md-flex" style="max-width:560px; width:100%;">
            <div class="input-group input-group-sm w-100">
                <input class="form-control form-control-navbar" type="search" name="q" placeholder="Digite sua pesquisa e tecle enter..." aria-label="Pesquisar">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit" title="Buscar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <ul class="navbar-nav ml-auto align-items-center">
            <!-- Alternar Tema (lâmpada) -->
            <?php
            // Indicadores de Vencimento (documentos com data_vencimento)
            $vencidos_count = 0; $a_vencer_count = 0; $vencimentos_items = [];
            try {
                // Count vencidos
                $stmtV1 = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND data_vencimento IS NOT NULL AND data_vencimento < NOW()");
                $vencidos_count = (int)$stmtV1->fetchColumn();

                // Count a vencer (prÃ³ximos 30 dias)
                $stmtV2 = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND data_vencimento IS NOT NULL AND data_vencimento >= CURDATE() AND data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
                $a_vencer_count = (int)$stmtV2->fetchColumn();

                // Itens prÃ³ximos de vencer (5) + vencidos recentes (5)
                $stmtItems = $pdo->query(
                    "(SELECT id, titulo, data_vencimento, 0 AS exp FROM documentos WHERE apagado_em IS NULL AND data_vencimento IS NOT NULL AND data_vencimento >= CURDATE() AND data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY data_vencimento ASC LIMIT 5)
                     UNION ALL
                     (SELECT id, titulo, data_vencimento, 1 AS exp FROM documentos WHERE apagado_em IS NULL AND data_vencimento IS NOT NULL AND data_vencimento < CURDATE() ORDER BY data_vencimento DESC LIMIT 5)"
                );
                $vencimentos_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) { /* silencioso */ }
            ?>
            
            <!-- Simplificado ao padrÃ£o eDok: manter apenas notificaÃ§Ãµes, lÃ¢mpada, fullscreen e usuÃ¡rio -->
            <li class="nav-item dropdown">
                <?php 
                $notif_count = isset($_SESSION['notification_count']) ? (int)$_SESSION['notification_count'] : 0; 
                $notif_items = [];
                if (isset($_SESSION['user_id'])) {
                    try {
                        $stmtN = $pdo->prepare("SELECT id, tipo, mensagem, data_envio, lida FROM workflow_notificacoes WHERE usuario_id = ? ORDER BY data_envio DESC LIMIT 5");
                        $stmtN->execute([ (int)$_SESSION['user_id'] ]);
                        $notif_items = $stmtN->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Throwable $e) {}
                }
                ?>
                <a class="nav-link nav-pill" data-toggle="dropdown" href="#" title="NotificaÃ§Ãµes">
                    <i class="far fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="badge badge-warning navbar-badge"><?= $notif_count ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header"><?= $notif_count ?> NotificaÃ§Ãµes</span>
                    <div class="dropdown-divider"></div>
                    <?php if (!empty($notif_items)): ?>
                        <?php foreach ($notif_items as $n): ?>
                            <a href="notificacoes.php" class="dropdown-item">
                                <i class="fas fa-circle mr-2 <?= $n['lida'] ? 'text-muted' : 'text-primary' ?>"></i>
                                <?= htmlspecialchars(mb_strimwidth($n['mensagem'], 0, 60, 'â€¦', 'UTF-8')) ?>
                                <span class="float-right text-muted text-sm"><?= date('d/m H:i', strtotime($n['data_envio'])) ?></span>
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="notificacoes.php" class="dropdown-item dropdown-footer">
                        <i class="fas fa-history mr-2"></i> Ver atividades recentes
                    </a>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="#" id="theme-switch" role="button" title="Mudar Tema">
                    <i class="fas fa-fw fa-lightbulb"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button" title="Tela Cheia">
                    <i class="fas fa-fw fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <?php $___uname = trim((string)($_SESSION['user_name'] ?? ($_SESSION['usuario']['nome'] ?? 'UsuÃ¡rio'))); $___first = strtok($___uname, ' ') ?: $___uname; ?>
                    <span><?= htmlspecialchars($___first); ?> &nbsp;</span><i class="fas fa-chevron-down fa-xs"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="perfil.php" class="dropdown-item"><i class="fas fa-user-circle mr-2"></i> Meu Perfil</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt mr-2"></i> Sair</a>
                </div>
            </li>
        </ul>
    </nav>
    <script>
    (function(){
      function setTheme(mode){
        if(mode==='light'){
          document.body.classList.remove('dark-mode');
          localStorage.setItem('theme','light');
        } else {
          document.body.classList.add('dark-mode');
          localStorage.setItem('theme','dark');
        }
      }
      try{
        var saved = localStorage.getItem('theme');
        if(saved){ setTheme(saved); }
        var btn = document.getElementById('btn-toggle-theme');
        if(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); var isDark = document.body.classList.contains('dark-mode'); setTheme(isDark?'light':'dark'); }); }
      }catch(e){}
    })();
    </script>
        <?php if (!empty($BREADCRUMB) && is_array($BREADCRUMB)): ?>
        <div class="content-header py-2">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark" style="font-size:1.1rem;">
                            <?= htmlspecialchars($BREADCRUMB['title'] ?? '') ?>
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right" style="background:transparent;">
                            <?php foreach (($BREADCRUMB['items'] ?? []) as $bc): ?>
                                <li class="breadcrumb-item<?= !empty($bc['active']) ? ' active' : '' ?>">
                                    <?php if (!empty($bc['url']) && empty($bc['active'])): ?>
                                        <a href="<?= htmlspecialchars($bc['url']) ?>"><?= htmlspecialchars($bc['label'] ?? '') ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($bc['label'] ?? '') ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php
        // Dados para o modal Sobre (versÃ£o, revisÃ£o, pacotes)
        $APP_VERSION = defined('APP_VERSION') ? APP_VERSION : '2.0.0';
        $APP_REVISAO = substr(sha1(($APP_VERSION ?? '') . ' ' . (defined('BRAND_NAME') ? BRAND_NAME : 'GED') . ' ' . PHP_VERSION), 0, 7);

        // Leitura de packages do composer (mÃ¡ximo 100 itens para modal)
        $__packages = [];
        try {
            $vendorDir = realpath(__DIR__ . '/../vendor');
            if ($vendorDir && is_dir($vendorDir)) {
                foreach (glob($vendorDir . '/*/*/composer.json') as $composerFile) {
                    $data = json_decode((string)@file_get_contents($composerFile), true) ?: [];
                    if (!empty($data['name'])) {
                        $__packages[] = [
                            'name' => $data['name'],
                            'version' => $data['version'] ?? ($data['extra']['branch-alias']['dev-master'] ?? ''),
                            'license' => is_array($data['license'] ?? null) ? implode(', ', $data['license']) : ($data['license'] ?? 'N/D'),
                            'homepage' => $data['homepage'] ?? ($data['support']['source'] ?? ''),
                        ];
                    }
                    if (count($__packages) >= 100) break;
                }
                // Ordena alfabeticamente por nome
                usort($__packages, function($a,$b){return strcmp($a['name'],$b['name']);});
            }
        } catch (Throwable $e) { $__packages = []; }
    ?>
    <!-- Modal Sobre (estilo eDok) -->
    <div class="modal fade" id="modalSobre" tabindex="-1" role="dialog" aria-labelledby="modalSobreLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:12px;">
                <div class="modal-header border-0 d-block text-center">
                    <img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : BASE_URL . '/assets/dist/img/logo_enfasged.svg' ?>" alt="<?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>" style="height:56px; width:auto;">
                    <button type="button" class="close position-absolute" style="right:1rem;top:1rem;" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-0">
                    <ul class="nav nav-pills justify-content-center mb-3" id="sobreTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-sobre" data-toggle="pill" href="#painel-sobre" role="tab">Sobre</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-licencas" data-toggle="pill" href="#painel-licencas" role="tab">LicenÃ§as de Terceiros</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="sobreTabsContent">
                        <div class="tab-pane fade show active" id="painel-sobre" role="tabpanel" aria-labelledby="tab-sobre">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge badge-secondary mr-2" style="font-weight:600;">VERSÃƒO</div>
                                <div class="h5 mb-0 mr-3"><?= htmlspecialchars($APP_VERSION) ?></div>
                                <div class="badge badge-secondary mr-2" style="font-weight:600;">REVISÃƒO</div>
                                <div class="h5 mb-0"><code><?= htmlspecialchars($APP_REVISAO) ?></code></div>
                            </div>
                            <p class="text-justify">
                                <?= defined('BRAND_NAME') ? BRAND_NAME : 'Este sistema' ?> Ã© uma soluÃ§Ã£o de GestÃ£o EletrÃ´nica de Documentos com foco em
                                conformidade legal, seguranÃ§a e usabilidade. O uso do software e de sua documentaÃ§Ã£o estÃ¡ sujeito aos
                                termos de uso e Ã s polÃ­ticas internas da organizaÃ§Ã£o. Ã‰ proibida a engenharia reversa ou a cÃ³pia nÃ£o autorizada
                                do software, salvo nas hipÃ³teses permitidas pela legislaÃ§Ã£o aplicÃ¡vel.
                            </p>
                            <p class="text-justify">
                                Assinaturas digitais baseadas na ICP-Brasil sÃ£o reconhecidas com validade jurÃ­dica (MP 2.200-2/2001, Lei 14.063/2020) e
                                documentos digitalizados seguem os requisitos do Decreto 10.278/2020. As assinaturas PAdES sÃ£o compatÃ­veis com o
                                ecossistema Adobe (AATL).
                            </p>
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="small text-muted">
                                        <strong><?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?></strong><br>
                                        <?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Servidor local') ?><br>
                                        PHP <?= phpversion() ?> Â· <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Servidor') ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                    <a href="manual.php" class="btn btn-outline-primary btn-sm mr-2"><i class="fas fa-book mr-1"></i> Manual</a>
                                    <a href="privacidade.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-user-shield mr-1"></i> Privacidade</a>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="painel-licencas" role="tabpanel" aria-labelledby="tab-licencas">
                            <p class="mb-2">Abaixo estÃ£o listados os principais componentes de terceiros utilizados por este sistema, com suas versÃµes e licenÃ§as.</p>
                            <div class="table-responsive" style="max-height:320px;">
                                <table class="table table-sm table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Componente</th>
                                            <th>VersÃ£o</th>
                                            <th>LicenÃ§a</th>
                                            <th>Link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($__packages)): ?>
                                            <tr><td colspan="4" class="text-muted">Nenhum pacote detectado.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($__packages as $p): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($p['name']) ?></code></td>
                                                    <td><?= htmlspecialchars($p['version'] ?: '-') ?></td>
                                                    <td><?= htmlspecialchars($p['license']) ?></td>
                                                    <td>
                                                        <?php if (!empty($p['homepage'])): ?>
                                                            <a href="<?= htmlspecialchars($p['homepage']) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary"><i class="fas fa-external-link-alt"></i></a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <small class="text-muted">&copy; <?= date('Y') ?> <?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>. Todos os direitos reservados.</small>
                    <small class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?= date('d/m/Y') ?></small>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var abrir = document.getElementById('abrir-sobre-modal');
            if (abrir) abrir.addEventListener('click', function(e){ e.preventDefault(); $('#modalSobre').modal('show'); });

            // AlternÃ¢ncia de tema cinza (somente telas/mÃ³dulos)
            var themeSwitch = document.getElementById('theme-switch');
            if (themeSwitch) {
                themeSwitch.addEventListener('click', function(e){
                    e.preventDefault();
                    var isGray = document.body.classList.toggle('theme-gray');
                    // MantÃ©m a classe tambÃ©m no <html> para evitar piscadas em navegaÃ§Ã£o
                    if (isGray) document.documentElement.classList.add('theme-gray'); else document.documentElement.classList.remove('theme-gray');
                    try { localStorage.setItem('theme', isGray ? 'gray' : 'light'); } catch (err) {}
                });
            }

            // Flash toasts (SweetAlert2)
            try {
                <?php
                $___toast_js = '';
                // Forma 1: $_SESSION['flash'] = ['type'=>'success','message'=>'...']
                if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
                    $ftype = $_SESSION['flash']['type'] ?? 'info';
                    $fmsg = $_SESSION['flash']['message'] ?? '';
                    $___toast_js = "Swal.fire({toast:true,position:'top-end',timer:3000,showConfirmButton:false,icon:'" . addslashes($ftype) . "',title:'" . addslashes($fmsg) . "'});";
                    unset($_SESSION['flash']);
                } else {
                    // Forma 1b: $_SESSION['flash_message'] = ['type'=>'sucesso'|'erro'|...] mapeado para icon
                    if (!empty($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
                        $ftype = strtolower($_SESSION['flash_message']['type'] ?? 'info');
                        $icon = ($ftype === 'sucesso' || $ftype === 'success') ? 'success' : (($ftype === 'erro' || $ftype === 'error') ? 'error' : ($ftype === 'aviso' ? 'warning' : 'info'));
                        $fmsg = $_SESSION['flash_message']['text'] ?? ($_SESSION['flash_message']['message'] ?? '');
                        $___toast_js = "Swal.fire({toast:true,position:'top-end',timer:3500,showConfirmButton:false,icon:'" . addslashes($icon) . "',title:'" . addslashes($fmsg) . "'});";
                        unset($_SESSION['flash_message']);
                    }
                    // Forma 2: chaves separadas
                    $map = ['success','error','warning','info'];
                    foreach ($map as $m) {
                        $key = 'flash_' . $m;
                        if (!empty($_SESSION[$key])) {
                            $msg = (string)$_SESSION[$key];
                            $___toast_js .= "Swal.fire({toast:true,position:'top-end',timer:3000,showConfirmButton:false,icon:'$m',title:'" . addslashes($msg) . "'});";
                            unset($_SESSION[$key]);
                        }
                    }
                }
                echo $___toast_js;
                ?>
            } catch (e) {}
        });
    </script>
    <!-- JavaScript Moderno -->
    <script src="<?= BASE_URL ?>/public/js/app.js?v=<?= urlencode($assetVer) ?>" defer></script>
</head>
