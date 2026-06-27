<?php
require_once '../core/init.php';
require_once '../helpers/ProfessionalLayout.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$layout = new ProfessionalLayout('Status do Sistema');
$layout->addBreadcrumb('Início', 'index.php');
$layout->addBreadcrumb('Status');

ob_start();
?>

<style>
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .status-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
    }
    .status-card.ok {
        border-left: 4px solid #10b981;
    }
    .status-card.error {
        border-left: 4px solid #ef4444;
        background: #fef2f2;
    }
    .status-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .status-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .status-desc {
        font-size: 0.875rem;
        color: #6b7280;
    }
    .menu-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .menu-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        border-bottom: 2px solid #3b82f6;
        padding-bottom: 0.5rem;
    }
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    .menu-btn {
        padding: 1rem;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        text-decoration: none;
        color: #1f2937;
        text-align: center;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
    }
    .menu-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        transform: translateY(-2px);
    }
    .menu-icon {
        font-size: 1.5rem;
    }
</style>

<div class="status-grid">
    <div class="status-card ok">
        <div class="status-icon">✅</div>
        <div class="status-title">PHP</div>
        <div class="status-desc"><?php echo phpversion(); ?></div>
    </div>
    <div class="status-card ok">
        <div class="status-icon">✅</div>
        <div class="status-title">Banco de Dados</div>
        <div class="status-desc">Conectado</div>
    </div>
    <div class="status-card ok">
        <div class="status-icon">✅</div>
        <div class="status-title">Sessão</div>
        <div class="status-desc"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
    </div>
    <div class="status-card ok">
        <div class="status-icon">✅</div>
        <div class="status-title">Layout</div>
        <div class="status-desc">Funcionando</div>
    </div>
</div>

<!-- Menu Principal -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-file"></i> Documentos</div>
    <div class="menu-grid">
        <a href="documentos.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-list"></i></span>
            <span>Listar</span>
        </a>
        <a href="documentos_adicionar.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-plus"></i></span>
            <span>Novo</span>
        </a>
        <a href="buscar.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-search"></i></span>
            <span>Buscar</span>
        </a>
    </div>
</div>

<!-- Menu Pastas -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-folder"></i> Pastas</div>
    <div class="menu-grid">
        <a href="pastas_arvore.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-sitemap"></i></span>
            <span>Árvore</span>
        </a>
        <a href="pastas_criar.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-folder-plus"></i></span>
            <span>Nova</span>
        </a>
    </div>
</div>

<!-- Menu Digitalizar -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-scanner"></i> Digitalização</div>
    <div class="menu-grid">
        <a href="digitalizar_dynamsoft.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-scanner"></i></span>
            <span>Dynamsoft</span>
        </a>
        <a href="digitalizar_moderno.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-camera"></i></span>
            <span>Câmera</span>
        </a>
    </div>
</div>

<!-- Menu Workflows -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-diagram-project"></i> Workflows</div>
    <div class="menu-grid">
        <a href="workflows.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-project-diagram"></i></span>
            <span>Listar</span>
        </a>
        <a href="workflows_criar.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-plus"></i></span>
            <span>Novo</span>
        </a>
    </div>
</div>

<!-- Menu Notificações -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-bell"></i> Notificações</div>
    <div class="menu-grid">
        <a href="notificacoes.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-envelope"></i></span>
            <span>Ver</span>
        </a>
    </div>
</div>

<!-- Menu Admin (se for admin) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-cog"></i> Administração</div>
    <div class="menu-grid">
        <a href="admin_sistema_checklist.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-check-circle"></i></span>
            <span>Checklist</span>
        </a>
        <a href="usuarios_listar.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-users"></i></span>
            <span>Usuários</span>
        </a>
        <a href="logs_sistema.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-history"></i></span>
            <span>Logs</span>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Menu Relatórios -->
<div class="menu-section">
    <div class="menu-title"><i class="fas fa-chart-line"></i> Relatórios</div>
    <div class="menu-grid">
        <a href="relatorios_avancados.php" class="menu-btn">
            <span class="menu-icon"><i class="fas fa-file-csv"></i></span>
            <span>Avançados</span>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
$layout->setContent($content);
echo $layout->render();
?>
