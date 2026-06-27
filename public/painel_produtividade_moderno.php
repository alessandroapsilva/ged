<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../core/init.php';
require_once '../helpers/ProfessionalLayout.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

try {
    // Queries com tratamento de erro
    $total_docs = 0;
    $total_pastas = 0;
    $total_usuarios = 0;
    $docs_hoje = 0;
    $total_assinados = 0;
    $workflows_andamento = 0;
    $grafico1_labels = json_encode([]);
    $grafico1_data = json_encode([]);
    $grafico2_labels = json_encode([]);
    $grafico2_data = json_encode([]);
    
    try {
        $total_docs = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro total_docs: " . $e->getMessage()); }
    
    try {
        $total_pastas = (int)$pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro total_pastas: " . $e->getMessage()); }
    
    try {
        $total_usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro total_usuarios: " . $e->getMessage()); }
    
    try {
        $docs_hoje = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE()")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro docs_hoje: " . $e->getMessage()); }
    
    try {
        $total_assinados = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND assinado = 1")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro total_assinados: " . $e->getMessage()); }
    
    try {
        $workflows_andamento = (int)$pdo->query("SELECT COUNT(*) FROM workflow_documentos WHERE status = 'em_andamento'")->fetchColumn();
    } catch (Throwable $e) { error_log("Erro workflows: " . $e->getMessage()); }
    
    // Gráficos
    try {
        $docs_por_tipo_stmt = $pdo->query("SELECT t.nome, COUNT(d.id) as total FROM tipos_documento t LEFT JOIN documentos d ON t.id = d.tipo_documento_id WHERE d.apagado_em IS NULL OR d.id IS NULL GROUP BY t.nome");
        $docs_por_tipo = $docs_por_tipo_stmt->fetchAll(PDO::FETCH_ASSOC);
        $grafico1_labels = json_encode(array_column($docs_por_tipo, 'nome'));
        $grafico1_data = json_encode(array_column($docs_por_tipo, 'total'));
    } catch (Throwable $e) { 
        error_log("Erro grafico1: " . $e->getMessage()); 
        $grafico1_labels = json_encode([]);
        $grafico1_data = json_encode([]);
    }
    
    try {
        $docs_7_dias_stmt = $pdo->query("SELECT DATE(data_upload) as dia, COUNT(id) as total FROM documentos WHERE data_upload >= CURDATE() - INTERVAL 7 DAY AND apagado_em IS NULL GROUP BY dia ORDER BY dia ASC");
        $docs_7_dias = $docs_7_dias_stmt->fetchAll(PDO::FETCH_ASSOC);
        $grafico2_labels = json_encode(array_column($docs_7_dias, 'dia'));
        $grafico2_data = json_encode(array_column($docs_7_dias, 'total'));
    } catch (Throwable $e) { 
        error_log("Erro grafico2: " . $e->getMessage()); 
        $grafico2_labels = json_encode([]);
        $grafico2_data = json_encode([]);
    }

} catch (Exception $e) {
    error_log("Erro geral no painel: " . $e->getMessage());
}

// Configuração do Layout
$layout = new ProfessionalLayout('Painel de Produtividade');
$layout->addBreadcrumb('Home', 'index.php');
$layout->addBreadcrumb('Dashboard');
$layout->addScript('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js');

ob_start();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .stat-card .value {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 0.5rem 0;
    }
    .stat-card .label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .card-header {
        background: #f9fafb;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        border-radius: 8px 8px 0 0;
    }
    .card-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .card-body {
        padding: 1.5rem;
    }
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    .btn-success {
        background: #10b981;
        color: white;
    }
    .btn-info {
        background: #06b6d4;
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-file" style="font-size: 1.5rem; opacity: 0.8;"></i>
        <div class="value"><?php echo $total_docs; ?></div>
        <div class="label">Documentos</div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <i class="fas fa-folder" style="font-size: 1.5rem; opacity: 0.8;"></i>
        <div class="value"><?php echo $total_pastas; ?></div>
        <div class="label">Pastas</div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <i class="fas fa-users" style="font-size: 1.5rem; opacity: 0.8;"></i>
        <div class="value"><?php echo $total_usuarios; ?></div>
        <div class="label">Usuários</div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <i class="fas fa-calendar-today" style="font-size: 1.5rem; opacity: 0.8;"></i>
        <div class="value"><?php echo $docs_hoje; ?></div>
        <div class="label">Hoje</div>
    </div>
</div>

<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-doughnut"></i> Documentos por Tipo</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="graficoDocsPorTipo"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line"></i> Últimos 7 Dias</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="graficoDocs7Dias"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bolt"></i> Ações Rápidas</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="documentos_adicionar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Documento</a>
            <a href="pastas_criar.php" class="btn btn-secondary"><i class="fas fa-folder-plus"></i> Nova Pasta</a>
            <a href="digitalizar_dynamsoft.php" class="btn btn-success"><i class="fas fa-scanner"></i> Digitalizar</a>
            <a href="relatorios_avancados.php" class="btn btn-info"><i class="fas fa-file-chart-line"></i> Relatórios</a>
            <a href="verificacao.php" class="btn" style="background: #8b5cf6; color: white;"><i class="fas fa-check-circle"></i> Verificar Sistema</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        Chart.defaults.color = '#64748b';

        const ctx1 = document.getElementById('graficoDocsPorTipo');
        if (ctx1) {
            try {
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo $grafico1_labels; ?>,
                        datasets: [{
                            data: <?php echo $grafico1_data; ?>,
                            backgroundColor: ['#3b82f6', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#ef4444', '#ec4899', '#6366f1'],
                            borderColor: '#ffffff',
                            borderWidth: 2
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
            } catch (e) { console.error('Erro gráfico 1:', e); }
        }

        const ctx2 = document.getElementById('graficoDocs7Dias');
        if (ctx2) {
            try {
                new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: <?php echo $grafico2_labels; ?>,
                        datasets: [{
                            label: 'Uploads',
                            data: <?php echo $grafico2_data; ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            } catch (e) { console.error('Erro gráfico 2:', e); }
        }
    });
</script>

<?php
$content = ob_get_clean();
$layout->setContent($content);
$layout->render();
?>

try {
    // --- CONSULTAS PARA OS CARDS DE RESUMO ---
    $total_docs = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $total_pastas = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $docs_hoje = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND DATE(data_upload) = CURDATE()")->fetchColumn();

    // Métricas extras
    $total_assinados = 0;
    try { $total_assinados = (int)$pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL AND assinado = 1")->fetchColumn(); } catch (Throwable $e) { $total_assinados = 0; }

    $workflows_andamento = 0;
    try { $workflows_andamento = (int)$pdo->query("SELECT COUNT(*) FROM workflow_documentos WHERE status = 'em_andamento'")->fetchColumn(); } catch (Throwable $e) { $workflows_andamento = 0; }

    // --- DADOS PARA OS GRÁFICOS ---
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

// Configuração do Layout
$layout = new ProfessionalLayout('Painel de Produtividade');
$layout->addBreadcrumb('Home', 'index.php');
$layout->addBreadcrumb('Dashboard');
$layout->addScript('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js');

ob_start();
?>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card-modern {
            background: linear-gradient(135deg, var(--color-primary, #2563eb) 0%, var(--color-secondary, #7c3aed) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .stat-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card-modern.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card-modern.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-card-modern.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card-modern.cyan { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .stat-card-modern.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

        .stat-card-content { position: relative; z-index: 1; }
        .stat-card-label { font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-card-number { font-size: 2.25rem; font-weight: 700; margin-bottom: 0.25rem; line-height: 1; }
        .stat-card-footer { font-size: 0.8rem; opacity: 0.85; display: flex; align-items: center; gap: 0.5rem; }
        .stat-card-icon { font-size: 2rem; position: absolute; top: 1rem; right: 1rem; opacity: 0.2; }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>

    <div class="dashboard-container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Total Documentos -->
            <div class="stat-card-modern">
                <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Documentos</div>
                    <div class="stat-card-number"><?= number_format($total_docs) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-database"></i> <span>Total arquivado</span>
                    </div>
                </div>
            </div>

            <!-- Total Pastas -->
            <div class="stat-card-modern purple">
                <div class="stat-card-icon"><i class="fas fa-folder"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Pastas</div>
                    <div class="stat-card-number"><?= number_format($total_pastas) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-sitemap"></i> <span>Estrutura</span>
                    </div>
                </div>
            </div>

            <!-- Total Usuários -->
            <div class="stat-card-modern orange">
                <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Usuários</div>
                    <div class="stat-card-number"><?= number_format($total_usuarios) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-user-check"></i> <span>Ativos</span>
                    </div>
                </div>
            </div>

            <!-- Documentos Hoje -->
            <div class="stat-card-modern green">
                <div class="stat-card-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Hoje</div>
                    <div class="stat-card-number"><?= number_format($docs_hoje) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-upload"></i> <span>Novos envios</span>
                    </div>
                </div>
            </div>
            
             <!-- Assinados -->
            <div class="stat-card-modern cyan">
                <div class="stat-card-icon"><i class="fas fa-file-signature"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Assinados</div>
                    <div class="stat-card-number"><?= number_format($total_assinados) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-certificate"></i> <span>Assinatura Digital</span>
                    </div>
                </div>
            </div>

             <!-- Workflows -->
            <div class="stat-card-modern red">
                <div class="stat-card-icon"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-card-content">
                    <div class="stat-card-label">Workflows</div>
                    <div class="stat-card-number"><?= number_format($workflows_andamento) ?></div>
                    <div class="stat-card-footer">
                        <i class="fas fa-spinner fa-spin"></i> <span>Em andamento</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-container">
            <!-- Documentos por Tipo -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Documentos por Tipo</h3>
                </div>
                <div class="card-body">
                    <canvas id="graficoDocsPorTipo" style="height: 300px;"></canvas>
                </div>
            </div>

            <!-- Documentos Últimos 7 Dias -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Evolução (7 dias)</h3>
                </div>
                <div class="card-body">
                    <canvas id="graficoDocs7Dias" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Ações Rápidas</h3>
            </div>
            <div class="card-body">
                 <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="documentos_adicionar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Documento</a>
                    <a href="pastas_criar.php" class="btn btn-secondary"><i class="fas fa-folder-plus"></i> Nova Pasta</a>
                    <a href="digitalizar.php" class="btn btn-success"><i class="fas fa-camera"></i> Digitalizar</a>
                    <a href="relatorios_avancados.php" class="btn btn-info"><i class="fas fa-file-analytics"></i> Relatórios</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurações Globais ChartJS
            Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
            Chart.defaults.color = '#64748b';

            // Gráfico de Documentos por Tipo
            const ctx1 = document.getElementById('graficoDocsPorTipo');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: <?= $grafico1_labels ?>,
                        datasets: [{
                            data: <?= $grafico1_data ?>,
                            backgroundColor: ['#3b82f6', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#ef4444', '#ec4899', '#6366f1'],
                            borderColor: '#ffffff',
                            borderWidth: 2
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
            }

            // Gráfico de Documentos nos Últimos 7 Dias
            const ctx2 = document.getElementById('graficoDocs7Dias');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: <?= $grafico2_labels ?>,
                        datasets: [{
                            label: 'Uploads',
                            data: <?= $grafico2_data ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
$content = ob_get_clean();
$layout->setContent($content);
echo $layout->render();
?>