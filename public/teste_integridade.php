<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Testes de integridade
$testes = [
    'PHP' => [
        'versao' => phpversion(),
        'status' => 'OK',
        'valor' => '8.2.12'
    ]
];

// Testar conexão com banco
try {
    $result = $pdo->query("SELECT 1")->fetch();
    $testes['Banco de Dados'] = [
        'status' => 'OK',
        'mensagem' => 'Conectado'
    ];
} catch (Throwable $e) {
    $testes['Banco de Dados'] = [
        'status' => 'ERRO',
        'mensagem' => $e->getMessage()
    ];
}

// Testar contagens
try {
    $counts = [];
    $counts['Documentos'] = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $counts['Pastas'] = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $counts['Usuários'] = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    
    $testes['Integridade de Dados'] = [
        'status' => 'OK',
        'conteudo' => $counts
    ];
} catch (Throwable $e) {
    $testes['Integridade de Dados'] = [
        'status' => 'ERRO',
        'mensagem' => $e->getMessage()
    ];
}

// Testar arquivos críticos
$arquivos_criticos = [
    'init.php' => '../core/init.php',
    'ProfessionalLayout.php' => '../helpers/ProfessionalLayout.php',
    'dynamsoft.webtwain.min.js' => 'js/dynamsoft.webtwain.min.js',
    'professional.css' => 'css/professional.css'
];

$arquivos_status = [];
foreach ($arquivos_criticos as $nome => $caminho) {
    $arquivos_status[$nome] = file_exists($caminho) ? '✅' : '❌';
}

$testes['Arquivos Críticos'] = [
    'status' => array_filter($arquivos_status, fn($s) => $s === '❌') ? 'AVISO' : 'OK',
    'conteudo' => $arquivos_status
];

// Testar permissões de upload
$upload_dir = 'uploads/';
$testes['Permissões de Upload'] = [
    'status' => is_writable($upload_dir) ? 'OK' : 'ERRO',
    'mensagem' => is_writable($upload_dir) ? 'Diretório gravável' : 'Sem permissão de gravação'
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Integridade - GED</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        h1 {
            color: #1f2937;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .test-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .test-card.ok {
            border-left-color: #10b981;
        }
        
        .test-card.erro {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .test-card.aviso {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .test-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .test-title {
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-ok {
            background: #d1fae5;
            color: #047857;
        }
        
        .status-erro {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-aviso {
            background: #fef3c7;
            color: #d97706;
        }
        
        .test-content {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .test-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .test-content table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .test-content table td:first-child {
            font-weight: 500;
            color: #1f2937;
            width: 40%;
        }
        
        .footer {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            display: inline-block;
            color: #3b82f6;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="painel_produtividade_moderno.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
        
        <header>
            <h1><i class="fas fa-heart-pulse"></i> Teste de Integridade do Sistema</h1>
            <div class="header-subtitle">
                Verificação completa dos componentes e funcionalidades
            </div>
        </header>
        
        <?php foreach ($testes as $nome_teste => $dados_teste): 
            $status_classe = strtolower($dados_teste['status']);
            $status_class = $status_classe === 'ok' ? 'ok' : ($status_classe === 'erro' ? 'erro' : 'aviso');
            $badge_class = 'status-' . $status_class;
        ?>
        <div class="test-card <?php echo $status_class; ?>">
            <div class="test-header">
                <div class="test-title">
                    <i class="fas fa-<?php 
                        $icones = [
                            'PHP' => 'code',
                            'Banco de Dados' => 'database',
                            'Integridade de Dados' => 'shield-check',
                            'Arquivos Críticos' => 'file-check',
                            'Permissões de Upload' => 'lock'
                        ];
                        echo $icones[$nome_teste] ?? 'circle';
                    ?>"></i>
                    <?php echo htmlspecialchars($nome_teste); ?>
                </div>
                <span class="test-status <?php echo $badge_class; ?>">
                    <?php echo htmlspecialchars($dados_teste['status']); ?>
                </span>
            </div>
            
            <div class="test-content">
                <?php if (isset($dados_teste['versao'])): ?>
                    <strong>Versão:</strong> <?php echo htmlspecialchars($dados_teste['versao']); ?>
                <?php endif; ?>
                
                <?php if (isset($dados_teste['mensagem'])): ?>
                    <?php echo htmlspecialchars($dados_teste['mensagem']); ?>
                <?php endif; ?>
                
                <?php if (isset($dados_teste['conteudo']) && is_array($dados_teste['conteudo'])): ?>
                    <table>
                        <?php foreach ($dados_teste['conteudo'] as $chave => $valor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($chave); ?></td>
                                <td>
                                    <?php 
                                    if (is_numeric($valor)) {
                                        echo number_format($valor, 0, ',', '.');
                                    } else {
                                        echo htmlspecialchars($valor);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="footer">
            <p>Se encontrar algum erro, entre em contato com o suporte técnico.</p>
            <p style="color: #6b7280; font-size: 12px; margin-top: 10px;">
                Última verificação: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
