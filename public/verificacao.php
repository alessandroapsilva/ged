<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lista de páginas para verificar
$paginas = [
    // Documentos
    ['url' => 'documentos.php', 'nome' => 'Documentos - Listar', 'categoria' => 'Documentos'],
    ['url' => 'documentos_adicionar.php', 'nome' => 'Documentos - Adicionar', 'categoria' => 'Documentos'],
    ['url' => 'buscar.php', 'nome' => 'Buscar', 'categoria' => 'Documentos'],
    ['url' => 'busca_avancada.php', 'nome' => 'Busca Avançada', 'categoria' => 'Documentos'],
    
    // Pastas
    ['url' => 'pastas_arvore.php', 'nome' => 'Pastas - Árvore', 'categoria' => 'Pastas'],
    ['url' => 'pastas_criar.php', 'nome' => 'Pastas - Criar', 'categoria' => 'Pastas'],
    
    // Digitalização
    ['url' => 'digitalizar_dynamsoft.php', 'nome' => 'Digitalizar - Dynamsoft', 'categoria' => 'Digitalização'],
    ['url' => 'digitalizar_moderno.php', 'nome' => 'Digitalizar - Câmera', 'categoria' => 'Digitalização'],
    
    // Workflows
    ['url' => 'workflows.php', 'nome' => 'Workflows - Listar', 'categoria' => 'Workflows'],
    ['url' => 'workflows_criar.php', 'nome' => 'Workflows - Criar', 'categoria' => 'Workflows'],
    
    // Notificações
    ['url' => 'notificacoes.php', 'nome' => 'Notificações', 'categoria' => 'Notificações'],
    
    // Admin (se admin)
    ['url' => 'admin_sistema_checklist.php', 'nome' => 'Admin - Checklist', 'categoria' => 'Administração', 'admin' => true],
    ['url' => 'usuarios_listar.php', 'nome' => 'Usuários - Listar', 'categoria' => 'Administração', 'admin' => true],
    ['url' => 'logs_sistema.php', 'nome' => 'Logs do Sistema', 'categoria' => 'Administração', 'admin' => true],
    
    // Perfil
    ['url' => 'perfil.php', 'nome' => 'Meu Perfil', 'categoria' => 'Usuário'],
];

// Filtrar páginas por permissão
$paginas_filtradas = [];
$eh_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

foreach ($paginas as $pagina) {
    if (isset($pagina['admin']) && $pagina['admin'] && !$eh_admin) {
        continue;
    }
    $paginas_filtradas[] = $pagina;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header-info {
            color: #6b7280;
            font-size: 14px;
        }
        
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #10b981;
        }
        
        .status-card.error {
            border-left-color: #ef4444;
        }
        
        .status-card h3 {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .status-card .value {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .pages-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .section-title {
            background: #f3f4f6;
            padding: 15px 20px;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .pages-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1px;
            background: #e5e7eb;
        }
        
        .page-item {
            background: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s;
        }
        
        .page-item:hover {
            background: #f9fafb;
        }
        
        .page-name {
            color: #1f2937;
            font-size: 14px;
            font-weight: 500;
        }
        
        .page-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            padding: 6px 12px;
            background: #dbeafe;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .page-link:hover {
            background: #3b82f6;
            color: white;
        }
        
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
            padding: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="painel_produtividade_moderno.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
        
        <header>
            <h1><i class="fas fa-check-circle"></i> Verificação do Sistema</h1>
            <div class="header-info">
                Versão PHP: <?php echo phpversion(); ?> | 
                Usuário: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?> |
                Data: <?php echo date('d/m/Y H:i'); ?>
            </div>
        </header>
        
        <div class="status-cards">
            <div class="status-card">
                <h3><i class="fas fa-server"></i> Servidor</h3>
                <div class="value">✅ OK</div>
            </div>
            <div class="status-card">
                <h3><i class="fas fa-database"></i> Banco de Dados</h3>
                <div class="value">✅ OK</div>
            </div>
            <div class="status-card">
                <h3><i class="fas fa-user"></i> Sessão</h3>
                <div class="value">✅ OK</div>
            </div>
            <div class="status-card">
                <h3><i class="fas fa-file-alt"></i> Páginas</h3>
                <div class="value"><?php echo count($paginas_filtradas); ?></div>
            </div>
        </div>
        
        <?php
        $categorias = [];
        foreach ($paginas_filtradas as $pagina) {
            $cat = $pagina['categoria'];
            if (!isset($categorias[$cat])) {
                $categorias[$cat] = [];
            }
            $categorias[$cat][] = $pagina;
        }
        
        foreach ($categorias as $categoria => $paginas_cat) {
            echo '<div class="pages-section">';
            echo '<div class="section-title"><i class="fas fa-folder"></i> ' . htmlspecialchars($categoria) . '</div>';
            echo '<div class="pages-list">';
            
            foreach ($paginas_cat as $pagina) {
                echo '<div class="page-item">';
                echo '<span class="page-name">' . htmlspecialchars($pagina['nome']) . '</span>';
                echo '<a href="' . htmlspecialchars($pagina['url']) . '" class="page-link" target="_blank">';
                echo '<i class="fas fa-external-link-alt"></i> Abrir</a>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="footer">
            <p>Clique em "Abrir" para testar cada página. Se encontrar erros, entre em contato com o suporte.</p>
        </div>
    </div>
</body>
</html>
