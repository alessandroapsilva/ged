<?php
// templates/sidebar.php - Layout Moderno eDok (Light)
// Garantir consistência com ProfessionalLayout.php

$current_page = basename($_SERVER['PHP_SELF'], ".php");
$assetBase = defined('BASE_URL') ? BASE_URL . '/public' : '/ged/public';

// Helper para verificar ativo
function is_active($pages) {
    global $current_page;
    if (!is_array($pages)) $pages = [$pages];
    foreach ($pages as $p) {
        if ($current_page === $p || strpos($current_page, $p) !== false) return 'active';
    }
    return '';
}

function is_menu_open($pages) {
    global $current_page;
    if (!is_array($pages)) $pages = [$pages];
    foreach ($pages as $p) {
        if (in_array($current_page, $pages) || strpos($current_page, $p) !== false) return 'menu-open';
    }
    return '';
}
?>
<style>
    /* Override para garantir estilo Light na Sidebar */
    .main-sidebar { background-color: #fff !important; width: 260px; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
    .nav-sidebar .nav-item .nav-link { color: #555 !important; }
    .nav-sidebar .nav-item .nav-link.active { background-color: #0066cc !important; color: #fff !important; box-shadow: 0 4px 12px rgba(0,102,204, 0.25); }
    .nav-sidebar .nav-item .nav-link:hover { color: #0066cc !important; background-color: rgba(0,102,204, 0.05) !important; }
    .nav-sidebar .nav-header { color: #999; font-weight: 700; font-size: 0.75rem; padding-top: 1.5rem; }
    .brand-link { border-bottom: 1px solid #f0f0f0 !important; }
    .brand-text { color: #333 !important; font-family: 'Space Grotesk', sans-serif; font-weight: 700; }
</style>

<aside class="main-sidebar sidebar-light-primary elevation-4" style="border-right: 1px solid #e9ecef;">
    <!-- Brand Logo -->
    <a href="<?= $assetBase ?>/painel_produtividade_moderno.php" class="brand-link">
        <div class="brand-image d-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm" style="width: 33px; height: 33px; font-weight: 700; font-size: 16px; margin-left: 0.8rem;">
            E
        </div>
        <span class="brand-text font-weight-light ml-2">GED <span class="font-weight-bold" style="color: #0066cc;">eDok</span></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-header">PRINCIPAL</li>
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= $assetBase ?>/painel_produtividade_moderno.php" class="nav-link <?= is_active(['painel_produtividade', 'dashboard']) ?>">
                        <i class="nav-icon fas fa-th-large"></i>
                        <p>Visão Geral</p>
                    </a>
                </li>

                <!-- Documentos -->
                <li class="nav-item">
                    <a href="<?= $assetBase ?>/documentos.php" class="nav-link <?= is_active(['documentos', 'documento_detalhes', 'documentos_ver']) ?>">
                        <i class="nav-icon fas fa-folder-open"></i>
                        <p>Meus Documentos</p>
                    </a>
                </li>

                <!-- Digitalização (MODERNO) -->
                <li class="nav-item">
                    <a href="<?= $assetBase ?>/digitalizar_moderno.php" class="nav-link <?= is_active(['digitalizar', 'digitalizar_moderno']) ?>">
                        <i class="nav-icon fas fa-qrcode"></i>
                        <p>Digitalização</p>
                    </a>
                </li>

                <!-- Busca -->
                <li class="nav-item">
                    <a href="<?= $assetBase ?>/buscar.php" class="nav-link <?= is_active('buscar') ?>">
                        <i class="nav-icon fas fa-search"></i>
                        <p>Busca Avançada</p>
                    </a>
                </li>

                <li class="nav-header">GESTÃO</li>

                <!-- Gestão Treeview -->
                <li class="nav-item has-treeview <?= is_menu_open(['ingest', 'tipos_listar', 'lixeira', 'ingest_listar']) ?>">
                    <a href="#" class="nav-link <?= is_active(['ingest', 'tipos_listar', 'lixeira', 'ingest_listar']) ?>">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>
                            Arquivamento
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/ingest.php" class="nav-link <?= is_active(['ingest', 'ingest_listar']) ?>">
                                <i class="fas fa-robot nav-icon"></i>
                                <p>Ingestão Automática</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/tipos_listar.php" class="nav-link <?= is_active('tipos_listar') ?>">
                                <i class="fas fa-tags nav-icon"></i>
                                <p>Tipos de Documento</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/lixeira.php" class="nav-link <?= is_active('lixeira') ?>">
                                <i class="fas fa-trash-alt nav-icon"></i>
                                <p>Lixeira</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Relatórios -->
                <li class="nav-item has-treeview <?= is_menu_open(['relatorio', 'relatorios']) ?>">
                    <a href="#" class="nav-link <?= is_active(['relatorio', 'relatorios']) ?>">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>
                            Relatórios
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/relatorio_vencimentos.php" class="nav-link <?= is_active('relatorio_vencimentos') ?>">
                                <i class="far fa-calendar-check nav-icon"></i>
                                <p>Vencimentos</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Admin Section -->
                <?php if (function_exists('usuario_tem_permissao') && usuario_tem_permissao('admin.access')): ?>
                <li class="nav-header">SISTEMA</li>
                <li class="nav-item has-treeview <?= is_menu_open(['usuarios_listar', 'configuracoes', 'logs_sistema', 'funcoes_listar', 'admin_workflows']) ?>">
                    <a href="#" class="nav-link <?= is_active(['usuarios_listar', 'configuracoes', 'logs_sistema', 'funcoes_listar', 'admin_workflows']) ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            Administração
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/usuarios_listar.php" class="nav-link <?= is_active('usuarios_listar') ?>">
                                <i class="fas fa-users nav-icon"></i>
                                <p>Usuários</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/funcoes_listar.php" class="nav-link <?= is_active('funcoes_listar') ?>">
                                <i class="fas fa-user-shield nav-icon"></i>
                                <p>Permissões</p>
                            </a>
                        </li>
                         <li class="nav-item">
                            <a href="<?= $assetBase ?>/configuracoes.php" class="nav-link <?= is_active('configuracoes') ?>">
                                <i class="fas fa-sliders-h nav-icon"></i>
                                <p>Configurações</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= $assetBase ?>/logs_sistema.php" class="nav-link <?= is_active('logs_sistema') ?>">
                                <i class="fas fa-file-code nav-icon"></i>
                                <p>Logs</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>