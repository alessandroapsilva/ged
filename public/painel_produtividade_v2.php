<?php
/**
 * Dashboard Moderno - Painel de Produtividade Avançado
 * Versão 2.0 - Com widgets interativos e métricas em tempo real
 */

require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

try {
    // --- MÉTRICAS PRINCIPAIS ---
    $total_docs = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $total_pastas = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $docs_hoje = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE()")->fetchColumn();
    
    // Docs de ontem para comparação
    $docs_ontem = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE() - INTERVAL 1 DAY")->fetchColumn();
    $trend_docs = $docs_ontem > 0 ? (($docs_hoje - $docs_ontem) / $docs_ontem) * 100 : 0;

    // Métricas extras
    $total_assinados = 0;
    try { $total_assinados = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND assinado = 1")->fetchColumn(); } catch (Throwable $e) {}

    $workflows_andamento = 0;
    try { $workflows_andamento = (int)$pdo->query("SELECT COUNT(*) FROM workflow_documentos WHERE status = 'em_andamento'")->fetchColumn(); } catch (Throwable $e) {}

    // Tamanho total do acervo
    $tamanho_total = 0;
    try {
        $stmt = $pdo->query("SELECT SUM(tamanho_arquivo) as total FROM documentos WHERE apagado_em IS NULL");
        $tamanho_total = $stmt->fetchColumn() ?: 0;
    } catch (Throwable $e) {}

    // Atividade recente (últimos 10 documentos)
    $atividade_recente = [];
    try {
        $stmt = $pdo->query("
            SELECT d.id, d.titulo, d.data_upload, u.nome as usuario_nome, t.nome as tipo_nome
            FROM documentos d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
            WHERE d.apagado_em IS NULL
            ORDER BY d.data_upload DESC
            LIMIT 10
        ");
        $atividade_recente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // Top 5 usuários mais ativos
    $top_usuarios = [];
    try {
        $stmt = $pdo->query("
            SELECT u.nome, COUNT(d.id) as total_docs
            FROM usuarios u
            LEFT JOIN documentos d ON u.id = d.usuario_id AND d.apagado_em IS NULL
            GROUP BY u.id, u.nome
            ORDER BY total_docs DESC
            LIMIT 5
        ");
        $top_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // --- DADOS PARA GRÁFICOS ---
    
    // Gráfico 1: Documentos por Tipo
    $docs_por_tipo_stmt = $pdo->query("
        SELECT t.nome, COUNT(d.id) as total 
        FROM tipos_documento t 
        LEFT JOIN documentos d ON t.id = d.tipo_documento_id AND d.apagado_em IS NULL
        GROUP BY t.nome
        ORDER BY total DESC
        LIMIT 10
    ");
    $docs_por_tipo = $docs_por_tipo_stmt->fetchAll(PDO::FETCH_ASSOC);
    $grafico1_labels = json_encode(array_column($docs_por_tipo, 'nome'));
    $grafico1_data = json_encode(array_column($docs_por_tipo, 'total'));
    
    // Gráfico 2: Documentos nos últimos 30 dias
    $docs_30_dias_stmt = $pdo->query("
        SELECT DATE(data_upload) as dia, COUNT(id) as total 
        FROM documentos 
        WHERE data_upload >= CURDATE() - INTERVAL 30 DAY 
        AND apagado_em IS NULL 
        GROUP BY dia 
        ORDER BY dia ASC
    ");
    $docs_30_dias = $docs_30_dias_stmt->fetchAll(PDO::FETCH_ASSOC);
    $grafico2_labels = json_encode(array_column($docs_30_dias, 'dia'));
    $grafico2_data = json_encode(array_column($docs_30_dias, 'total'));

    // Gráfico 3: Documentos por usuário (Top 10)
    $docs_por_usuario_stmt = $pdo->query("
        SELECT u.nome, COUNT(d.id) as total 
        FROM usuarios u 
        LEFT JOIN documentos d ON u.id = d.usuario_id AND d.apagado_em IS NULL
        GROUP BY u.id, u.nome
        ORDER BY total DESC
        LIMIT 10
    ");
    $docs_por_usuario = $docs_por_usuario_stmt->fetchAll(PDO::FETCH_ASSOC);
    $grafico3_labels = json_encode(array_column($docs_por_usuario, 'nome'));
    $grafico3_data = json_encode(array_column($docs_por_usuario, 'total'));

} catch (PDOException $e) {
    die("Erro ao carregar dados do painel: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-chart-line"></i> Dashboard Executivo</h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <button class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Cards de Métricas Principais -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-gradient-primary">
                        <div class="inner">
                            <h3><?= number_format($total_docs) ?></h3>
                            <p>Documentos Gerenciados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <a href="documentos" class="small-box-footer">
                            Ver detalhes <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-gradient-success">
                        <div class="inner">
                            <h3><?= number_format($total_pastas) ?></h3>
                            <p>Pastas Criadas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <a href="documentos" class="small-box-footer">
                            Ver estrutura <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-gradient-warning">
                        <div class="inner">
                            <h3><?= $docs_hoje ?></h3>
                            <p>Adicionados Hoje</p>
                            <?php if ($trend_docs != 0): ?>
                                <small class="text-sm">
                                    <i class="fas fa-arrow-<?= $trend_docs > 0 ? 'up' : 'down' ?>"></i>
                                    <?= abs(round($trend_docs, 1)) ?>% vs ontem
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <a href="documentos?hoje=1" class="small-box-footer">
                            Ver documentos <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-gradient-info">
                        <div class="inner">
                            <h3><?= formatBytes($tamanho_total) ?></h3>
                            <p>Tamanho do Acervo</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <a href="admin_saude_acervo" class="small-box-footer">
                            Saúde do acervo <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cards Secundários -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Usuários Ativos</span>
                            <span class="info-box-number"><?= $total_usuarios ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-file-signature"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Docs Assinados</span>
                            <span class="info-box-number"><?= $total_assinados ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-project-diagram"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Workflows Ativos</span>
                            <span class="info-box-number"><?= $workflows_andamento ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Média/Dia (30d)</span>
                            <span class="info-box-number"><?= round($total_docs / 30, 1) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-bar"></i> Documentos por Tipo</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoDocsPorTipo" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Documentos (Últimos 30 dias)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoDocs30Dias" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users"></i> Top Usuários</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoUsuarios" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Atividade Recente</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                                <?php foreach (array_slice($atividade_recente, 0, 8) as $doc): ?>
                                <li class="item">
                                    <div class="product-img">
                                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="product-info">
                                        <a href="documentos_ver?id=<?= $doc['id'] ?>" class="product-title">
                                            <?= htmlspecialchars(substr($doc['titulo'], 0, 50)) ?>
                                            <span class="badge badge-info float-right"><?= htmlspecialchars($doc['tipo_nome']) ?></span>
                                        </a>
                                        <span class="product-description">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($doc['usuario_nome']) ?>
                                            <span class="float-right text-muted text-sm">
                                                <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($doc['data_upload'])) ?>
                                            </span>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="documentos" class="uppercase">Ver Todos os Documentos</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(function () {
    // Configurações globais dos gráficos
    Chart.defaults.font.family = 'Source Sans Pro';
    Chart.defaults.color = '#666';

    // Gráfico 1: Documentos por Tipo (Barras Horizontais)
    const ctx1 = document.getElementById('graficoDocsPorTipo').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?= $grafico1_labels ?>,
            datasets: [{
                label: 'Total de Documentos',
                data: <?= $grafico1_data ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                x: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });

    // Gráfico 2: Documentos nos últimos 30 dias (Área)
    const ctx2 = document.getElementById('graficoDocs30Dias').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?= $grafico2_labels ?>,
            datasets: [{
                label: 'Documentos Adicionados',
                data: <?= $grafico2_data ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Gráfico 3: Top Usuários (Doughnut)
    const ctx3 = document.getElementById('graficoUsuarios').getContext('2d');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: <?= $grafico3_labels ?>,
            datasets: [{
                data: <?= $grafico3_data ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 99, 255, 0.8)',
                    'rgba(99, 255, 132, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            }
        }
    });
});
</script>

<?php
// Função auxiliar para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
