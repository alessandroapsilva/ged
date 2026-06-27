<?php
/**
 * Sistema de Saúde e Diagnóstico do GED
 * Acesse em: /health_check.php
 */

require_once '../config.php';

class HealthCheck {
    private $results = [];
    private $score = 100;
    
    public function run() {
        $this->checkPhp();
        $this->checkDatabase();
        $this->checkFileSystem();
        $this->checkSecurity();
        $this->checkEmail();
        $this->checkExtensions();
        
        return $this;
    }
    
    private function checkPhp() {
        $this->results['php'] = [
            'version' => phpversion(),
            'status' => version_compare(phpversion(), '7.4', '>=') ? 'OK' : 'WARNING',
            'memory_limit' => ini_get('memory_limit'),
            'max_upload' => ini_get('upload_max_filesize'),
            'max_post' => ini_get('post_max_size'),
            'execution_time' => ini_get('max_execution_time') . 's'
        ];
    }
    
    private function checkDatabase() {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            $tables = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'")->fetchColumn();
            
            $this->results['database'] = [
                'status' => 'OK',
                'host' => DB_HOST,
                'database' => DB_NAME,
                'version' => $version,
                'tables' => $tables
            ];
        } catch (Exception $e) {
            $this->results['database'] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            $this->score -= 30;
        }
    }
    
    private function checkFileSystem() {
        $dirs = [
            '../logs' => 'Logs',
            '../storage' => 'Storage',
            '../uploads' => 'Uploads',
            '../public' => 'Public'
        ];
        
        $fs_check = [];
        
        foreach ($dirs as $dir => $name) {
            $path = __DIR__ . '/' . $dir;
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            
            $fs_check[$name] = [
                'exists' => $exists ? 'Yes' : 'NO',
                'writable' => $writable ? 'Yes' : 'NO',
                'status' => $writable ? 'OK' : 'WARNING'
            ];
            
            if (!$writable) {
                $this->score -= 5;
            }
        }
        
        $this->results['filesystem'] = $fs_check;
    }
    
    private function checkSecurity() {
        $security = [];
        
        // HTTPS
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $security['https'] = [
            'status' => $https ? 'OK' : 'WARNING',
            'value' => $https ? 'Yes' : 'No'
        ];
        if (!$https) $this->score -= 10;
        
        // Headers de segurança
        $headers = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection'
        ];
        
        foreach ($headers as $header) {
            $value = headers_list() ? 'Verificado' : 'Não verificado';
            $security['header_' . str_replace('-', '_', strtolower($header))] = [
                'status' => 'OK',
                'value' => $value
            ];
        }
        
        // Session config
        $security['session_httponly'] = [
            'status' => (ini_get('session.cookie_httponly') ? 'OK' : 'WARNING'),
            'value' => ini_get('session.cookie_httponly') ? 'Yes' : 'No'
        ];
        
        $this->results['security'] = $security;
    }
    
    private function checkEmail() {
        $email_check = [];
        
        $email_check['smtp_host'] = ['value' => SMTP_HOST ?? 'Not configured'];
        $email_check['smtp_port'] = ['value' => SMTP_PORT ?? 'Not configured'];
        $email_check['mail_from'] = ['value' => MAIL_FROM ?? 'Not configured'];
        
        $this->results['email'] = $email_check;
    }
    
    private function checkExtensions() {
        $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl', 'zip'];
        $optional = ['imagick', 'gd', 'openssl'];
        
        $ext_check = [];
        
        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            $ext_check['required_' . $ext] = [
                'status' => $loaded ? 'OK' : 'ERROR',
                'value' => $loaded ? 'Loaded' : 'Not loaded'
            ];
            
            if (!$loaded) {
                $this->score -= 5;
            }
        }
        
        foreach ($optional as $ext) {
            $loaded = extension_loaded($ext);
            $ext_check['optional_' . $ext] = [
                'status' => $loaded ? 'OK' : 'WARNING',
                'value' => $loaded ? 'Loaded' : 'Not loaded'
            ];
        }
        
        $this->results['extensions'] = $ext_check;
    }
    
    public function getScore() {
        return max(0, $this->score);
    }
    
    public function getResults() {
        return $this->results;
    }
    
    public function getStatus() {
        $score = $this->getScore();
        
        if ($score >= 90) return 'EXCELLENT';
        if ($score >= 75) return 'GOOD';
        if ($score >= 60) return 'WARNING';
        return 'CRITICAL';
    }
}

// Executa verificação
$health = new HealthCheck();
$health->run();

// Determina content-type baseado em Accept header
$accept = $_GET['format'] ?? $_SERVER['HTTP_ACCEPT'] ?? 'text/html';

if (strpos($accept, 'application/json') !== false || $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $health->getStatus(),
        'score' => $health->getScore(),
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $health->getResults()
    ], JSON_PRETTY_PRINT);
    exit;
}

// HTML Response
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED - Health Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .score-badge {
            display: inline-block;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 20px 0;
        }
        .status-excellent { color: #10b981; }
        .status-good { color: #3b82f6; }
        .status-warning { color: #f59e0b; }
        .status-critical { color: #ef4444; }
        
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            color: #1e293b;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        .check-item-name {
            font-weight: 600;
            color: #1e293b;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-ok {
            background: #dcfce7;
            color: #15803d;
        }
        .status-warning-badge {
            background: #fef3c7;
            color: #92400e;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            padding: 20px;
            background: #f8fafc;
            border-radius: 4px;
            border-left: 4px solid #2563eb;
        }
        .info-card-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #1e293b;
        }
        .info-card-value {
            font-family: monospace;
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 GED Health Check</h1>
            <div class="score-badge status-<?php echo strtolower($health->getStatus()); ?>">
                <?php echo $health->getScore(); ?>%
            </div>
            <p>Status: <strong><?php echo $health->getStatus(); ?></strong></p>
            <p style="font-size: 0.9rem; opacity: 0.9;">Última verificação: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="content">
            <?php foreach ($health->getResults() as $section => $items): ?>
            <div class="section">
                <h2 class="section-title"><?php echo ucfirst(str_replace('_', ' ', $section)); ?></h2>
                
                <?php if ($section === 'php'): ?>
                    <div class="info-grid">
                        <?php foreach ($items as $key => $value): ?>
                            <div class="info-card">
                                <div class="info-card-title"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></div>
                                <div class="info-card-value"><?php echo $value; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                <?php elseif ($section === 'database'): ?>
                    <?php if ($items['status'] === 'OK'): ?>
                        <div class="check-item">
                            <span class="check-item-name">✓ Conectado</span>
                            <span class="status-badge status-ok">OK</span>
                        </div>
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-title">Host</div>
                                <div class="info-card-value"><?php echo $items['host']; ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-title">Database</div>
                                <div class="info-card-value"><?php echo $items['database']; ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-title">Versão</div>
                                <div class="info-card-value"><?php echo $items['version']; ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-title">Tabelas</div>
                                <div class="info-card-value"><?php echo $items['tables']; ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="check-item">
                            <span class="check-item-name">✗ Erro de conexão</span>
                            <span class="status-badge status-error">ERRO</span>
                        </div>
                        <div style="padding: 15px; background: #fee2e2; border-radius: 4px; margin-top: 10px;">
                            <strong style="color: #991b1b;">Erro:</strong>
                            <pre style="margin-top: 10px; color: #991b1b; font-size: 0.9rem;"><?php echo htmlspecialchars($items['error']); ?></pre>
                        </div>
                    <?php endif; ?>
                
                <?php else: ?>
                    <?php foreach ($items as $name => $item): ?>
                        <div class="check-item">
                            <span class="check-item-name"><?php echo ucfirst(str_replace(['_', 'optional', 'required'], [' ', '', ''], $name)); ?></span>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="color: #64748b;"><?php echo $item['value'] ?? '-'; ?></span>
                                <?php 
                                    $status_class = 'status-ok';
                                    if (isset($item['status'])) {
                                        if ($item['status'] === 'ERROR') $status_class = 'status-error';
                                        elseif ($item['status'] === 'WARNING') $status_class = 'status-warning-badge';
                                    }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $item['status'] ?? 'OK'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>GED Professional v1.0 | Relatório de Saúde do Sistema</p>
            <p>JSON API disponível em: <code>?format=json</code></p>
        </div>
    </div>
</body>
</html>
