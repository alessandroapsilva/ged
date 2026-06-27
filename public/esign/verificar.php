<?php
// public/esign/verificar.php (ENFAS GED - Verificação pública de assinatura)

require_once '../../core/init.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';

$codigo_verificador = $_GET['code'] ?? null;
$assinatura = null;
$erro = '';

if (empty($codigo_verificador)) {
    $erro = 'Nenhum código de verificação foi fornecido.';
} else {
    try {
        // 1) Tenta localizar na tabela nova documentos_assinaturas (detalhes JSON contendo o verificador)
        $sqlNew = "SELECT da.id, da.documento_id, da.data_assinatura, da.tipo_assinatura, da.detalhes, d.titulo
                   FROM documentos_assinaturas da
                   JOIN documentos d ON d.id = da.documento_id
                   WHERE da.detalhes LIKE ?
                   ORDER BY da.data_assinatura DESC
                   LIMIT 5";
        $stmt = $pdo->prepare($sqlNew);
        $stmt->execute(['%' . $codigo_verificador . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $r) {
            $det = json_decode($r['detalhes'] ?? '[]', true) ?: [];
            if (!empty($det['verificador']) && hash_equals($det['verificador'], $codigo_verificador)) {
                $mostrarIp = lgpd_log_ips($pdo);
                $assinatura = [
                    'nome_signatario' => $det['usuario_nome'] ?? ($det['certificado']['subject']['CN'] ?? 'Assinatura ICP-Brasil'),
                    'cpf_cnpj_signatario' => null,
                    'data_assinatura' => $r['data_assinatura'],
                    'ip_assinatura' => $mostrarIp ? ($det['ip'] ?? null) : null,
                    'verificador' => $det['verificador'],
                    'documento_id' => $r['documento_id'],
                    'titulo' => $r['titulo'],
                ];
                break;
            }
        }

        // 2) Se não achou na nova, tenta legado em `assinaturas`
        if (!$assinatura) {
            try {
                $sqlLegacy = "SELECT 
                                a.nome_signatario, a.data_assinatura, a.ip_assinatura, a.verificador,
                                COALESCE(dv.documento_id, a.documento_id) AS documento_id,
                                d.titulo
                              FROM assinaturas a
                              LEFT JOIN documento_versoes dv ON a.versao_id = dv.id
                              LEFT JOIN documentos d ON d.id = COALESCE(dv.documento_id, a.documento_id)
                              WHERE a.verificador = ?
                              ORDER BY a.data_assinatura DESC
                              LIMIT 1";
                $stl = $pdo->prepare($sqlLegacy);
                $stl->execute([$codigo_verificador]);
                $l = $stl->fetch(PDO::FETCH_ASSOC);
                if ($l) {
                    $mostrarIp = lgpd_log_ips($pdo);
                    $assinatura = [
                        'nome_signatario' => $l['nome_signatario'] ?: 'Assinatura eletrônica',
                        'cpf_cnpj_signatario' => null,
                        'data_assinatura' => $l['data_assinatura'],
                        'ip_assinatura' => $mostrarIp ? ($l['ip_assinatura'] ?? null) : null,
                        'verificador' => $l['verificador'],
                        'documento_id' => $l['documento_id'],
                        'titulo' => $l['titulo'] ?? 'Documento',
                    ];
                }
            } catch (Throwable $e) {
                // se tabela legado não existe/colunas diferentes, ignora
            }
        }

        if (!$assinatura) {
            $erro = 'Código de verificação inválido ou assinatura não encontrada.';
        }
    } catch (Throwable $e) {
        $erro = 'Ocorreu um erro ao consultar o banco de dados.';
        error_log('verificar.php erro: ' . $e->getMessage());
    }
}

// Carrega o template de cabeçalho
include '../../templates/header.php';

// Constrói a URL completa de verificação
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$url_completa = "$protocol://$host$uri";

?>

<style>
    :root {
        --brand-primary: <?= defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#007bff' ?>;
    }
    body {
        background-color: #2c3034 !important;
        color: #f8f9fa;
    }
    .main-sidebar, .main-header, .main-footer {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        background-color: #2c3034 !important;
        padding-top: 2rem;
    }
    .verification-container {
        max-width: 800px;
        margin: auto;
    }
    .brand-logo {
        max-width: 150px;
        margin-bottom: 2rem;
    }
    .details-section {
        background-color: #343a40;
        padding: 2rem;
        border-radius: 0.25rem;
    }
    .detail-row {
        display: flex;
        padding: 1rem 0;
        border-bottom: 1px solid #4f5962;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        flex-basis: 30%;
        font-weight: bold;
        color: #adb5bd;
    }
    .detail-value {
        flex-basis: 70%;
        word-break: break-all;
    }
    .detail-value small {
        display: block;
        color: #868e96;
        margin-top: 0.25rem;
    }
    .copy-btn {
        cursor: pointer;
    }
    /* Badge estilo eDok */
    .status-badge {
        display: inline-block;
        padding: .35rem .6rem;
        border-radius: .25rem;
        font-weight: 600;
    }
    .status-ok { background: rgba(40,167,69,.15); color: #28a745; border: 1px solid rgba(40,167,69,.35); }
    .status-fail { background: rgba(220,53,69,.15); color: #dc3545; border: 1px solid rgba(220,53,69,.35); }
    .cta-buttons .btn { margin-right: .5rem; }
</style>

<div class="content-wrapper">
    <div class="container-fluid verification-container text-center">
        <img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : (BASE_URL . '/assets/dist/img/logo_enfasged.svg') ?>" alt="<?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>" class="brand-logo">
    </div>

    <div class="container-fluid verification-container">

    <?php if ($assinatura): // SE ENCONTROU A ASSINATURA ?>

            <div class="alert alert-success" style="border-left: 6px solid var(--brand-primary);">
                <h4 class="mb-1 d-flex align-items-center justify-content-between">
                    <span><i class="icon fas fa-check-circle"></i> Assinatura localizada</span>
                    <span class="status-badge status-ok">Válida</span>
                </h4>
                <div class="small text-muted">Título: <?php echo htmlspecialchars($assinatura['titulo'] ?? 'Documento'); ?></div>
                <?php 
                    $viewUrl = BASE_URL . '/documentos_ver.php?id=' . (int)($assinatura['documento_id'] ?? 0);
                    $propUrl = BASE_URL . '/documentos_propriedades.php?id=' . (int)($assinatura['documento_id'] ?? 0);
                ?>
                <div class="mt-2 cta-buttons">
                    <a class="btn btn-sm btn-outline-light" target="_blank" href="<?= htmlspecialchars($viewUrl) ?>"><i class="fas fa-external-link-alt"></i> Visualizar Documento</a>
                    <a class="btn btn-sm btn-outline-light" target="_blank" href="<?= htmlspecialchars($propUrl) ?>"><i class="fas fa-file-alt"></i> Propriedades</a>
                </div>
            </div>

            <div class="details-section">
                <div class="detail-row">
                    <div class="detail-label">Nome</div>
                    <div class="detail-value"><?php echo htmlspecialchars($assinatura['nome_signatario']); ?></div>
                </div>
                <?php if (!empty($rows) && !empty($rows[0]['tipo_assinatura'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Tipo</div>
                    <div class="detail-value"><?php echo htmlspecialchars($rows[0]['tipo_assinatura']); ?></div>
                </div>
                <?php endif; ?>
                <?php if(!empty($assinatura['cpf_cnpj_signatario'])): ?>
                <div class="detail-row">
                    <div class="detail-label">CPF/CNPJ</div>
                    <div class="detail-value"><?php echo htmlspecialchars($assinatura['cpf_cnpj_signatario']); ?></div>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">Data e Hora da Assinatura</div>
                    <div class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($assinatura['data_assinatura'])); ?></div>
                </div>
                <?php 
                // Se houver dados de certificado na nova tabela, exibir (eDok-like)
                $cert = null; 
                if (!empty($rows)) {
                    foreach ($rows as $r) {
                        $detTmp = json_decode($r['detalhes'] ?? '[]', true) ?: [];
                        if (!empty($detTmp['certificado'])) { $cert = $detTmp['certificado']; break; }
                    }
                }
                if ($cert) { 
                    $cn = $cert['subject']['CN'] ?? null;
                    $issuer = $cert['issuer']['CN'] ?? null;
                    $validFrom = $cert['validFrom'] ?? null;
                    $validTo = $cert['validTo'] ?? null;
                    $expStatus = '';
                    if ($validTo) { $expStatus = (strtotime($validTo) >= time()) ? 'Válido' : 'Expirado'; }
                ?>
                <div class="detail-row">
                    <div class="detail-label">Certificado (Sujeito)</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($cn ?: '—'); ?>
                        <small><?= $issuer ? ('Emissor: ' . htmlspecialchars($issuer)) : '' ?></small>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Validade do Certificado</div>
                    <div class="detail-value">
                        <?php if ($validFrom): ?>De: <?= date('d/m/Y H:i', strtotime($validFrom)); ?><?php endif; ?>
                        <?php if ($validTo): ?> · Até: <?= date('d/m/Y H:i', strtotime($validTo)); ?><?php endif; ?>
                        <?php if ($expStatus): ?><small>Status: <?= htmlspecialchars($expStatus); ?></small><?php endif; ?>
                    </div>
                </div>
                <?php } ?>
                <?php if(!empty($assinatura['ip_assinatura'])): ?>
                <div class="detail-row">
                    <div class="detail-label">IP</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($assinatura['ip_assinatura']); ?>
                        <small>Endereço IP registrado na assinatura (exibido conforme a política de LGPD).</small>
                    </div>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">Verificador da Assinatura</div>
                    <div class="detail-value">
                        <code><?php echo htmlspecialchars($assinatura['verificador']); ?></code>
                        <small>Chave de verificação que confirma os dados informados na assinatura.</small>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">ID (chave) do Documento</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($assinatura['documento_id']); ?>
                        <small>Assinatura válida somente para o documento com este ID e chave de integridade.</small>
                    </div>
                </div>
                 <div class="detail-row">
                    <div class="detail-label">Link de Verificação</div>
                    <div class="detail-value">
                        <div class="input-group">
                            <input type="text" class="form-control" id="verification-link" value="<?php echo $url_completa; ?>" readonly>
                            <div class="input-group-append">
                                <span class="input-group-text copy-btn" onclick="copiarLink()" title="Copiar link">
                                    <i class="fas fa-copy"></i>
                                </span>
                            </div>
                        </div>
                         <small>Este é o link utilizado para verificar esta assinatura no futuro.</small>
                    </div>
                </div>
            </div>

        <?php else: // SE NÃO ENCONTROU A ASSINATURA ?>
            
            <div class="alert alert-danger" style="border-left: 6px solid #dc3545;">
                <h4 class="mb-1 d-flex align-items-center justify-content-between">
                    <span><i class="icon fas fa-times-circle"></i> Falha na verificação</span>
                    <span class="status-badge status-fail">Não encontrada</span>
                </h4>
                <div class="small text-muted">Não foi possível localizar uma assinatura para este código.</div>
            </div>

             <div class="details-section">
                <p class="lead"><?php echo htmlspecialchars($erro); ?></p>
                <hr style="border-top: 1px solid #4f5962;">
                <p>Por favor, verifique se o link ou o código de verificação foi copiado corretamente.</p>
                <?php if (!empty($codigo_verificador)): ?>
                    <p><strong>Código verificado:</strong> <code><?php echo htmlspecialchars($codigo_verificador); ?></code></p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
        
        <footer class="text-center mt-4 mb-4">
            <small>&copy; <?php echo date('Y'); ?> <?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?>. Todos os direitos reservados.</small>
        </footer>
    </div>
</div>

<script>
function copiarLink() {
    var copyText = document.getElementById('verification-link');
    if (!copyText) return;
    // Tenta Clipboard API moderna
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(copyText.value).then(function(){
            alert('Link de verificação copiado!');
        }).catch(function(){
            tryLegacyCopy(copyText);
        });
        return;
    }
    // Fallback
    tryLegacyCopy(copyText);
}
function tryLegacyCopy(el){
    try {
        el.select();
        el.setSelectionRange(0, 99999);
        document.execCommand('copy');
        alert('Link de verificação copiado!');
    } catch(e) {}
}
</script>

<?php
include '../../templates/footer.php';
?>