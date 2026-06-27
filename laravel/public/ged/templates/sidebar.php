<?php
$pagina_atual = basename($_SERVER['SCRIPT_NAME']);

// Variáveis para controlar qual menu fica aberto
$paginas_gestao = ['tipos_listar', 'lixeira', 'painel_produtividade', 'ingest', 'ingest_listar', 'ingest_importar_pasta'];
$paginas_relatorios = ['relatorio_vencimentos.php'];
$paginas_registros = ['logs_listar.php', 'logs_sistema.php'];
$paginas_admin = ['usuarios_listar.php', 'funcoes_listar.php', 'configuracoes.php', 'admin_email_templates.php', 'admin_email_template_edit.php', 'admin_email_template_test.php', 'admin_workflows.php', 'admin_workflow_edit.php', 'admin_sistema_checklist.php', 'admin_lgpd.php', 'admin_icp_config.php'];

$gestao_menu_open = in_array($pagina_atual, $paginas_gestao);
$relatorios_menu_open = in_array($pagina_atual, $paginas_relatorios);
$registros_menu_open = in_array($pagina_atual, $paginas_registros);
$admin_menu_open = in_array($pagina_atual, $paginas_admin);
?>
<style>
    .main-sidebar .brand-link { background-color: #1f2937; color: #e5e7eb !important; height: calc(3.5rem + 1px); padding: 0.5rem 1rem; display: flex; align-items: center; border-bottom: 1px solid #2a3a46; box-shadow: 0 2px 8px rgba(0,0,0,0.25); z-index: 10; }
    .main-sidebar .brand-link img { max-height: 52px; width: auto; filter: drop-shadow(0 3px 8px rgba(0,0,0,.35)); }
</style>
<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: #1f2937;">
    <a href="painel_produtividade" class="brand-link d-flex justify-content-center" style="background:#1f2937;">
        <img src="<?= defined('BRAND_LOGO') ? BRAND_LOGO : BASE_URL . '/assets/dist/img/logo_enfasged.svg' ?>" alt="<?= defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED' ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/dist/img/AdminLTELogo.png';">
    </a>
    <div class="sidebar">
    <!-- removido nome de usuário para visual eDok -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item"><a href="painel_produtividade" class="nav-link <?= ($pagina_atual == 'painel_produtividade') ? 'active' : ''; ?>"><i class="nav-icon fas fa-th-large"></i><p>Início</p></a></li>
                <li class="nav-item"><a href="documentos" class="nav-link <?= ($pagina_atual == 'documentos.php') ? 'active' : ''; ?>"><i class="nav-icon fas fa-folder-open"></i><p>Documentos</p></a></li>

                <li class="nav-item has-treeview <?= $relatorios_menu_open ? 'menu-open' : ''; ?>"><a href="#" class="nav-link <?= $relatorios_menu_open ? 'active' : ''; ?>"><i class="nav-icon fas fa-chart-bar"></i><p>Relatórios<i class="right fas fa-angle-left"></i></p></a><ul class="nav nav-treeview"><li class="nav-item"><a href="relatorio_vencimentos" class="nav-link <?= ($pagina_atual == 'relatorio_vencimentos.php') ? 'active' : ''; ?>"><i class="far fa-calendar-check nav-icon"></i><p>Vencimentos</p></a></li></ul></li>
                <li class="nav-item has-treeview <?= $gestao_menu_open ? 'menu-open' : ''; ?>"><a href="#" class="nav-link <?= $gestao_menu_open ? 'active' : ''; ?>"><i class="nav-icon fas fa-archive"></i><p>Gestão de Documentos<i class="right fas fa-angle-left"></i></p></a><ul class="nav nav-treeview"><li class="nav-item"><a href="ingest" class="nav-link <?= in_array($pagina_atual, ['ingest.php','ingest_listar.php','ingest_importar_pasta.php']) ? 'active' : ''; ?>"><i class="fas fa-robot nav-icon"></i><p>Ingest</p></a></li><li class="nav-item"><a href="tipos_listar" class="nav-link <?= ($pagina_atual == 'tipos_listar.php') ? 'active' : ''; ?>"><i class="fas fa-file-signature nav-icon"></i><p>Tipos de Documentos</p></a></li><li class="nav-item"><a href="lixeira" class="nav-link <?= ($pagina_atual == 'lixeira.php') ? 'active' : ''; ?>"><i class="fas fa-trash-alt nav-icon"></i><p>Lixeira</p></a></li></ul></li>
                <li class="nav-item has-treeview <?= $registros_menu_open ? 'menu-open' : ''; ?>"><a href="#" class="nav-link <?= $registros_menu_open ? 'active' : ''; ?>"><i class="nav-icon fas fa-history"></i><p>Registros<i class="right fas fa-angle-left"></i></p></a><ul class="nav nav-treeview"><li class="nav-item"><a href="logs_listar.php" class="nav-link <?= ($pagina_atual == 'logs_listar.php') ? 'active' : ''; ?>"><i class="far fa-circle nav-icon"></i><p>Atividades</p></a></li><li class="nav-item"><a href="logs_sistema.php" class="nav-link <?= ($pagina_atual == 'logs_sistema.php') ? 'active' : ''; ?>"><i class="far fa-circle nav-icon"></i><p>Sistema</p></a></li></ul></li>

                <?php if (function_exists('usuario_tem_permissao') && usuario_tem_permissao('admin.access')): ?>
                <li class="nav-item has-treeview <?= $admin_menu_open ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?= $admin_menu_open ? 'active' : ''; ?>"><i class="nav-icon fas fa-user-shield"></i><p>Administração<i class="right fas fa-angle-left"></i></p></a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="usuarios_listar" class="nav-link <?= ($pagina_atual == 'usuarios_listar.php') ? 'active' : ''; ?>"><i class="fas fa-users nav-icon"></i><p>Usuários</p></a></li>
                        <li class="nav-item"><a href="funcoes_listar" class="nav-link <?= ($pagina_atual == 'funcoes_listar.php') ? 'active' : ''; ?>"><i class="fas fa-user-lock nav-icon"></i><p>Funções & Permissões</p></a></li>
                        <li class="nav-item"><a href="admin_workflows" class="nav-link <?= ($pagina_atual == 'admin_workflows.php' || $pagina_atual == 'admin_workflow_edit.php') ? 'active' : ''; ?>"><i class="fas fa-project-diagram nav-icon"></i><p>Workflows</p></a></li>
                        <li class="nav-item"><a href="configuracoes" class="nav-link <?= ($pagina_atual == 'configuracoes.php') ? 'active' : ''; ?>"><i class="fas fa-cogs nav-icon"></i><p>Configurações</p></a></li>
                        <li class="nav-item"><a href="admin_sistema_checklist" class="nav-link <?= ($pagina_atual == 'admin_sistema_checklist.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-check nav-icon"></i><p>Checklist de Produção</p></a></li>
                        <li class="nav-item"><a href="admin_lgpd" class="nav-link <?= ($pagina_atual == 'admin_lgpd.php') ? 'active' : ''; ?>"><i class="fas fa-user-shield nav-icon"></i><p>LGPD & Privacidade</p></a></li>
                        <li class="nav-item"><a href="admin_icp_config" class="nav-link <?= ($pagina_atual == 'admin_icp_config.php') ? 'active' : ''; ?>"><i class="fas fa-certificate nav-icon"></i><p>Configurar ICP</p></a></li>
                        <?php if (function_exists('usuario_tem_permissao') && usuario_tem_permissao('email.templates.manage')): ?>
                            <li class="nav-item"><a href="admin_email_templates" class="nav-link <?= ($pagina_atual == 'admin_email_templates.php') ? 'active' : ''; ?>"><i class="fas fa-envelope-open-text nav-icon"></i><p>Templates de E-mail</p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
            </ul>
        </nav>
    </div>
</aside>
