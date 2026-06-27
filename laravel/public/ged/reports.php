<?php
include 'auth_check.php';
require_once 'classes/Document.php';

$document = new Document();

// Filtros para relatório
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$format = $_GET['format'] ?? 'html';

// Buscar dados do relatório
$reportData = $document->generateReport($startDate, $endDate, $status, $category);

if ($format === 'csv') {
    // Exportar CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_documentos_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // Cabeçalhos
    fputcsv($output, ['ID', 'Título', 'Status', 'Categoria', 'Prioridade', 'Criado Por', 'Data Criação', 'Prazo']);

    // Dados
    foreach ($reportData['documents'] as $doc) {
        fputcsv($output, [
            $doc['id'],
            $doc['title'],
            $doc['status'],
            $doc['category_name'] ?? 'N/A',
            $doc['priority'],
            $doc['creator_name'],
            date('d/m/Y H:i', strtotime($doc['created_at'])),
            $doc['deadline'] ? date('d/m/Y', strtotime($doc['deadline'])) : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}

$categories = $document->getCategories();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - E-Doc</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <a href="index.php">&larr; Voltar ao Painel</a>

        <h1>Relatórios e Estatísticas</h1>

        <!-- Filtros do relatório -->
        <div class="filters" style="margin-bottom: 2rem;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end;">
                <div>
                    <label>Data Inicial:</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                </div>
                <div>
                    <label>Data Final:</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                </div>
                <div>
                    <label>Status:</label>
                    <select name="status" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                        <option value="">Todos</option>
                        <option value="Protocolado" <?php echo $status === 'Protocolado' ? 'selected' : ''; ?>>Protocolado</option>
                        <option value="Em Revisão" <?php echo $status === 'Em Revisão' ? 'selected' : ''; ?>>Em Revisão</option>
                        <option value="Aguardando Aprovação" <?php echo $status === 'Aguardando Aprovação' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <option value="Em Análise" <?php echo $status === 'Em Análise' ? 'selected' : ''; ?>>Em Análise</option>
                        <option value="Aprovado" <?php echo $status === 'Aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="Reprovado" <?php echo $status === 'Reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                        <option value="Arquivado" <?php echo $status === 'Arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                    </select>
                </div>
                <div>
                    <label>Categoria:</label>
                    <select name="category" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button">Gerar Relatório</button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['format' => 'csv'])); ?>" class="button button-secondary">Exportar CSV</a>
            </form>
        </div>

        <!-- Estatísticas do período -->
        <div class="dashboard" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <h3>Total no Período</h3>
                <div class="stat-number"><?php echo $reportData['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Aprovados</h3>
                <div class="stat-number"><?php echo $reportData['approved']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Reprovados</h3>
                <div class="stat-number"><?php echo $reportData['rejected']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pendentes</h3>
                <div class="stat-number"><?php echo $reportData['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Taxa Aprovação</h3>
                <div class="stat-number"><?php echo $reportData['total'] > 0 ? round(($reportData['approved'] / $reportData['total']) * 100, 1) : 0; ?>%</div>
            </div>
            <div class="stat-card overdue">
                <h3>Vencidos</h3>
                <div class="stat-number"><?php echo $reportData['overdue']; ?></div>
            </div>
        </div>

        <!-- Tabela de documentos -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; background: rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden;">
                <thead style="background: rgba(255,255,255,0.1);">
                    <tr>
                        <th style="padding: 1rem;">ID</th>
                        <th style="padding: 1rem;">Título</th>
                        <th style="padding: 1rem;">Status</th>
                        <th style="padding: 1rem;">Categoria</th>
                        <th style="padding: 1rem;">Prioridade</th>
                        <th style="padding: 1rem;">Criado Por</th>
                        <th style="padding: 1rem;">Data Criação</th>
                        <th style="padding: 1rem;">Prazo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reportData['documents'])): ?>
                        <?php foreach ($reportData['documents'] as $doc): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <td style="padding: 1rem;"><?php echo htmlspecialchars($doc['id']); ?></td>
                                <td style="padding: 1rem;"><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td style="padding: 1rem;">
                                    <span class="<?php echo get_status_class($doc['status']); ?>">
                                        <?php echo htmlspecialchars($doc['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;"><?php echo htmlspecialchars($doc['category_name'] ?? 'N/A'); ?></td>
                                <td style="padding: 1rem;">
                                    <span class="priority-badge priority-<?php echo strtolower($doc['priority']); ?>">
                                        <?php echo htmlspecialchars($doc['priority']); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;"><?php echo htmlspecialchars($doc['creator_name']); ?></td>
                                <td style="padding: 1rem;"><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                                <td style="padding: 1rem;">
                                    <?php if ($doc['deadline']): ?>
                                        <span class="<?php echo (strtotime($doc['deadline']) < time() && $doc['status'] !== 'Aprovado' && $doc['status'] !== 'Arquivado') ? 'deadline-overdue' : 'deadline-normal'; ?>">
                                            <?php echo date('d/m/Y', strtotime($doc['deadline'])); ?>
                                        </span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">Nenhum documento encontrado no período.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>