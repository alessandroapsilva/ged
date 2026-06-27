<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();
require_permission('admin.access');

// KPIs
$kpis = [
  'total_docs' => 0,
  'indexed_docs' => 0,
  'unindexed_docs' => 0,
  'ocr_docs' => 0,
  'active_links' => 0,
  'downloads_today' => 0,
  'signatures' => 0,
];

try {
  $kpis['total_docs'] = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
  $kpis['indexed_docs'] = (int)$pdo->query("SELECT COUNT(*) FROM documentos d JOIN documentos_indice di ON di.documento_id = d.id WHERE d.apagado_em IS NULL")->fetchColumn();
  $kpis['unindexed_docs'] = max(0, $kpis['total_docs'] - $kpis['indexed_docs']);
  try { $kpis['ocr_docs'] = (int)$pdo->query("SELECT COUNT(DISTINCT documento_id) FROM documentos_ocr")->fetchColumn(); } catch (Throwable $e) { $kpis['ocr_docs'] = 0; }
  try { $kpis['active_links'] = (int)$pdo->query("SELECT COUNT(*) FROM documento_links WHERE (expires_at IS NULL OR expires_at > NOW()) AND (max_downloads IS NULL OR downloads < max_downloads)")->fetchColumn(); } catch (Throwable $e) { $kpis['active_links'] = 0; }
  try { $kpis['downloads_today'] = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='SHARE_DOWNLOAD' AND DATE(created_at)=CURDATE()")->fetchColumn(); } catch (Throwable $e) { $kpis['downloads_today'] = 0; }
  try { $kpis['signatures'] = (int)$pdo->query("SELECT COUNT(*) FROM documentos_assinaturas")->fetchColumn(); } catch (Throwable $e) { $kpis['signatures'] = 0; }
} catch (Throwable $e) {}

// Docs por tipo
$docsPorTipo = [];
try {
  $st = $pdo->query("SELECT COALESCE(t.nome,'N/A') as tipo, COUNT(*) qt FROM documentos d LEFT JOIN tipos_documento t ON d.tipo_documento_id=t.id WHERE d.apagado_em IS NULL GROUP BY tipo ORDER BY qt DESC");
  $docsPorTipo = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $docsPorTipo = []; }

// Docs por mês (últimos 12 meses)
$docsMes = [];
try {
  $st = $pdo->query("SELECT DATE_FORMAT(data_upload,'%Y-%m') ym, COUNT(*) qt FROM documentos WHERE apagado_em IS NULL AND data_upload >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC");
  $docsMes = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $docsMes = []; }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>Saúde do Acervo</h1></div></div></div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-2 col-6"><div class="small-box bg-primary"><div class="inner"><h3><?= $kpis['total_docs'] ?></h3><p>Documentos</p></div><div class="icon"><i class="fas fa-file-alt"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= $kpis['indexed_docs'] ?></h3><p>Indexados</p></div><div class="icon"><i class="fas fa-search"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= $kpis['unindexed_docs'] ?></h3><p>Não indexados</p></div><div class="icon"><i class="fas fa-exclamation"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $kpis['ocr_docs'] ?></h3><p>Docs com OCR</p></div><div class="icon"><i class="fas fa-magic"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?= $kpis['active_links'] ?></h3><p>Links ativos</p></div><div class="icon"><i class="fas fa-share-alt"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-dark"><div class="inner"><h3><?= $kpis['downloads_today'] ?></h3><p>Downloads (hoje)</p></div><div class="icon"><i class="fas fa-download"></i></div></div></div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="card card-outline card-dark">
            <div class="card-header"><h3 class="card-title">Documentos por Tipo</h3></div>
            <div class="card-body"><canvas id="chartTipos" height="160"></canvas></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card card-outline card-dark">
            <div class="card-header"><h3 class="card-title">Uploads por Mês (12m)</h3></div>
            <div class="card-body"><canvas id="chartMes" height="160"></canvas></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const tipos = <?= json_encode(array_column($docsPorTipo, 'tipo')); ?>;
const tiposQt = <?= json_encode(array_column($docsPorTipo, 'qt')); ?>;
const ym = <?= json_encode(array_column($docsMes, 'ym')); ?>;
const ymQt = <?= json_encode(array_column($docsMes, 'qt')); ?>;

new Chart(document.getElementById('chartTipos').getContext('2d'), { type: 'bar', data: { labels: tipos, datasets: [{ label: 'Docs', data: tiposQt, backgroundColor: '#007bff' }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
new Chart(document.getElementById('chartMes').getContext('2d'), { type: 'line', data: { labels: ym, datasets: [{ label: 'Uploads', data: ymQt, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.15)', fill: true }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
</script>
