<?php
/**
 * Modern Header Component
 * Incluir em templates com: <?php include 'includes/header.php'; ?>
 */

$user_avatar = isset($_SESSION['user_nome']) ? strtoupper(substr($_SESSION['user_nome'], 0, 1)) : 'U';
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Usuário';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header>
    <div class="navbar">
        <!-- Logo -->
        <div class="logo">
            <a href="/ged/documentos">
                <?php if (file_exists(__DIR__ . '/../assets/dist/img/logo_enfasged.svg')): ?>
                    <img src="/ged/assets/dist/img/logo_enfasged.svg" alt="GED">
                <?php else: ?>
                    <span>📄 GED</span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav-menu">
            <a href="/ged/documentos" class="<?= $current_page === 'documentos.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Documentos
            </a>
            <a href="/ged/painel_produtividade" class="<?= $current_page === 'painel_produtividade.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="/ged/buscar" class="<?= $current_page === 'busca_avancada.php' ? 'active' : '' ?>">
                <i class="fas fa-search"></i> Busca
            </a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="/ged/usuarios_listar" class="<?= strpos($current_page, 'usuarios') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Administração
                </a>
            <?php endif; ?>
        </nav>

        <!-- Right Section -->
        <div class="nav-right">
            <!-- Theme Toggle -->
            <button class="theme-toggle" aria-label="Alternar tema">🌙</button>

            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-profile">
                    <div class="user-avatar"><?= $user_avatar ?></div>
                    <div class="user-name"><?= $user_name ?></div>
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </div>

                <div class="dropdown-menu">
                    <a href="/ged/perfil">
                        <i class="fas fa-user"></i> Meu Perfil
                    </a>
                    <a href="/ged/configuracoes">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a href="/ged/sobre">
                        <i class="fas fa-info-circle"></i> Sobre
                    </a>
                    <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #e5e7eb;">
                    <a href="/ged/logout">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script src="/ged/public/js/app.js" defer></script>
