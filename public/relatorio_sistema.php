<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Coleta de informações do sistema
$sistema = [
    'php_version' => phpversion(),
    'php_sapi' => php_sapi_name(),
    'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'memoria_limite' => ini_get('memory_limit'),
    'max_upload' => ini_get('upload_max_filesize'),
    'tempo_max' => ini_get('max_execution_time'),
    'charset' => ini_get('default_charset'),
];

// Informações do usuário
$usuario = [
    'id' => $_SESSION['user_id'] ?? 'N/A',
    'nome' => $_SESSION['user_name'] ?? 'N/A',
    'email' => $_SESSION['user_email'] ?? 'N/A',
    'role' => $_SESSION['user_role'] ?? 'N/A',
    'ultimo_login' => $_SESSION['login_time'] ?? 'N/A'
];

// Estatísticas do banco
$stats = [];
try {
    $stats['documentos'] = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $stats['pastas'] = $pdo->query("SELECT COUNT(*) FROM pastas WHERE apagado_em IS NULL")->fetchColumn();
    $stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $stats['tipos'] = $pdo->query("SELECT COUNT(*) FROM tipos_documento")->fetchColumn();
    $stats['workflows'] = $pdo->query("SELECT COUNT(*) FROM workflows")->fetchColumn();
} catch (Throwable $e) {
    $stats['erro'] = $e->getMessage();
}

// Módulos instalados
$modulos = [
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'GD' => extension_loaded('gd'),
    'CURL' => extension_loaded('curl'),
    'JSON' => extension_loaded('json'),
    'Date' => extension_loaded('date'),
    'SPL' => extension_loaded('spl'),
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório do Sistema</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            padding: 20px;
            color: #1f2937;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header-info {
            color: #6b7280;
            font-size: 12px;
        }
        
        .print-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .print-btn:hover {
            background: #2563eb;
        }
        
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        table tr:last-child {
            border-bottom: none;
        }
        
        table td {
            padding: 12px;
        }
        
        table td:first-child {
            font-weight: 500;
            width: 40%;
            color: #374151;
        }
        
        table td:last-child {
            color: #6b7280;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #047857;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #0369a1;
        }
        
        .back-link {
            color: #3b82f6;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media print {
            body { background: white; }
            .print-btn, .back-link { display: none; }
            .section { box-shadow: none; border: 1px solid #e5e7eb; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="painel_produtividade_moderno.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
        
        <div class="header">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Relatório do Sistema</h1>
                <div class="header-info">
                    Gerado em <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </div>
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
        
        <!-- Informações do Sistema -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-server"></i> Informações do Sistema
            </div>
            <table>
                <tr>
                    <td>Versão PHP</td>
                    <td><?php echo $sistema['php_version']; ?></td>
                </tr>
                <tr>
                    <td>SAPI</td>
                    <td><?php echo $sistema['php_sapi']; ?></td>
                </tr>
                <tr>
                    <td>Servidor Web</td>
                    <td><?php echo $sistema['servidor']; ?></td>
                </tr>
                <tr>
                    <td>Limite de Memória</td>
                    <td><?php echo $sistema['memoria_limite']; ?></td>
                </tr>
                <tr>
                    <td>Tamanho Máximo de Upload</td>
                    <td><?php echo $sistema['max_upload']; ?></td>
                </tr>
                <tr>
                    <td>Tempo Máximo de Execução</td>
                    <td><?php echo $sistema['tempo_max']; ?> segundos</td>
                </tr>
                <tr>
                    <td>Charset Padrão</td>
                    <td><?php echo $sistema['charset']; ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Informações do Usuário -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-user-circle"></i> Informações do Usuário
            </div>
            <table>
                <tr>
                    <td>ID</td>
                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                </tr>
                <tr>
                    <td>Nome</td>
                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                </tr>
                <tr>
                    <td>Perfil</td>
                    <td>
                        <span class="badge <?php echo $usuario['role'] === 'admin' ? 'badge-info' : 'badge-success'; ?>">
                            <?php echo htmlspecialchars($usuario['role']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Módulos PHP -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-puzzle-piece"></i> Módulos PHP
            </div>
            <table>
                <?php foreach ($modulos as $modulo => $ativado): ?>
                <tr>
                    <td><?php echo $modulo; ?></td>
                    <td>
                        <span class="badge <?php echo $ativado ? 'badge-success' : 'badge-error'; ?>">
                            <?php echo $ativado ? '✓ Ativado' : '✗ Desativado'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <!-- Estatísticas -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-chart-pie"></i> Estatísticas do Banco de Dados
            </div>
            <table>
                <?php foreach ($stats as $chave => $valor): ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $chave)); ?></td>
                    <td><?php echo is_numeric($valor) ? number_format($valor, 0, ',', '.') : htmlspecialchars($valor); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
