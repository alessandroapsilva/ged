<?php
// public/documentos_propriedades.php (Visual eDok 100% Completo)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// --- Função Auxiliar ---
function formatar_tamanho_arquivo($bytes) {
    if ($bytes >= 1048576) { return number_format($bytes / 1048576, 2) . ' MB'; }
    elseif ($bytes >= 1024) { return number_format($bytes / 1024, 2) . ' KB'; }
    elseif ($bytes > 0) { return $bytes . ' bytes'; }
    else { return '0 bytes'; }
}

// --- Busca de Dados ---
$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id === 0) { header('Location: documentos.php'); exit(); }

try {
    // 1. Documento Principal
    $sql = "SELECT d.*, t.nome as tipo_nome, u.nome as usuario_nome, p.nome as pasta_nome
            FROM documentos d
            LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            LEFT JOIN pastas p ON d.pasta_id = p.id
            WHERE d.id = ? AND d.apagado_em IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Documento não encontrado.'];
        header('Location: documentos.php');
        exit();
    }
    
    // 2. Metadados
    $meta_sql = "SELECT mc.rotulo AS chave, dm.valor
                 FROM documento_metadados dm
                 JOIN metadado_campos mc ON dm.campo_id = mc.id
                 WHERE dm.documento_id = ? ORDER BY mc.ordem ASC";
    $meta_stmt = $pdo->prepare($meta_sql);
    $meta_stmt->execute([$documento_id]);
    $metadados = $meta_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $verificador_origem = 'N/A';
    foreach ($metadados as $meta) {
        if (strtolower($meta['chave']) === 'checksum' || strtolower($meta['chave']) === 'hash') {
            $verificador_origem = htmlspecialchars($meta['valor']);
            break;
        }
    }

    // 3. Assinaturas (nova e legada)
    // Nova tabela com detalhes em JSON
    $assinaturas_novas = [];
    try {
        $stn = $pdo->prepare("SELECT id, documento_id, usuario_id, tipo_assinatura, data_assinatura, detalhes FROM documentos_assinaturas WHERE documento_id = ? ORDER BY data_assinatura DESC");
        $stn->execute([$documento_id]);
        $assinaturas_novas = $stn->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { $assinaturas_novas = []; }

    // Legado (sem detalhes ricos)
    $assinaturas_legado = [];
    try {
        $stl = $pdo->prepare("SELECT documento_id, usuario_id, nome_signatario, ip_assinatura, verificador, data_assinatura, status FROM assinaturas WHERE documento_id = ? AND status = 'assinado' ORDER BY data_assinatura DESC");
        $stl->execute([$documento_id]);
        $assinaturas_legado = $stl->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { $assinaturas_legado = []; }

    $total_icp = 0; $total_simples = 0;
    foreach ($assinaturas_novas as $a) {
        if (strcasecmp($a['tipo_assinatura'], 'ICP-Brasil') === 0) $total_icp++;
        if (strcasecmp($a['tipo_assinatura'], 'Simples') === 0) $total_simples++;
    }
    $total_assinaturas = count($assinaturas_novas) + count($assinaturas_legado);

    // 4. Verificação de Integridade
    $caminho_arquivo_fisico = PROJECT_ROOT . '/public/' . $documento['caminho_arquivo'];
    $tamanho_arquivo = 'Arquivo não encontrado';
    $status_integridade = ['texto' => 'Inválida', 'classe' => 'danger'];
    $hash_do_arquivo_armazenado = $documento['hash_arquivo'] ?? '';
    
    if (file_exists($caminho_arquivo_fisico)) {
        $tamanho_arquivo = formatar_tamanho_arquivo(filesize($caminho_arquivo_fisico));
        $hash_atual = hash_file('sha256', $caminho_arquivo_fisico);
        
        if (!empty($hash_do_arquivo_armazenado) && $hash_atual === $hash_do_arquivo_armazenado) {
            $status_integridade = ['texto' => 'Válida', 'classe' => 'success'];
        }
    }
    
    // 5. Nível de Conformidade (Lógica simples)
    $conformidade_nivel = 1;
    $conformidade_texto = "Metadados em conformidade, sem assinaturas";
    if ($total_assinaturas > 0) {
        // Se houver ICP, considerar nível superior
        if ($total_icp > 0) {
            $conformidade_nivel = 3;
            $conformidade_texto = "Metadados em conformidade, com assinatura digital ICP-Brasil";
        } else {
            $conformidade_nivel = 2;
            $conformidade_texto = "Metadados em conformidade, com assinaturas eletrônicas";
        }
    }

} catch (PDOException $e) { die("Erro ao carregar dados: " . $e->getMessage()); }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-9"><h3 class="m-0 text-truncate" title="<?= htmlspecialchars($documento['titulo']); ?>">Documento: <?= htmlspecialchars($documento['titulo']); ?></h3></div>
                <div class="col-sm-3"><a href="documentos.php?pasta_id=<?= $documento['pasta_id'] ?>" class="btn btn-secondary float-sm-right"><i class="fas fa-arrow-left"></i> Voltar</a></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (file_exists(__DIR__ . '/../templates/partials/notifications.php')) { include_once __DIR__ . '/../templates/partials/notifications.php'; } ?>
            <div class="row">
                <div class="col-lg-10 offset-lg-1">
                    <div class="card card-dark card-outline">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-12 text-center">
                                    <div class="btn-group btn-group-lg">
                                        <?php $vparam = !empty($documento['atualizado_em']) ? strtotime($documento['atualizado_em']) : time(); ?>
                                        <a href="documentos_ver.php?id=<?= $documento_id; ?>&v=<?= $vparam; ?>" class="btn btn-success" data-toggle="modal" data-target="#modal-visualizar" data-doc-id="<?= (int)$documento_id; ?>" data-doc-title="<?=htmlspecialchars($documento['titulo']);?>"><i class="fas fa-eye"></i> Visualizar</a>
                                        <a href="documentos_editar.php?id=<?= $documento_id; ?>" class="btn btn-warning"><i class="fas fa-pencil-alt"></i> Editar</a>
                                        <?php if (function_exists('usuario_tem_permissao') && usuario_tem_permissao('document.share')): ?>
                                            <a href="compartilhar_documento.php?id=<?= $documento_id; ?>" class="btn btn-secondary"><i class="fas fa-share-alt"></i> Compartilhar</a>
                                        <?php endif; ?>
                                        <a href="documentos_apagar.php?id=<?= $documento_id; ?>" class="btn btn-danger btn-apagar-swal"><i class="fas fa-trash"></i> Apagar</a>
                                    </div>
                                </div>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">ID</strong><div class="col-12 col-sm-9"><?= $documento['id']; ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Local</strong><div class="col-12 col-sm-9"><?= htmlspecialchars($documento['pasta_nome'] ?? 'Raiz'); ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Nome</strong><div class="col-12 col-sm-9"><?= htmlspecialchars($documento['titulo']); ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Descrição</strong><div class="col-12 col-sm-9"><?= !empty($documento['descricao']) ? nl2br(htmlspecialchars($documento['descricao'])) : 'N/A'; ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Origem</strong><div class="col-12 col-sm-9">Envio manual / Digitalização</div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Tipo do Documento</strong><div class="col-12 col-sm-9"><?= htmlspecialchars($documento['tipo_nome'] ?? 'N/A'); ?></div></div></li>
                                
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Metadados</strong>
                                        <div class="col-12 col-sm-9">
                                            <?php if (empty($metadados)): ?>
                                                Nenhum metadado definido.
                                            <?php else: ?>
                                                <table class="table table-striped table-hover table-sm mb-0">
                                                    <thead class="thead-light"><tr><th style="width: 5%; text-align: center;">Ordem</th><th style="width: 40%;">Chave</th><th>Valor</th></tr></thead>
                                                    <tbody>
                                                        <?php $order = 1; foreach ($metadados as $meta): ?>
                                                        <tr><td class="text-center text-muted"><?= $order++; ?></td><td><?= htmlspecialchars($meta['chave']); ?></td><td><?= htmlspecialchars($meta['valor']); ?></td></tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Assinaturas Eletrônicas</strong>
                                        <div class="col-12 col-sm-9">
                                            <?= $total_simples > 0 ? $total_simples . ' assinatura(s) simples' : 'Nenhuma'; ?>
                                            <small class="form-text text-muted">Assinaturas eletrônicas simples identificam o signatário e estão em conformidade com a <strong>Lei Nº 14.063/20</strong>, Art. 4º, I.</small>
                                        </div>
                                    </div>
                                </li>

                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Assinaturas Digitais</strong>
                                        <div class="col-12 col-sm-9">
                                            <?php if ($total_icp > 0): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover table-sm mb-2">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="width:6%;" class="text-center">Ordem</th>
                                                                <th style="width:30%;">Sujeito (CN)</th>
                                                                <th style="width:26%;">Emissor (CN)</th>
                                                                <th style="width:22%;">Validade</th>
                                                                <th style="width:6%;" class="text-center">Status</th>
                                                                <th style="width:10%;" class="text-right">Ação</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $icpOrdem = 1; foreach ($assinaturas_novas as $a): if (strcasecmp($a['tipo_assinatura'],'ICP-Brasil')!==0) continue; $det = json_decode($a['detalhes'] ?? '[]', true) ?: []; $cert = $det['certificado'] ?? null; $cn = $cert['subject']['CN'] ?? ($det['usuario_nome'] ?? '—'); $issuer = $cert['issuer']['CN'] ?? '—'; $vFrom = $cert['validFrom'] ?? null; $vTo = $cert['validTo'] ?? null; $ok = $vTo ? (strtotime($vTo) >= time()) : null; ?>
                                                            <tr>
                                                                <td class="text-center text-muted"><?= $icpOrdem++; ?></td>
                                                                <td title="<?= htmlspecialchars($cn); ?>"><?= htmlspecialchars(mb_strimwidth($cn, 0, 40, '…','UTF-8')); ?></td>
                                                                <td title="<?= htmlspecialchars($issuer); ?>" class="text-muted small"><?= htmlspecialchars(mb_strimwidth($issuer, 0, 34, '…','UTF-8')); ?></td>
                                                                <td>
                                                                    <?php if ($vFrom): ?>De: <?= date('d/m/Y', strtotime($vFrom)); ?><?php endif; ?>
                                                                    <?php if ($vTo): ?> · Até: <?= date('d/m/Y', strtotime($vTo)); ?><?php endif; ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <?php if ($ok === null): ?><span class="badge badge-secondary">N/D</span>
                                                                    <?php elseif ($ok): ?><span class="badge badge-success">Válido</span>
                                                                    <?php else: ?><span class="badge badge-danger">Expirado</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-right">
                                                                    <?php if (!empty($det['verification_url'])): ?>
                                                                        <a class="btn btn-xs btn-outline-primary" target="_blank" href="<?= htmlspecialchars($det['verification_url']); ?>" title="Verificar"><i class="fas fa-external-link-alt"></i></a>
                                                                    <?php elseif (!empty($det['verificador'])): $url = sprintf('%s://%s/ged/public/esign/verificar.php?code=%s', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on')?'https':'http', $_SERVER['HTTP_HOST'] ?? 'localhost', $det['verificador']); ?>
                                                                        <a class="btn btn-xs btn-outline-primary" target="_blank" href="<?= htmlspecialchars($url); ?>" title="Verificar"><i class="fas fa-external-link-alt"></i></a>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">—</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <small class="form-text text-muted">Assinaturas digitais com certificado ICP-Brasil atendem à <strong>Lei Nº 14.063/20</strong>, Art. 4º, II e III.</small>
                                            <?php else: ?>
                                                Nenhuma
                                                <small class="form-text text-muted">Assinaturas digitais com certificado ICP-Brasil atendem à <strong>Lei Nº 14.063/20</strong>, Art. 4º, II e III.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>

                                <?php if ($total_assinaturas > 0): ?>
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Detalhes das Assinaturas</strong>
                                        <div class="col-12 col-sm-9">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover table-sm mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Tipo</th>
                                                            <th>Signatário</th>
                                                            <th>Data</th>
                                                            <th>Verificador</th>
                                                            <th>Verificação</th>
                                                            <th>QR</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($assinaturas_novas as $a): $det = json_decode($a['detalhes'] ?? '[]', true) ?: []; ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($a['tipo_assinatura']); ?></td>
                                                            <td><?= htmlspecialchars($det['usuario_nome'] ?? ($det['certificado']['subject']['CN'] ?? 'N/D')); ?></td>
                                                            <td><?= date('d/m/Y H:i', strtotime($a['data_assinatura'])); ?></td>
                                                            <td><code><?= htmlspecialchars(substr(($det['verificador'] ?? ''), 0, 12)); ?>...</code></td>
                                                            <td>
                                                                <?php if (!empty($det['verification_url'])): ?>
                                                                    <a href="<?= htmlspecialchars($det['verification_url']); ?>" target="_blank" class="btn btn-xs btn-outline-primary">Abrir</a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($det['verification_url'])): ?>
                                                                    <img src="qrcode_generator.php?text=<?= urlencode($det['verification_url']); ?>" alt="QR" style="height:48px; width:48px;"/>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <?php foreach ($assinaturas_legado as $l): ?>
                                                        <tr>
                                                            <td><span class="badge badge-secondary">Legado</span></td>
                                                            <td><?= htmlspecialchars($l['nome_signatario'] ?: 'N/D'); ?></td>
                                                            <td><?= date('d/m/Y H:i', strtotime($l['data_assinatura'])); ?></td>
                                                            <td><code><?= htmlspecialchars(substr(($l['verificador'] ?? ''), 0, 12)); ?>...</code></td>
                                                            <td>
                                                                <?php if (!empty($l['verificador'])): $url = sprintf('%s://%s/ged/public/esign/verificar.php?code=%s', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on')?'https':'http', $_SERVER['HTTP_HOST'] ?? 'localhost', $l['verificador']); ?>
                                                                    <a href="<?= htmlspecialchars($url); ?>" target="_blank" class="btn btn-xs btn-outline-primary">Abrir</a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($l['verificador'])): ?>
                                                                    <img src="qrcode_generator.php?text=<?= urlencode($url); ?>" alt="QR" style="height:48px; width:48px;"/>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <li class="list-group-item" id="conformidade">
                                    <div class="row"><strong class="col-12 col-sm-3">Conformidade</strong>
                                        <div class="col-12 col-sm-9">
                                            <span class="badge badge-warning">Nível <?= $conformidade_nivel ?></span> <?= $conformidade_texto ?>
                                            <small class="form-text text-muted">Conforme <strong>Decreto Nº 10.278/20</strong>.</small>
                                        </div>
                                    </div>
                                </li>

                                <li class="list-group-item" id="integridade">
                                    <div class="row"><strong class="col-12 col-sm-3">Chave de Integridade</strong>
                                        <div class="col-12 col-sm-9 d-flex align-items-center flex-wrap">
                                            <span class="badge bg-<?= $status_integridade['classe'] ?>"><?= $status_integridade['texto'] ?></span>
                                            <code class="text-muted ml-2 mr-2" style="font-size: 0.8rem;"><?= htmlspecialchars(substr($hash_do_arquivo_armazenado, 0, 12)) ?>...</code>
                                            <button class="btn btn-xs btn-outline-secondary btn-copy mr-2" data-url="<?= htmlspecialchars($hash_do_arquivo_armazenado) ?>" title="Copiar hash"><i class="fas fa-copy"></i></button>
                                            <form method="post" action="documentos_verificar_integridade.php" class="d-inline">
                                                <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                                <input type="hidden" name="documento_id" value="<?= (int)$documento_id; ?>"/>
                                                <button type="submit" class="btn btn-xs btn-outline-primary" title="Reverificar"><i class="fas fa-sync-alt"></i> Reverificar</button>
                                            </form>
                                            <a href="documentos_integridade_exportar.php?id=<?= (int)$documento_id; ?>" class="btn btn-xs btn-outline-info ml-2" title="Exportar comprovante" target="_blank"><i class="fas fa-file-alt"></i> Exportar</a>
                                            <small class="form-text text-muted w-100">Esta é a chave de integridade do documento, verificada automaticamente. Se "Inválida", o arquivo foi modificado.</small>
                                        </div>
                                    </div>
                                </li>

                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Verificador de Origem</strong>
                                        <div class="col-12 col-sm-9 d-flex align-items-center flex-wrap">
                                            <code style="font-size: 0.8rem; word-break: break-all;" class="mr-2"><?= $verificador_origem ?></code>
                                            <?php if ($verificador_origem && $verificador_origem !== 'N/A'): ?>
                                            <button class="btn btn-xs btn-outline-secondary btn-copy" data-url="<?= htmlspecialchars($verificador_origem) ?>" title="Copiar verificador de origem"><i class="fas fa-copy"></i></button>
                                            <?php endif; ?>
                                            <small class="form-text text-muted w-100">Este é o verificador de origem do documento que consta nos metadados (se houver).</small>
                                        </div>
                                    </div>
                                </li>

                                <?php
                                // Bloco: Visualizações Internas (analytics simples)
                                $views = [];
                                try {
                                    $stV = $pdo->prepare("SELECT dv.user_id, u.nome AS usuario_nome, u.email AS usuario_email, COUNT(*) AS total, MAX(dv.created_at) AS ultimo
                                                           FROM document_views dv
                                                           LEFT JOIN usuarios u ON u.id = dv.user_id
                                                           WHERE dv.documento_id = ?
                                                           GROUP BY dv.user_id
                                                           ORDER BY ultimo DESC");
                                    $stV->execute([$documento_id]);
                                    $views = $stV->fetchAll(PDO::FETCH_ASSOC) ?: [];
                                } catch (Throwable $e) { $views = []; }
                                ?>
                                <div class="card mt-4" id="visualizacoes-internas">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-2"></i>Visualizações Internas</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if (empty($views)): ?>
                                            <div class="p-3 text-muted">Sem registros de visualização interna ainda.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover mb-0">
                                                    <thead class="thead-light"><tr>
                                                        <th>Usuário</th>
                                                        <th>E-mail</th>
                                                        <th style="width:14%">Última visualização</th>
                                                        <th style="width:10%" class="text-center">Total</th>
                                                    </tr></thead>
                                                    <tbody>
                                                    <?php foreach ($views as $v): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($v['usuario_nome'] ?? ('#' . (int)$v['user_id'])) ?></td>
                                                            <td class="text-monospace small text-muted"><?= htmlspecialchars($v['usuario_email'] ?? '—') ?></td>
                                                            <td><?= $v['ultimo'] ? date('d/m/Y H:i', strtotime($v['ultimo'])) : '—' ?></td>
                                                            <td class="text-center"><?= (int)$v['total'] ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Verificador de Arquivamento</strong>
                                        <div class="col-12 col-sm-9 d-flex align-items-center flex-wrap">
                                            <code style="font-size: 0.8rem; word-break: break-all;" class="mr-2"><?= htmlspecialchars($hash_do_arquivo_armazenado) ?></code>
                                            <?php if ($hash_do_arquivo_armazenado): ?>
                                            <button class="btn btn-xs btn-outline-secondary btn-copy" data-url="<?= htmlspecialchars($hash_do_arquivo_armazenado) ?>" title="Copiar verificador"><i class="fas fa-copy"></i></button>
                                            <?php endif; ?>
                                            <small class="form-text text-muted w-100">Este é o verificador (SHA-256) do documento arquivado. Ao baixar o documento, o verificador gerado em seu computador deve ser IDÊNTICO a este.</small>
                                        </div>
                                    </div>
                                </li>

                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Tamanho</strong><div class="col-12 col-sm-9"><?= $tamanho_arquivo; ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Quantidade de Páginas</strong><div class="col-12 col-sm-9"><?= htmlspecialchars($documento['quantidade_paginas'] ?? 'N/A'); ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Proprietário</strong><div class="col-12 col-sm-9"><?= htmlspecialchars($documento['usuario_nome'] ?? 'Desconhecido'); ?></div></div></li>
                                <li class="list-group-item"><div class="row"><strong class="col-12 col-sm-3">Criado</strong><div class="col-12 col-sm-9"><?= date('d/m/Y H:i', strtotime($documento['data_upload'])); ?></div></div></li>
                                
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Atualizado</strong>
                                        <div class="col-12 col-sm-9"><?= !empty($documento['atualizado_em']) ? date('d/m/Y H:i', strtotime($documento['atualizado_em'])) : ( !empty($documento['data_atualizacao']) ? date('d/m/Y H:i', strtotime($documento['data_atualizacao'])) : 'N/A' ); ?></div>
                                    </div>
                                </li>
                                <?php if (defined('ENABLE_VERSIONING') && !ENABLE_VERSIONING): ?>
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Histórico de Versões</strong>
                                        <div class="col-12 col-sm-9 text-muted">Desativado. Este ambiente está configurado para substituição direta (estilo eDok). Ative definindo <code>GED_ENABLE_VERSIONING=1</code> se quiser manter snapshots.</div>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item">
                                    <div class="row"><strong class="col-12 col-sm-3">Vencimento</strong>
                                        <div class="col-12 col-sm-9">
                                            <?php if (!empty($documento['data_vencimento'])): 
                                                $dataVenc = new DateTime($documento['data_vencimento']);
                                                $agora = new DateTime();
                                                $diff = $agora->diff($dataVenc);
                                                $textoVencimento = $diff->y > 0 ? " (em " . $diff->y . " anos)" : ($diff->m > 0 ? " (em " . $diff->m . " meses)" : " (em " . $diff->d . " dias)");
                                                if ($diff->invert) $textoVencimento = " (Vencido)";
                                            ?>
                                                <?= date('d/m/Y', strtotime($documento['data_vencimento'])); ?>
                                                <span class="text-muted"><?= $textoVencimento; ?></span>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <?php
                            // Bloco: Links de Compartilhamento
                            $links = [];
                            try {
                                $sqlLinks = "SELECT dl.*, u.nome AS criador_nome FROM documento_links dl LEFT JOIN usuarios u ON u.id = dl.created_by WHERE dl.documento_id = ? ORDER BY dl.created_at DESC";
                                $stLinks = $pdo->prepare($sqlLinks);
                                $stLinks->execute([$documento_id]);
                                $links = $stLinks->fetchAll(PDO::FETCH_ASSOC) ?: [];
                            } catch (Throwable $e) { $links = []; }
                            $temPermCompartilhar = function_exists('usuario_tem_permissao') ? usuario_tem_permissao('document.share') : true;
                            ?>

                            <div class="card mt-4" id="links-share">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-link mr-2"></i>Links de Compartilhamento</h5>
                                    <?php if ($temPermCompartilhar): ?>
                                        <a href="compartilhar_documento.php?id=<?= $documento_id; ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>Novo Link</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($links)): ?>
                                        <div class="p-3 text-muted">Nenhum link criado para este documento.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="width:24%">Link</th>
                                                        <th style="width:10%" class="text-center">Downloads</th>
                                                        <th style="width:14%">Expira em</th>
                                                        <th style="width:12%">Último acesso</th>
                                                        <th style="width:10%" class="text-center">24h</th>
                                                        <th style="width:8%" class="text-center">Senha</th>
                                                        <th style="width:12%">Criado em</th>
                                                        <th style="width:6%">Por</th>
                                                        <th style="width:6%" class="text-right">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on') ? 'https' : 'http';
                                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                                $base = rtrim($scheme . '://' . $host . BASE_URL, '/');
                                                foreach ($links as $lk):
                                                    $absUrl = $base . '/compartilhar_download.php?code=' . urlencode($lk['code']) . '&view=1';
                                                    // Estatísticas rápidas do acesso no api_access_log
                                                    $ident = 'share:' . $lk['code'];
                                                    $ultimo = null; $ultLbl = '<span class="text-muted">—</span>';
                                                    $tot24 = 0;
                                                    try {
                                                        $stLA = $pdo->prepare("SELECT MAX(criado_em) FROM api_access_log WHERE identificador = ?");
                                                        $stLA->execute([$ident]);
                                                        $ultimo = $stLA->fetchColumn();
                                                        if ($ultimo) { $ultLbl = date('d/m/Y H:i', strtotime($ultimo)); }
                                                        $stC = $pdo->prepare("SELECT COUNT(*) FROM api_access_log WHERE identificador = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
                                                        $stC->execute([$ident]);
                                                        $tot24 = (int)$stC->fetchColumn();
                                                    } catch (Throwable $e) {}
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="small text-monospace text-truncate" title="<?= htmlspecialchars($absUrl); ?>"><?= htmlspecialchars($absUrl); ?></div>
                                                            <?php if (!empty($lk['view_only'] ?? 0)): ?>
                                                                <span class="badge badge-primary mt-1">Somente visualização</span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($lk['force_watermark'] ?? 0)): ?>
                                                                <span class="badge badge-warning mt-1">Marca d'água forçada</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?= (int)$lk['downloads']; ?><?= $lk['max_downloads'] ? ' / ' . (int)$lk['max_downloads'] : ''; ?>
                                                        </td>
                                                        <td>
                                                            <?= !empty($lk['expires_at']) ? date('d/m/Y H:i', strtotime($lk['expires_at'])) : '<span class="text-muted">Sem expiração</span>'; ?>
                                                        </td>
                                                        <td><?= $ultLbl; ?></td>
                                                        <td class="text-center"><?= $tot24; ?></td>
                                                        <td class="text-center">
                                                            <?= !empty($lk['password_hash']) ? '<span class="badge badge-info">Sim</span>' : '<span class="text-muted">Não</span>'; ?>
                                                        </td>
                                                        <td><?= date('d/m/Y H:i', strtotime($lk['created_at'])); ?></td>
                                                        <td><?= htmlspecialchars($lk['criador_nome'] ?? '—'); ?></td>
                                                        <td class="text-right">
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-secondary btn-copy" data-url="<?= htmlspecialchars($absUrl); ?>" title="Copiar"><i class="fas fa-copy"></i></button>
                                                                <button class="btn btn-outline-info btn-qr" data-url="<?= htmlspecialchars($absUrl); ?>" title="QR Code"><i class="fas fa-qrcode"></i></button>
                                                                <?php if ($temPermCompartilhar): ?>
                                                                <form method="post" action="compartilhar_revogar.php" class="d-inline form-revogar">
                                                                    <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                                                    <input type="hidden" name="documento_id" value="<?= (int)$documento_id; ?>"/>
                                                                    <input type="hidden" name="link_id" value="<?= (int)$lk['id']; ?>"/>
                                                                    <button type="submit" class="btn btn-outline-danger" title="Revogar"><i class="fas fa-ban"></i></button>
                                                                </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php
                            // Bloco: Atividade (7 dias) - linhas para Interno e Links
                            // Gera sequência de dias (D-6 .. D0)
                            $dias = [];
                            for ($i = 6; $i >= 0; $i--) { $dias[] = date('Y-m-d', strtotime("-{$i} day")); }

                            // Interno (document_views)
                            $mapInterno = array_fill_keys($dias, 0);
                            try {
                                $stI = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM document_views WHERE documento_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
                                $stI->execute([$documento_id]);
                                foreach ($stI->fetchAll(PDO::FETCH_ASSOC) as $r) { $mapInterno[$r['d']] = (int)$r['c']; }
                            } catch (Throwable $e) {}

                            // Links (api_access_log por identificador share:code)
                            $mapLinks = array_fill_keys($dias, 0);
                            if (!empty($links)) {
                                $codes = array_filter(array_map(function($lk){ return $lk['code'] ?? null; }, $links));
                                if (!empty($codes)) {
                                    // Monta placeholders e consulta agregando por dia
                                    $ids = array_map(function($c){ return 'share:' . $c; }, $codes);
                                    $place = implode(',', array_fill(0, count($ids), '?'));
                                    $params = $ids;
                                    $sqlAL = "SELECT DATE(criado_em) d, COUNT(*) c FROM api_access_log WHERE identificador IN ($place) AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(criado_em)";
                                    try {
                                        $stL = $pdo->prepare($sqlAL);
                                        $stL->execute($params);
                                        foreach ($stL->fetchAll(PDO::FETCH_ASSOC) as $r) { $mapLinks[$r['d']] = (int)$r['c']; }
                                    } catch (Throwable $e) {}
                                }
                            }
                            $labelsJs = json_encode(array_map(function($d){ return date('d/m', strtotime($d)); }, $dias));
                            $internoJs = json_encode(array_values($mapInterno));
                            $linksJs = json_encode(array_values($mapLinks));
                            ?>
                            <div class="card mt-4" id="atividade-7d">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-chart-line mr-2"></i>Atividade (últimos 7 dias)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart-atividade" height="90"></canvas>
                                </div>
                            </div>
                            <script>
                            (function(){
                                try {
                                    const ctx = document.getElementById('chart-atividade');
                                    if (!ctx) return;
                                    const labels = <?= $labelsJs ?>;
                                    const interno = <?= $internoJs ?>;
                                    const links = <?= $linksJs ?>;
                                    new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels,
                                            datasets: [
                                                { label: 'Interno', data: interno, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.1)', tension: 0.25, fill: true },
                                                { label: 'Links', data: links, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension: 0.25, fill: true }
                                            ]
                                        },
                                        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, precision: 0 } } }
                                    });
                                } catch (e) {}
                            })();
                            </script>

                            <?php
                            // Bloco: Compartilhamentos internos por usuário
                            require_once PROJECT_ROOT . '/helpers/share_user_helper.php';
                            $shares = [];
                            try { $shares = share_user_list($pdo, $documento_id); } catch (Throwable $e) { $shares = []; }
                            ?>
                            <div class="card mt-4" id="shares-internos">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-user-friends mr-2"></i>Compartilhamentos Internos</h5>
                                    <?php if ($temPermCompartilhar): ?>
                                        <a href="compartilhar_usuario.php?id=<?= $documento_id; ?>" class="btn btn-sm btn-primary"><i class="fas fa-user-plus mr-1"></i>Novo Compartilhamento</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($shares)): ?>
                                        <div class="p-3 text-muted">Nenhum usuário com acesso específico a este documento.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="thead-light"><tr>
                                                    <th>Usuário</th>
                                                    <th>E-mail</th>
                                                    <th style="width:18%">Permissões</th>
                                                    <th style="width:14%">Expira em</th>
                                                    <th style="width:14%">Concedido em</th>
                                                    <th style="width:12%">Por</th>
                                                    <th style="width:10%" class="text-right">Ações</th>
                                                </tr></thead>
                                                <tbody>
                                                <?php foreach ($shares as $s): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($s['usuario_nome'] ?? ('#' . (int)$s['user_id'])); ?><?= !empty($s['revoked_at']) ? ' <span class="badge badge-secondary ml-1">Revogado</span>' : '' ?></td>
                                                        <td class="text-monospace small text-muted"><?= htmlspecialchars($s['usuario_email'] ?? '—'); ?></td>
                                                        <td>
                                                            <?php if (!empty($s['view_only'])): ?><span class="badge badge-primary mr-1">Somente visualização</span><?php endif; ?>
                                                            <?php if (!empty($s['can_download'])): ?><span class="badge badge-success">Download</span><?php else: ?><span class="badge badge-secondary">Sem download</span><?php endif; ?>
                                                        </td>
                                                        <td><?= !empty($s['expires_at']) ? date('d/m/Y H:i', strtotime($s['expires_at'])) : '<span class="text-muted">—</span>'; ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($s['created_at'])); ?></td>
                                                        <td><?= htmlspecialchars($s['concedente_nome'] ?? '—'); ?></td>
                                                        <td class="text-right">
                                                            <?php if (empty($s['revoked_at']) && $temPermCompartilhar): ?>
                                                            <form method="post" action="compartilhar_usuario_toggle.php" class="d-inline mr-1">
                                                                <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                                                <input type="hidden" name="documento_id" value="<?= (int)$documento_id; ?>" />
                                                                <input type="hidden" name="share_id" value="<?= (int)$s['id']; ?>" />
                                                                <input type="hidden" name="field" value="view_only" />
                                                                <input type="hidden" name="value" value="<?= !empty($s['view_only']) ? 0 : 1; ?>" />
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Alternar somente visualização"><i class="fas fa-eye-slash"></i></button>
                                                            </form>
                                                            <form method="post" action="compartilhar_usuario_toggle.php" class="d-inline mr-1">
                                                                <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                                                <input type="hidden" name="documento_id" value="<?= (int)$documento_id; ?>" />
                                                                <input type="hidden" name="share_id" value="<?= (int)$s['id']; ?>" />
                                                                <input type="hidden" name="field" value="can_download" />
                                                                <input type="hidden" name="value" value="<?= !empty($s['can_download']) ? 0 : 1; ?>" />
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Alternar download"><i class="fas fa-download"></i></button>
                                                            </form>
                                                            <form method="post" action="compartilhar_usuario_revogar.php" class="d-inline form-revogar-user">
                                                                <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                                                                <input type="hidden" name="documento_id" value="<?= (int)$documento_id; ?>" />
                                                                <input type="hidden" name="share_id" value="<?= (int)$s['id']; ?>" />
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban"></i> Revogar</button>
                                                            </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modal-visualizar" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <div>
                    <div class="small text-muted mb-1">Visualizando Documento:</div>
                    <h5 class="modal-title mb-0" id="modal-doc-title">Visualizando</h5>
                </div>
                <div class="ml-auto d-flex align-items-center" style="gap:8px">
                    <a id="btn-view-newtab" href="#" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-external-link-alt mr-1"></i>Ver em Nova Aba</a>
                    <a id="btn-download" href="#" class="btn btn-sm btn-outline-light"><i class="fas fa-download mr-1"></i>Baixar</a>
                    <button id="btn-print" type="button" class="btn btn-sm btn-outline-light"><i class="fas fa-print mr-1"></i>Imprimir</button>
                    <button type="button" class="close ml-2" data-dismiss="modal">&times;</button>
                </div>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdf-viewer" src="about:blank" style="width:100%; height:80vh; border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-qr" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">QR Code do Link</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body text-center">
                <img id="qr-img" src="about:blank" alt="QR" style="max-width:100%;height:auto;"/>
                <div class="small text-monospace mt-2 text-truncate" id="qr-url"></div>
            </div>
        </div>
    </div>
  
</div>

<?php if (!empty($_SESSION['link_criado_url'])): $linkCriado = $_SESSION['link_criado_url']; unset($_SESSION['link_criado_url']); ?>
<div class="modal fade" id="modal-link-criado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Link criado com sucesso</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group">
                    <label>URL do compartilhamento</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="link-criado-input" value="<?= htmlspecialchars($linkCriado); ?>" readonly>
                        <div class="input-group-append"><button class="btn btn-outline-secondary" type="button" id="btn-copy-link"><i class="fas fa-copy"></i></button></div>
                    </div>
                    <small class="form-text text-muted">Use este link para compartilhar. Para visualização com marca d'água, acrescente <code>&view=1</code>. Links "Somente visualização" ou com "Marca d'água forçada" aplicam automaticamente quando suportado.</small>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= htmlspecialchars($linkCriado); ?>&view=1" target="_blank" class="btn btn-info"><i class="fas fa-eye"></i> Abrir (visualizar)</a>
                <a href="<?= htmlspecialchars($linkCriado); ?>" target="_blank" class="btn btn-primary"><i class="fas fa-download"></i> Abrir (download)</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Script para o modal de visualização
    $('#modal-visualizar').on('show.bs.modal', function(e) {
        const link = $(e.relatedTarget);
        const href = link.attr('href');
        const titulo = link.data('doc-title');
        $('#modal-doc-title').text(titulo);
        $('#pdf-viewer').attr('src', href);
        // Ações de topo
        $('#btn-view-newtab').attr('href', href);
        const dl = href + (href.indexOf('?')>-1 ? '&' : '?') + 'download=1';
        $('#btn-download').attr('href', dl);
        // Imprimir: tenta chamar print do iframe quando possível
        $('#btn-print').off('click').on('click', function(){
          const frame = document.getElementById('pdf-viewer');
          try { frame.contentWindow.focus(); frame.contentWindow.print(); } catch(err) { window.open(href+'&print=1','_blank'); }
        });
    }).on('hidden.bs.modal', () => {
        $('#pdf-viewer').attr('src', 'about:blank');
    });

    // Script para o botão de apagar
    $(document).on('click', '.btn-apagar-swal', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        Swal.fire({
            title: 'Mover para a lixeira?',
            text: "O documento poderá ser recuperado.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, mover!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
    // Copiar link para área de transferência
    $(document).on('click', '.btn-copy', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link copiado!', showConfirmButton: false, timer: 1500 });
            }).catch(() => {
                fallbackCopy(url);
            });
        } else {
            fallbackCopy(url);
        }
        function fallbackCopy(text) {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link copiado!', showConfirmButton: false, timer: 1500 });
        }
    });

    // Confirmar revogação via SweetAlert
    $(document).on('submit', '.form-revogar', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Revogar este link? ',
            text: 'Usuários não poderão mais acessar pelo link.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, revogar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed) form.submit(); });
    });

    // Modal QR Code
    $(document).on('click', '.btn-qr', function(e){
        e.preventDefault();
        const url = $(this).data('url');
        $('#qr-img').attr('src', 'qrcode_generator.php?text=' + encodeURIComponent(url));
        $('#qr-url').text(url).attr('title', url);
        $('#modal-qr').modal('show');
    });

    // Modal Link Criado: copiar
    $(document).on('click', '#btn-copy-link', function(){
        const input = document.getElementById('link-criado-input');
        if (!input) return;
        input.select();
        document.execCommand('copy');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link copiado!', showConfirmButton: false, timer: 1500 });
    });

    // Abrir automaticamente se existir no DOM
    if (document.getElementById('modal-link-criado')) {
        $('#modal-link-criado').modal('show');
    }
});
</script>