<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

try {
    // --- CONSULTAS PARA OS CARDS DE RESUMO ---
    $total_docs = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $total_pastas = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $docs_hoje = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE()")->fetchColumn();

    // Métricas extras (robustas, com fallback 0 caso não existam colunas/tabelas)
    $total_assinados = 0;
    try { $total_assinados = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND assinado = 1")->fetchColumn(); } catch (Throwable $e) { $total_assinados = 0; }

    $workflows_andamento = 0;
    try { $workflows_andamento = (int)$pdo->query("SELECT COUNT(*) FROM workflow_documentos WHERE status = 'em_andamento'")->fetchColumn(); } catch (Throwable $e) { $workflows_andamento = 0; }

    // --- DADOS PARA OS GRÁFICOS (sem alteração) ---
    $docs_por_tipo_stmt = $pdo->query("SELECT t.nome, COUNT(d.id) as total FROM tipos_documento t JOIN documentos d ON t.id = d.tipo_documento_id WHERE d.apagado_em IS NULL GROUP BY t.nome");
    $docs_por_tipo = $docs_por_tipo_stmt->fetchAll(PDO::FETCH_ASSOC);
    $grafico1_labels = json_encode(array_column($docs_por_tipo, 'nome'));
    $grafico1_data = json_encode(array_column($docs_por_tipo, 'total'));
    
    $docs_7_dias_stmt = $pdo->query("SELECT DATE(data_upload) as dia, COUNT(id) as total FROM documentos WHERE data_upload >= CURDATE() - INTERVAL 7 DAY AND apagado_em IS NULL GROUP BY dia ORDER BY dia ASC");
    $docs_7_dias = $docs_7_dias_stmt->fetchAll(PDO::FETCH_ASSOC);
    $grafico2_labels = json_encode(array_column($docs_7_dias, 'dia'));
    $grafico2_data = json_encode(array_column($docs_7_dias, 'total'));

} catch (PDOException $e) {
    die("Erro ao carregar dados do painel: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><h1>Painel de Produtividade</h1></div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Documentos Gerenciados</div>
                        <div class="stat-value"><?= $total_docs ?></div>
                        <div class="stat-sub">Acervo total no GED</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-folder"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Pastas Criadas</div>
                        <div class="stat-value"><?= $total_pastas ?></div>
                        <div class="stat-sub">Organização por pastas</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Usuários</div>
                        <div class="stat-value"><?= $total_usuarios ?></div>
                        <div class="stat-sub">Contas ativas</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-plus"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Adicionados Hoje</div>
                        <div class="stat-value"><?= $docs_hoje ?></div>
                        <div class="stat-sub"><span class="stat-chip">Hoje</span> Novos uploads</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Documentos Assinados</div>
                        <div class="stat-value"><?= $total_assinados ?></div>
                        <div class="stat-sub">Fluxos concluídos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-project-diagram"></i></div>
                    <div class="stat-body">
                        <div class="stat-label">Workflows em andamento</div>
                        <div class="stat-value"><?= $workflows_andamento ?></div>
                        <div class="stat-sub">Processos ativos</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Documentos por Tipo</h3></div>
                        <div class="card-body"><canvas id="graficoDocsPorTipo" style="height: 300px;"></canvas></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Documentos Adicionados (Últimos 7 dias)</h3></div>
                        <div class="card-body"><canvas id="graficoDocs7Dias" style="height: 300px;"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<script>
// Todo o seu JavaScript para os gráficos continua o mesmo, sem alterações
$(function () {
    var ctx1 = document.getElementById('graficoDocsPorTipo').getContext('2d');
    var graficoDocsPorTipo = new Chart(ctx1, { type: 'bar', data: { labels: <?= $grafico1_labels ?>, datasets: [{ label: 'Total de Documentos', data: <?= $grafico1_data ?>, backgroundColor: 'rgba(0, 123, 255, 0.8)', borderColor: 'rgba(0, 123, 255, 1)', borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
    var ctx2 = document.getElementById('graficoDocs7Dias').getContext('2d');
    var graficoDocs7Dias = new Chart(ctx2, { type: 'line', data: { labels: <?= $grafico2_labels ?>, datasets: [{ label: 'Documentos Adicionados', data: <?= $grafico2_data ?>, backgroundColor: 'rgba(40, 167, 69, 0.2)', borderColor: 'rgba(40, 167, 69, 1)', borderWidth: 2, tension: 0.3 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
});
</script>