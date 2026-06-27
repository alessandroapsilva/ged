<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ENFAS GED')</title>
    <link rel="icon" type="image/svg+xml" href="/assets/dist/img/logo_enfasged.svg">
    <link rel="stylesheet" href="/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/assets/dist/css/ged_styles.css">
    <link rel="stylesheet" href="/assets/dist/css/brand.css">
    <style>
        .main-sidebar .brand-link { background-color: #1f2937; color: #e5e7eb !important; height: calc(3.5rem + 1px); padding: 0.5rem 1rem; display: flex; align-items: center; border-bottom: 1px solid #2a3a46; box-shadow: 0 2px 8px rgba(0,0,0,0.25); z-index: 10; }
        .main-sidebar .brand-link img { max-height: 52px; width: auto; filter: drop-shadow(0 3px 8px rgba(0,0,0,.35)); }
    </style>
    <script>
        (function(){
            try {
                var theme = localStorage.getItem('theme');
                if (!theme) { localStorage.setItem('theme', 'gray'); theme = 'gray'; }
                if (theme === 'gray') { document.documentElement.classList.add('theme-gray'); }
            } catch (e) {}
        })();
    </script>
</head>
<body class="hold-transition sidebar-mini layout-footer-fixed dark-mode">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light" style="background:#ffffff; border-bottom:1px solid #e5e7eb;">
        <a href="/documentos" class="navbar-brand d-none d-sm-inline-flex align-items-center">
            <img src="/assets/dist/img/logo_enfasged.svg" alt="ENFAS GED" style="height:36px; width:auto; filter: drop-shadow(0 2px 6px rgba(0,0,0,.35));">
        </a>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" title="Menu"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <form action="/buscar" method="get" class="form-inline mx-auto d-none d-md-flex" style="max-width:560px; width:100%;">
            <div class="input-group input-group-sm w-100">
                <input class="form-control form-control-navbar" type="search" name="q" placeholder="Digite sua pesquisa e tecle enter..." aria-label="Pesquisar">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit" title="Buscar"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item">
                <a class="nav-link" href="#" id="btn-toggle-theme" title="Alternar tema"><i class="fas fa-lightbulb"></i></a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: #1f2937;">
        <a href="/documentos" class="brand-link d-flex justify-content-center" style="background:#1f2937;">
            <img src="/assets/dist/img/logo_enfasged.svg" alt="ENFAS GED">
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu" data-accordion="false">
                    <li class="nav-item"><a href="/documentos" class="nav-link"><i class="nav-icon fas fa-folder-open"></i><p>Documentos</p></a></li>
                    <li class="nav-item"><a href="/tipos_listar" class="nav-link"><i class="fas fa-file-signature nav-icon"></i><p>Tipos de Documentos</p></a></li>
                    <li class="nav-item"><a href="/ged/public/lixeira.php" class="nav-link"><i class="fas fa-trash-alt nav-icon"></i><p>Lixeira</p></a></li>
                    <li class="nav-item"><a href="/ged/public/ingest.php" class="nav-link"><i class="fas fa-robot nav-icon"></i><p>Ingest</p></a></li>
                    <li class="nav-item"><a href="/ged/public/logs_listar.php" class="nav-link"><i class="fas fa-history nav-icon"></i><p>Atividades</p></a></li>
                    <li class="nav-item"><a href="/ged/public/usuarios_listar.php" class="nav-link"><i class="fas fa-users nav-icon"></i><p>Usuários</p></a></li>
                    <li class="nav-item"><a href="/ged/public/funcoes_listar.php" class="nav-link"><i class="fas fa-user-lock nav-icon"></i><p>Funções & Permissões</p></a></li>
                    <li class="nav-item"><a href="/ged/public/configuracoes.php" class="nav-link"><i class="fas fa-cogs nav-icon"></i><p>Configurações</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('btn-toggle-theme');
  if(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); var t=localStorage.getItem('theme')==='gray'?'default':'gray'; localStorage.setItem('theme',t); location.reload(); }); }
});
</script>
</body>
</html>

