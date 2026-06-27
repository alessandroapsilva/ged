<?php
// public/sobre.php - Página Sobre (layout estilo eDok) com identidade própria
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Dados da aplicação
$APP_VERSION = defined('APP_VERSION') ? APP_VERSION : '2.0.0';
$APP_REVISAO = substr(sha1(($APP_VERSION ?? '') . ' ' . (defined('BRAND_NAME') ? BRAND_NAME : 'GED') . ' ' . PHP_VERSION), 0, 7);

// Pacotes composer (máx. 200)
$packages = [];
try {
    $vendorDir = realpath(__DIR__ . '/../vendor');
    if ($vendorDir && is_dir($vendorDir)) {
        foreach (glob($vendorDir . '/*/*/composer.json') as $composerFile) {
            $data = json_decode((string)@file_get_contents($composerFile), true) ?: [];
            if (!empty($data['name'])) {
                $packages[] = [
                    'name' => $data['name'],
                    'version' => $data['version'] ?? ($data['extra']['branch-alias']['dev-master'] ?? ''),
                    'license' => is_array($data['license'] ?? null) ? implode(', ', $data['license']) : ($data['license'] ?? 'N/D'),
                    'homepage' => $data['homepage'] ?? ($data['support']['source'] ?? ''),
                ];
            }
            if (count($packages) >= 200) break;
        }
        usort($packages, function($a,$b){ return strcmp($a['name'],$b['name']); });
    }
} catch (Throwable $e) { $packages = []; }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-info-circle"></i> Sobre</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade">Painel</a></li>
                        <li class="breadcrumb-item active">Sobre</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header text-center">
                            <img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : BASE_URL . '/assets/dist/img/logo_enfasged.svg' ?>" alt="<?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>" style="height:60px; width:auto;">
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-pills justify-content-center mb-3" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#sobre-tab" role="tab">Sobre</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#licencas-tab" role="tab">Licenças de Terceiros</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="sobre-tab" role="tabpanel">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="badge badge-secondary mr-2" style="font-weight:600;">VERSÃO</div>
                                        <div class="h5 mb-0 mr-3"><?= htmlspecialchars($APP_VERSION) ?></div>
                                        <div class="badge badge-secondary mr-2" style="font-weight:600;">REVISÃO</div>
                                        <div class="h5 mb-0"><code><?= htmlspecialchars($APP_REVISAO) ?></code></div>
                                    </div>
                                    <p class="text-justify">
                                        <?= defined('BRAND_NAME') ? BRAND_NAME : 'Este sistema' ?> é uma solução de Gestão Eletrônica de Documentos
                                        com foco em conformidade legal, segurança e usabilidade. O uso do software e documentação está sujeito aos
                                        termos internos da organização. É proibida a engenharia reversa não autorizada, salvo nas hipóteses legais.
                                    </p>
                                    <p class="text-justify mb-4">
                                        Assinaturas digitais ICP-Brasil possuem validade jurídica (MP 2.200-2/2001, Lei 14.063/2020) e
                                        documentos digitalizados seguem o Decreto 10.278/2020. Assinaturas PAdES são compatíveis com Adobe (AATL).
                                    </p>
                                    <div class="row">
                                        <div class="col-md-8 small text-muted">
                                            <strong><?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?></strong><br>
                                            <?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Servidor local') ?><br>
                                            PHP <?= phpversion() ?> · <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Servidor') ?>
                                        </div>
                                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                            <a href="manual" class="btn btn-outline-primary btn-sm mr-2"><i class="fas fa-book mr-1"></i> Manual</a>
                                            <a href="privacidade" class="btn btn-outline-secondary btn-sm"><i class="fas fa-user-shield mr-1"></i> Privacidade</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="licencas-tab" role="tabpanel">
                                    <p class="mb-2">Abaixo estão listados os componentes de terceiros utilizados por este sistema, com suas versões e licenças.</p>
                                    <div class="table-responsive" style="max-height:420px;">
                                        <table class="table table-sm table-hover">
                                            <thead class="thead-light"><tr><th>Componente</th><th>Versão</th><th>Licença</th><th>Link</th></tr></thead>
                                            <tbody>
                                                <?php if (empty($packages)): ?>
                                                    <tr><td colspan="4" class="text-muted">Nenhum pacote detectado.</td></tr>
                                                <?php else: foreach ($packages as $p): ?>
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
                                                <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">&copy; <?= date('Y') ?> <?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>. Todos os direitos reservados.</small>
                            <small class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?= date('d/m/Y') ?></small>
                        </div>
                    </div>
                </div>
            </div>
                            <table class="table table-sm table-borderless">
                                <tr><td class="font-weight-bold" style="width:40%">VersÃ£o:</td><td><?= $versao_sistema ?></td></tr>
                                <tr><td class="font-weight-bold">Data de lanÃ§amento:</td><td><?= $data_versao ?></td></tr>
                                <tr><td class="font-weight-bold">Ambiente:</td><td><span class="badge badge-<?= $ambiente === 'production' ? 'success' : 'warning' ?>"><?= strtoupper($ambiente) ?></span></td></tr>
                                <tr><td class="font-weight-bold">PHP:</td><td><?= phpversion() ?></td></tr>
                                <tr><td class="font-weight-bold">Servidor Web:</td><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/D' ?></td></tr>
                                <tr><td class="font-weight-bold">Sistema Operacional:</td><td><?= PHP_OS ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar text-success"></i> EstatÃ­sticas</h5>
                            <table class="table table-sm table-borderless">
                                <tr><td class="font-weight-bold" style="width:40%">Documentos:</td><td><?= number_format($stats['documentos'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">UsuÃ¡rios:</td><td><?= number_format($stats['usuarios'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Tipos de Documento:</td><td><?= number_format($stats['tipos'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Assinaturas Digitais:</td><td><?= number_format($stats['assinaturas'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Compartilhamentos Ativos:</td><td><?= number_format($stats['compartilhamentos'], 0, ',', '.') ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recursos do Sistema -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-star"></i> Principais Recursos</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="fas fa-folder-open"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">GestÃ£o de Documentos</span>
                                    <span class="info-box-number">Upload, organizaÃ§Ã£o e pesquisa avanÃ§ada</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-signature"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Assinaturas Digitais</span>
                                    <span class="info-box-number">ICP-Brasil com validade jurÃ­dica</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-calendar-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Controle de Vencimentos</span>
                                    <span class="info-box-number">Prazos legais automÃ¡ticos</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-share-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Compartilhamento Seguro</span>
                                    <span class="info-box-number">Links com senha e expiraÃ§Ã£o</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-danger"><i class="fas fa-shield-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">SeguranÃ§a</span>
                                    <span class="info-box-number">Hash SHA-256 e criptografia</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-secondary"><i class="fas fa-history"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Auditoria Completa</span>
                                    <span class="info-box-number">Logs de todas as aÃ§Ãµes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conformidade Legal -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-balance-scale"></i> Conformidade Legal</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold"><i class="fas fa-check-circle text-success"></i> LegislaÃ§Ã£o Brasileira</h6>
                            <ul class="list-unstyled ml-4">
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>MP 2.200-2/2001</strong> - ICP-Brasil</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Lei 14.063/2020</strong> - Assinaturas EletrÃ´nicas</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Decreto 10.278/2020</strong> - DigitalizaÃ§Ã£o</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Lei 13.709/2018</strong> - LGPD</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Lei 12.682/2012</strong> - Armazenamento EletrÃ´nico</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Lei 8.159/1991</strong> - PolÃ­tica Nacional de Arquivos</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold"><i class="fas fa-globe text-info"></i> PadrÃµes Internacionais</h6>
                            <ul class="list-unstyled ml-4">
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>ISO 15489</strong> - GestÃ£o de Documentos</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>ISO 27001</strong> - SeguranÃ§a da InformaÃ§Ã£o</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>PAdES</strong> - PDF Advanced Electronic Signatures</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>Adobe AATL</strong> - Assinaturas Reconhecidas</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>eIDAS</strong> - Compatibilidade Europa</li>
                                <li><i class="fas fa-angle-right text-muted mr-2"></i><strong>UETA/ESIGN</strong> - Compatibilidade EUA</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tecnologias -->
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code"></i> Tecnologias Utilizadas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <i class="fab fa-php fa-3x text-primary mb-2"></i>
                            <h6>PHP <?= phpversion() ?></h6>
                            <small class="text-muted">Backend</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <i class="fas fa-database fa-3x text-success mb-2"></i>
                            <h6>MySQL/MariaDB</h6>
                            <small class="text-muted">Banco de Dados</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <i class="fab fa-js fa-3x text-warning mb-2"></i>
                            <h6>JavaScript</h6>
                            <small class="text-muted">Frontend Interativo</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <i class="fab fa-bootstrap fa-3x text-info mb-2"></i>
                            <h6>Bootstrap 4 / AdminLTE</h6>
                            <small class="text-muted">Interface UI</small>
                        </div>
                    </div>
                    <hr>
                    <h6 class="font-weight-bold"><i class="fas fa-cube text-secondary"></i> Bibliotecas de Terceiros</h6>
                    <?php if (empty($packages)): ?>
                        <p class="text-muted">Nenhuma biblioteca de terceiros detectada.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Pacote</th>
                                        <th>DescriÃ§Ã£o</th>
                                        <th>LicenÃ§a</th>
                                        <th>Site</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($packages, 0, 10) as $pkg): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($pkg['name']) ?></code></td>
                                            <td><small><?= htmlspecialchars(mb_strimwidth($pkg['description'], 0, 60, 'â€¦')) ?></small></td>
                                            <td><span class="badge badge-light"><?= htmlspecialchars($pkg['license']) ?></span></td>
                                            <td>
                                                <?php if (!empty($pkg['homepage'])): ?>
                                                    <a href="<?= htmlspecialchars($pkg['homepage']) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (count($packages) > 10): ?>
                                <p class="text-center text-muted"><small>... e mais <?= count($packages) - 10 ?> pacote(s)</small></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CrÃ©ditos e LicenÃ§a -->
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-award"></i> CrÃ©ditos e LicenÃ§a</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Desenvolvimento</h6>
                            <p>
                                <strong><?= BRAND_NAME ?></strong> foi desenvolvido para atender Ã s necessidades de gestÃ£o eletrÃ´nica de documentos 
                                com foco em <strong>conformidade legal</strong>, <strong>seguranÃ§a</strong> e <strong>usabilidade</strong>.
                            </p>
                            <p class="mb-1"><strong>Desenvolvido por:</strong> Equipe <?= BRAND_NAME ?></p>
                            <p class="mb-1"><strong>Suporte:</strong> <a href="mailto:<?= SYSTEM_EMAIL ?>"><?= SYSTEM_EMAIL ?></a></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">LicenÃ§a de Uso</h6>
                            <p>
                                Este software Ã© fornecido "como estÃ¡", sem garantias de qualquer tipo. 
                                O uso Ã© restrito aos termos acordados com a organizaÃ§Ã£o.
                            </p>
                            <p class="mb-0">
                                <span class="badge badge-secondary">Copyright Â© <?= date('Y') ?> <?= BRAND_NAME ?></span>
                                <span class="badge badge-secondary ml-1">Todos os direitos reservados</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Links Ãšteis -->
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link"></i> Links Ãšteis</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="manual" class="btn btn-block btn-outline-primary">
                                <i class="fas fa-book mr-2"></i> Manual do UsuÃ¡rio
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="privacidade" class="btn btn-block btn-outline-info">
                                <i class="fas fa-user-shield mr-2"></i> PolÃ­tica de Privacidade
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="https://www.gov.br/iti" target="_blank" rel="noopener" class="btn btn-block btn-outline-success">
                                <i class="fas fa-external-link-alt mr-2"></i> ITI - ICP-Brasil
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>