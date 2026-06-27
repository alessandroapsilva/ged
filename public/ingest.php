<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
// Redireciona permanentemente para a página moderna de digitalização
header('Location: digitalizar.php', true, 301);
exit();

// Garante que as tabelas do Ingest existam; se não, tenta criar a partir do SQL de migração
function ensure_ingest_tables(PDO $pdo): bool {
    $migrated = false;
    try {
        $pdo->query("SELECT 1 FROM ingest_arquivos LIMIT 1");
        return false; // já existe
    } catch (Throwable $e) {
        $sqlFile = PROJECT_ROOT . '/sql/20251030_create_ingest.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            // Divide por ponto-e-vírgula seguido de nova linha ou fim de arquivo
            $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
            foreach ($statements as $stmtRaw) {
                // Remove linhas de comentário no início do bloco
                $lines = preg_split('/\r?\n/', (string)$stmtRaw);
                $clean = [];
                $started = false;
                foreach ($lines as $ln) {
                    if (!$started && preg_match('/^\s*--/', $ln)) {
                        // ignora comentários iniciais
                        continue;
                    }
                    $started = true;
                    $clean[] = $ln;
                }
                $stmt = trim(implode("\n", $clean));
                if ($stmt === '') { continue; }
                try { $pdo->exec($stmt); $migrated = true; } catch (Throwable $ex) { /* ignora erros idempotentes */ }
            }
        }
    }
    return $migrated;
}

$__ingest_migrated = ensure_ingest_tables($pdo);

// Métricas do painel (tolerantes caso as tabelas ainda não existam por algum motivo)
try {
    $countHoje = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND DATE(admitido_em)=CURDATE()")
        ->fetchColumn();
    $countOntem = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND DATE(admitido_em)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)")
        ->fetchColumn();
    $count7d = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND admitido_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        ->fetchColumn();
    $countCorrigidos = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos WHERE status='corrigido'")
        ->fetchColumn();
    $countACorrigir = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos WHERE status='corrigir'")
        ->fetchColumn();
    $countTotal = (int)$pdo->query("SELECT COUNT(*) FROM ingest_arquivos")
        ->fetchColumn();
    $itens = $pdo->query("SELECT * FROM ingest_arquivos ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Fallback seguro com zeros e lista vazia
    $countHoje = $countOntem = $count7d = $countCorrigidos = $countACorrigir = $countTotal = 0;
    $itens = [];
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php if (!empty($__ingest_migrated)): ?>
        <div class="alert alert-info m-3" role="alert">
            <i class="fas fa-database mr-1"></i> Migrações do Ingest aplicadas automaticamente.
        </div>
    <?php endif; ?>
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid">
            <h1>Ingestão de Documentos</h1>
            <p class="text-muted mb-0">Envie PDFs em lote a partir do seu computador ou acesse a central de digitalização para usar o scanner.</p>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-teal">
                                <div class="inner"><h3><?= $countHoje ?></h3><p>Documentos Admitidos Hoje</p></div>
                                <div class="icon"><i class="far fa-calendar-check"></i></div>
                                <a href="ingest_listar.php?f=admitidos_hoje" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-teal">
                                <div class="inner"><h3><?= $countOntem ?></h3><p>Documentos Admitidos Ontem</p></div>
                                <div class="icon"><i class="far fa-calendar-minus"></i></div>
                                <a href="ingest_listar.php?f=admitidos_ontem" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-teal">
                                <div class="inner"><h3><?= $count7d ?></h3><p>Admitidos nos Últimos 7 Dias</p></div>
                                <div class="icon"><i class="far fa-calendar"></i></div>
                                <a href="ingest_listar.php?f=admitidos_7d" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-info">
                                <div class="inner"><h3><?= $countCorrigidos ?></h3><p>Arquivos Corrigidos</p></div>
                                <div class="icon"><i class="fas fa-check"></i></div>
                                <a href="ingest_listar.php?f=corrigidos" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner"><h3><?= $countACorrigir ?></h3><p>Arquivos a Corrigir</p></div>
                                <div class="icon"><i class="fas fa-exclamation"></i></div>
                                <a href="ingest_listar.php?f=a_corrigir" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-danger">
                                <div class="inner"><h3><?= $countTotal ?></h3><p>Total de Arquivos</p></div>
                                <div class="icon"><i class="fas fa-times"></i></div>
                                <a href="ingest_listar.php" class="small-box-footer">Listar <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <form id="form-ingest" action="ingest_process.php" method="post" enctype="multipart/form-data">
                        <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>
                        <div class="card card-primary card-outline">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Upload de Arquivos (PDF)</h3>
                                <small class="text-muted">Tamanho máximo conforme configuração do servidor</small>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="arquivos">Selecione um ou mais arquivos PDF</label>
                                    <input type="file" class="form-control-file" id="arquivos" name="arquivos[]" accept="application/pdf" multiple required>
                                    <small class="form-text text-muted">Você pode arrastar e soltar arquivos neste campo.</small>
                                </div>
                                <div id="lista-arquivos" class="small text-muted" style="display:none;"></div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label for="titulo_padrao">Título padrão (opcional)</label>
                                        <input type="text" class="form-control" id="titulo_padrao" name="titulo_padrao" placeholder="Será usado apenas se não marcar 'Usar nome do arquivo'">
                                    </div>
                                    <div class="form-group col-md-4 d-flex align-items-end">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="chk-usar-nome" name="usar_nome_arquivo" checked>
                                            <label class="custom-control-label" for="chk-usar-nome">Usar nome do arquivo como título</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="descricao">Descrição (opcional, aplicada a todos)</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="2" placeholder="Resumo curto do documento"></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="tipo_documento_id">Tipo de Documento</label>
                                        <select name="tipo_documento_id" id="tipo_documento_id" class="form-control">
                                            <option value="">-- Selecione --</option>
                                            <?php foreach ($tipos as $t): ?>
                                                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Usado também para calcular vencimento quando configurado.</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>&nbsp;</label>
                                        <div class="custom-control custom-switch d-block">
                                            <input type="checkbox" class="custom-control-input" id="chk-extrair-texto" name="extrair_texto" checked>
                                            <label class="custom-control-label" for="chk-extrair-texto">Extrair texto do PDF (se disponível)</label>
                                            <small class="form-text text-muted">Não executa OCR; apenas extrai texto embutido do PDF.</small>
                                        </div>
                                    </div>
                                </div>
                                <div id="metadados-dinamicos" class="mt-2"></div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" id="btn-enviar" class="btn btn-success">
                                    <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar para o GED
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="card card-secondary card-outline h-100">
                        <div class="card-header"><h3 class="card-title">Digitalização via Scanner</h3></div>
                        <div class="card-body">
                            <p>Prefere escanear diretamente? Use a nossa Central de Digitalização com suporte a Dynamsoft Web TWAIN.</p>
                            <a href="digitalizar.php" class="btn btn-outline-primary btn-block"><i class="fas fa-print mr-1"></i> Abrir Central de Digitalização</a>
                            <hr>
                            <p class="small text-muted mb-1">Dica: você pode digitalizar, revisar as páginas e salvar diretamente no GED.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="resultado-envio" style="display:none;"></div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="card card-dark card-outline">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Fila do Ingest (últimos 50)</h3>
                            <div>
                                <a href="ingest_importar_pasta.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-folder-open"></i> Importar da Pasta Monitorada</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:60px;">Nº</th>
                                            <th>Arquivo Original</th>
                                            <th>Falha</th>
                                            <th>Origem</th>
                                            <th class="text-right">Tamanho</th>
                                            <th>Data</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($itens)): ?>
                                        <tr><td colspan="7" class="text-center text-muted py-4"><i class="far fa-folder-open mr-2"></i>Nenhum arquivo na fila.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($itens as $i => $arq): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($arq['id']) ?></td>
                                            <td>
                                                <i class="far fa-file-pdf text-danger mr-1"></i>
                                                <a href="<?= '../' . htmlspecialchars($arq['caminho_relativo']) ?>" target="_blank"><?= htmlspecialchars($arq['nome_original']) ?></a>
                                                <div class="small mt-1">Status: <?= ingest_status_badge($arq['status']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($arq['falha_motivo'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($arq['origem'] ?? '') ?></td>
                                            <td class="text-right">
                                                <?php if (!empty($arq['tamanho_bytes'])): ?>
                                                    <?= number_format($arq['tamanho_bytes']/1024, 2, ',', '.') ?> KB
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></td>
                                            <td class="text-right">
                                                <div class="btn-group btn-group-sm">
                                                    <a class="btn btn-outline-secondary" href="<?= '../' . htmlspecialchars($arq['caminho_relativo']) ?>" target="_blank" title="Visualizar"><i class="fas fa-eye"></i></a>
                                                    <?php if ($arq['status'] === 'pendente' || $arq['status'] === 'corrigido'): ?>
                                                        <a class="btn btn-outline-success" href="ingest_admitir.php?id=<?= (int)$arq['id'] ?>" title="Admitir"><i class="fas fa-cloud-upload-alt"></i></a>
                                                    <?php endif; ?>
                                                    <?php if ($arq['status'] === 'corrigir'): ?>
                                                        <a class="btn btn-outline-warning" href="ingest_revalidar.php?id=<?= (int)$arq['id'] ?>" title="Revalidar"><i class="fas fa-redo"></i></a>
                                                    <?php endif; ?>
                                                    <a class="btn btn-outline-danger btn-apagar" href="ingest_apagar.php?id=<?= (int)$arq['id'] ?>" title="Remover"><i class="fas fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer small text-muted">
                            * Contabiliza apenas documentos processados pelo Ingest. Por padrão, arquivos mais recentes primeiro.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('arquivos');
  const lista = document.getElementById('lista-arquivos');
  input.addEventListener('change', function(){
    if (!this.files || this.files.length === 0) { lista.style.display='none'; lista.innerHTML=''; return; }
    let html = '<strong>' + this.files.length + ' arquivo(s) selecionado(s):</strong><ul class="mb-0">';
    for (let f of this.files) { html += '<li>' + f.name + ' (' + Math.round(f.size/1024) + ' KB)</li>'; }
    html += '</ul>';
    lista.innerHTML = html; lista.style.display='block';
  });

  // Carregar metadados ao escolher tipo
  const selectTipo = document.getElementById('tipo_documento_id');
  selectTipo.addEventListener('change', function(){
    const tipoId = this.value;
    const container = document.getElementById('metadados-dinamicos');
    container.innerHTML = '';
    if (tipoId) {
      fetch('ajax_get_metadados_fields.php?tipo_id=' + encodeURIComponent(tipoId))
        .then(r => r.json())
        .then(campos => {
          if (Array.isArray(campos) && campos.length) {
            let html = '<hr><h5>Metadados</h5>';
            campos.forEach(c => {
              html += '<div class="form-group">'
                   +  '<label for="meta-' + c.id + '">' + c.rotulo + '</label>'
                   +  '<input type="text" class="form-control" id="meta-' + c.id + '" name="meta[' + c.id + ']" />'
                   +  '</div>';
            });
            container.innerHTML = html;
          }
        })
        .catch(() => { /* silencia erros de UX */ });
    }
  });
});
</script>
