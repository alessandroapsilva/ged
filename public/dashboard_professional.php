<?php
header('Content-Type: text/html; charset=utf-8');
/**
 * Dashboard Profissional - Exemplo de Pagina Modernizada
 */

require_once '../core/init_professional.php';

requireAuth();

$layout = new ProfessionalLayout('Dashboard');
$layout->addBreadcrumb('Início', '/index.php');
$layout->setSubtitle('Bem-vindo de volta, ' . htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'));

$db = DatabaseManager::getInstance();

// Obtém estatísticas
$stats = [
    'total_docs' => $db->count('documentos'),
    'total_folders' => $db->count('pastas'),
    'docs_today' => $db->count('documentos', ['DATE(created_at)' => date('Y-m-d')]),
    'pending_workflows' => $db->count('workflows', ['status' => 'pending'])
];

// Constrói o conteúdo
ob_start();
?>

<!-- Flash Message -->
<?php echo renderFlashMessage(); ?>

<!-- Grid de Estatísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
    
    <!-- Card Documentos -->
    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);">Total de Documentos</p>
                    <h2 style="color: var(--color-primary); font-size: 2.5rem; margin: 0;">
                        <?php echo number_format($stats['total_docs']); ?>
                    </h2>
                </div>
                <div style="background: rgba(37, 99, 235, 0.1); width: 50px; height: 50px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-file-alt" style="font-size: 1.5rem; color: var(--color-primary);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Pastas -->
    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);">Total de Pastas</p>
                    <h2 style="color: var(--color-secondary); font-size: 2.5rem; margin: 0;">
                        <?php echo number_format($stats['total_folders']); ?>
                    </h2>
                </div>
                <div style="background: rgba(124, 58, 237, 0.1); width: 50px; height: 50px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-folder" style="font-size: 1.5rem; color: var(--color-secondary);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Hoje -->
    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);">Documentos Hoje</p>
                    <h2 style="color: var(--color-success); font-size: 2.5rem; margin: 0;">
                        <?php echo number_format($stats['docs_today']); ?>
                    </h2>
                </div>
                <div style="background: rgba(16, 185, 129, 0.1); width: 50px; height: 50px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-today" style="font-size: 1.5rem; color: var(--color-success);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Workflows -->
    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);">Workflows Pendentes</p>
                    <h2 style="color: var(--color-warning); font-size: 2.5rem; margin: 0;">
                        <?php echo number_format($stats['pending_workflows']); ?>
                    </h2>
                </div>
                <div style="background: rgba(245, 158, 11, 0.1); width: 50px; height: 50px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-tasks" style="font-size: 1.5rem; color: var(--color-warning);"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">Ações Rápidas</h3>
    </div>
    <div class="card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-md);">
        <a href="/documentos_adicionar.php" class="btn btn-primary" style="justify-content: center;">
            <i class="fas fa-plus"></i> Novo Documento
        </a>
        <a href="/pastas_criar.php" class="btn btn-primary" style="justify-content: center;">
            <i class="fas fa-folder-plus"></i> Nova Pasta
        </a>
        <a href="/buscar.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-search"></i> Buscar
        </a>
        <a href="/workflows.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-diagram-project"></i> Workflows
        </a>
    </div>
</div>

<!-- Documentos Recentes -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Documentos Recentes</h3>
        <a href="/documentos.php" style="color: var(--color-primary); text-decoration: none; font-size: var(--font-size-sm);">Ver todos →</a>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Tamanho</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_docs = $db->query(
                    "SELECT id, titulo, tipo, tamanho, created_at FROM documentos ORDER BY created_at DESC LIMIT 5"
                );
                
                if (empty($recent_docs)):
                ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: var(--spacing-lg);">
                        <p style="color: var(--color-text-secondary);">Nenhum documento ainda</p>
                        <a href="/documentos_adicionar.php" class="btn btn-primary btn-sm" style="margin-top: var(--spacing-md); display: inline-block;">
                            + Criar Primeiro Documento
                        </a>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recent_docs as $doc): ?>
                    <tr>
                        <td>
                            <a href="/documentos_ver.php?id=<?php echo $doc['id']; ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">
                                <?php echo htmlspecialchars(substr($doc['titulo'], 0, 40)); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($doc['tipo'] ?? 'N/A'); ?></span>
                        </td>
                        <td><?php echo formatBytes($doc['tamanho'] ?? 0); ?></td>
                        <td><?php echo formatDate($doc['created_at'], 'd/m/Y H:i'); ?></td>
                        <td>
                            <a href="/documentos_ver.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
$layout->setContent($content);
echo $layout->render();
?>
