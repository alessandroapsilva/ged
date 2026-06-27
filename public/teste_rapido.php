<?php
/**
 * TESTE RÁPIDO DO SISTEMA
 * ========================
 * Este arquivo verifica rapidamente se todos os componentes essenciais estão funcionando.
 */

require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$testes_passar = [];
$testes_falhar = [];

// 1. Teste PHP
$testes_passar['PHP'] = phpversion() >= '8.0' ? '✅ OK' : '❌ FALHA';

// 2. Teste BD
try {
    $test = $pdo->query("SELECT 1")->fetch();
    $testes_passar['Banco de Dados'] = '✅ OK';
} catch (Exception $e) {
    $testes_falhar['Banco de Dados'] = $e->getMessage();
}

// 3. Teste Sessão
$testes_passar['Sessão'] = isset($_SESSION['user_id']) ? '✅ OK' : '❌ FALHA';

// 4. Teste Arquivos
$files = [
    'CSS Professional' => 'css/professional.css',
    'JS Dynamsoft' => 'js/dynamsoft.webtwain.min.js',
    'Layout Helper' => '../helpers/ProfessionalLayout.php'
];

foreach ($files as $nome => $arquivo) {
    if (file_exists($arquivo)) {
        $testes_passar[$nome] = '✅ OK';
    } else {
        $testes_falhar[$nome] = 'Arquivo não encontrado';
    }
}

// 5. Teste Dados
try {
    $docs = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $testes_passar['Documentos'] = '✅ ' . number_format($docs, 0, ',', '.');
} catch (Exception $e) {
    $testes_falhar['Documentos'] = $e->getMessage();
}

// 6. Teste Diretórios
$dirs = [
    'Uploads' => 'uploads/',
    'Storage' => 'storage/'
];

foreach ($dirs as $nome => $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        $testes_passar[$nome] = '✅ Gravável';
    } else {
        $testes_falhar[$nome] = 'Sem permissão';
    }
}

// Determinar status geral
$status_geral = empty($testes_falhar) ? 'OK' : 'AVISO';
$cor_status = $status_geral === 'OK' ? '#10b981' : '#f59e0b';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Rápido</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f4f6;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .status-badge {
            display: inline-block;
            background: <?php echo $cor_status; ?>;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .content {
            padding: 30px;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-label {
            font-weight: 500;
            color: #1f2937;
        }
        .test-result {
            font-family: monospace;
            font-size: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .status-ok {
            color: #10b981;
        }
        .status-fail {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Teste Rápido do Sistema</h1>
            <div class="status-badge">
                <?php 
                if ($status_geral === 'OK') {
                    echo '✅ TODOS OS TESTES PASSARAM';
                } else {
                    echo '⚠️ ALGUNS TESTES FALHARAM';
                }
                ?>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($testes_passar)): ?>
            <div class="section">
                <div class="section-title">✅ Testes OK</div>
                <?php foreach ($testes_passar as $nome => $resultado): ?>
                <div class="test-item">
                    <span class="test-label"><?php echo htmlspecialchars($nome); ?></span>
                    <span class="test-result status-ok"><?php echo $resultado; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($testes_falhar)): ?>
            <div class="section">
                <div class="section-title">❌ Testes com Falha</div>
                <?php foreach ($testes_falhar as $nome => $erro): ?>
                <div class="test-item">
                    <span class="test-label"><?php echo htmlspecialchars($nome); ?></span>
                    <span class="test-result status-fail">❌ <?php echo htmlspecialchars($erro); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p><strong>Resumo:</strong> <?php echo count($testes_passar); ?> OK, <?php echo count($testes_falhar); ?> Falhas</p>
            <p style="margin-top: 15px;">
                <a href="painel_produtividade_moderno.php">← Voltar ao Dashboard</a> | 
                <a href="verificacao.php">Ver Menu Completo →</a>
            </p>
        </div>
    </div>
</body>
</html>
