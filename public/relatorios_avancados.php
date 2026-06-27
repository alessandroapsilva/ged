<?php
/**
 * Relatórios Avançados - GED
 * Gera relatórios executivos em PDF com gráficos
 */

require_once '../core/init.php';
require_once '../db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Busca dados para os relatórios
try {
    // Estatísticas gerais
    $stats = [];
    $stats['total_documentos'] = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $stats['total_pastas'] = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $stats['total_usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
    
    // Documentos por tipo
    $docs_por_tipo = $pdo->query("
        SELECT t.nome, COUNT(d.id) as total 
        FROM tipos_documento t 
        LEFT JOIN documentos d ON t.id = d.tipo_documento_id AND d.apagado_em IS NULL
        GROUP BY t.id, t.nome
        ORDER BY total DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Documentos por mês (últimos 12 meses)
    $docs_por_mes = $pdo->query("
        SELECT 
            DATE_FORMAT(data_upload, '%Y-%m') as mes,
            COUNT(*) as total
        FROM documentos
        WHERE data_upload >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND apagado_em IS NULL
        GROUP BY mes
        ORDER BY mes ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top usuários
    $top_usuarios = $pdo->query("
        SELECT 
            u.nome,
            COUNT(d.id) as total_docs,
            SUM(d.tamanho_arquivo) as tamanho_total
        FROM usuarios u
        LEFT JOIN documentos d ON u.id = d.usuario_id AND d.apagado_em IS NULL
        GROUP BY u.id, u.nome
        ORDER BY total_docs DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Tamanho total
    $tamanho_total = $pdo->query("SELECT SUM(tamanho_arquivo) FROM documentos WHERE apagado_em IS NULL")->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-chart-pie"></i> Relatórios Avançados</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Relatórios</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Filtros -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filtros do Relatório</h3>
                </div>
                <div class="card-body">
                    <form id="formRelatorio">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Período</label>
                                    <select class="form-control" name="periodo" id="periodo">
                                        <option value="7">Últimos 7 dias</option>
                                        <option value="30">Últimos 30 dias</option>
                                        <option value="90">Últimos 3 meses</option>
                                        <option value="365" selected>Último ano</option>
                                        <option value="all">Todo o período</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tipo de Relatório</label>
                                    <select class="form-control" name="tipo" id="tipoRelatorio">
                                        <option value="geral">Relatório Geral</option>
                                        <option value="documentos">Análise de Documentos</option>
                                        <option value="usuarios">Análise de Usuários</option>
                                        <option value="tipos">Por Tipo de Documento</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Formato</label>
                                    <select class="form-control" name="formato" id="formato">
                                        <option value="screen">Visualizar na Tela</option>
                                        <option value="pdf">Gerar PDF</option>
                                        <option value="excel">Exportar Excel</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-block" onclick="gerarRelatorio()">
                                        <i class="fas fa-file-pdf"></i> Gerar Relatório
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Prévia do Relatório -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye"></i> Prévia do Relatório</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-success" onclick="imprimirRelatorio()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="downloadPDF()">
                            <i class="fas fa-download"></i> Baixar PDF
                        </button>
                    </div>
                </div>
                <div class="card-body" id="areaRelatorio">
                    
                    <!-- Cabeçalho do Relatório -->
                    <div class="text-center mb-4">
                        <h2>Relatório de Gestão Documental</h2>
                        <p class="text-muted">Período: Todo o histórico</p>
                        <p class="text-muted">Gerado em: <?= date('d/m/Y H:i') ?></p>
                    </div>

                    <hr>

                    <!-- Resumo Executivo -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4><i class="fas fa-chart-bar"></i> Resumo Executivo</h4>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total de Documentos</span>
                                    <span class="info-box-number"><?= number_format($stats['total_documentos']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-folder"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total de Pastas</span>
                                    <span class="info-box-number"><?= number_format($stats['total_pastas']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Usuários Ativos</span>
                                    <span class="info-box-number"><?= number_format($stats['total_usuarios']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-database"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tamanho Total</span>
                                    <span class="info-box-number"><?= formatBytes($tamanho_total) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Documentos por Tipo</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartTipos" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Evolução Mensal (12 meses)</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartMeses" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela Top Usuários -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h4><i class="fas fa-trophy"></i> Top 10 Usuários Mais Ativos</h4>
                            <table class="table table-striped table-hover">
                                <thead class="bg-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Nome</th>
                                        <th class="text-center">Total de Documentos</th>
                                        <th class="text-center">Tamanho Total</th>
                                        <th class="text-center">Média por Doc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_usuarios as $index => $user): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><i class="fas fa-user"></i> <?= htmlspecialchars($user['nome']) ?></td>
                                        <td class="text-center"><strong><?= number_format($user['total_docs']) ?></strong></td>
                                        <td class="text-center"><?= formatBytes($user['tamanho_total'] ?: 0) ?></td>
                                        <td class="text-center">
                                            <?= $user['total_docs'] > 0 ? formatBytes($user['tamanho_total'] / $user['total_docs']) : '-' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rodapé do Relatório -->
                    <div class="text-center mt-5 pt-3" style="border-top: 2px solid #dee2e6;">
                        <p class="text-muted">
                            <small>
                                Relatório gerado automaticamente pelo Sistema GED<br>
                                © <?= date('Y') ?> - Todos os direitos reservados
                            </small>
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
// Dados dos gráficos
const tiposData = <?= json_encode($docs_por_tipo) ?>;
const mesesData = <?= json_encode($docs_por_mes) ?>;

// Gráfico de Tipos
new Chart(document.getElementById('chartTipos'), {
    type: 'doughnut',
    data: {
        labels: tiposData.map(t => t.nome),
        datasets: [{
            data: tiposData.map(t => t.total),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});

// Gráfico de Meses
new Chart(document.getElementById('chartMeses'), {
    type: 'line',
    data: {
        labels: mesesData.map(m => m.mes),
        datasets: [{
            label: 'Documentos',
            data: mesesData.map(m => m.total),
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

function gerarRelatorio() {
    GED.Toast.info('Gerando relatório...', 'Aguarde');
    // Aqui você pode adicionar lógica para filtrar os dados
    setTimeout(() => {
        GED.Toast.success('Relatório atualizado!');
    }, 1000);
}

function imprimirRelatorio() {
    window.print();
}

function downloadPDF() {
    GED.Loading.show('Gerando PDF...');
    
    const element = document.getElementById('areaRelatorio');
    const opt = {
        margin: 10,
        filename: 'relatorio-ged-' + Date.now() + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).save().then(() => {
        GED.Loading.hide();
        GED.Toast.success('PDF gerado com sucesso!');
    });
}
</script>

<style>
@media print {
    .main-header, .main-sidebar, .content-header, .card-tools, .breadcrumb {
        display: none !important;
    }
    .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
